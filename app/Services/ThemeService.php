<?php

namespace App\Services;

/**
 * Resuelve, selecciona y sincroniza temas visuales según permisos del usuario y archivos en disco.
 */

use App\Models\Theme;
use App\Models\User;
use App\Repositories\ThemeRepository;
use App\Services\ThemeFolderScanner;
use App\Support\ThemeVariables;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ThemeService
{
    /**
     * Inyecta repositorio de temas y escáner de carpetas del filesystem.
     */
    public function __construct(
        private readonly ThemeRepository $themeRepository,
        private readonly ThemeFolderScanner $themeFolderScanner,
    ) {}

    /**
     * Determina el tema activo del usuario o devuelve el predeterminado o fallback embebido.
     */
    public function resolveForUser(?User $user): Theme
    {
        if ($user?->theme_id) {
            $selected = $this->themeRepository->findById($user->theme_id);

            if ($selected && $selected->is_active && $this->canUseTheme($user, $selected)) {
                return $selected;
            }
        }

        return $this->themeRepository->getDefault()
            ?? $this->fallbackTheme();
    }

    /**
     * Prepara el payload compartido de tema (variables CSS, layout y logo) para inyectar en la vista.
     */
    public function sharePayload(?User $user): array
    {
        $theme = $this->resolveForUser($user);
        $variables = ThemeVariables::merge($theme->cssVariables());

        return [
            'slug'           => $theme->slug,
            'name'           => $theme->name,
            'variables'      => $variables,
            'custom_css'     => $theme->custom_css,
            // Variante de layout y logo enviados al frontend para aplicar estructura y marca
            'layout_variant' => $theme->layout_variant ?? 'default',
            'logo_url'       => $theme->logo_url,
        ];
    }

    /**
     * Lista temas disponibles para personalización, validando que el usuario tenga Creator Plus.
     */
    public function getAppearanceStatus(User $user): array
    {
        if (! $user->canCustomizeAppearance()) {
            throw ValidationException::withMessages([
                'appearance' => ['La personalización de apariencia requiere Creator Plus.'],
            ]);
        }

        $active = $this->resolveForUser($user);

        return [
            'active_theme_id' => $active->id,
            'themes' => $this->themeRepository->getAvailableForUser($user)
                ->map(fn (Theme $theme) => $this->formatTheme($theme, $user)),
        ];
    }

    /**
     * Asigna un tema al usuario tras validar existencia, permisos premium y personalización activa.
     */
    public function selectTheme(User $user, int $themeId): array
    {
        $theme = $this->themeRepository->findById($themeId);

        if (! $theme || ! $theme->is_active) {
            throw ValidationException::withMessages([
                'theme_id' => ['Tema no encontrado.'],
            ]);
        }

        if (! $user->canCustomizeAppearance()) {
            throw ValidationException::withMessages([
                'theme_id' => ['La personalización de apariencia requiere Creator Plus.'],
            ]);
        }

        if (! $this->canUseTheme($user, $theme)) {
            throw ValidationException::withMessages([
                'theme_id' => ['Este tema requiere Creator Plus.'],
            ]);
        }

        $user->update(['theme_id' => $theme->id]);

        return [
            'success' => true,
            'message' => "Tema «{$theme->name}» aplicado.",
            'theme' => $this->formatTheme($theme, $user),
            'variables' => ThemeVariables::merge($theme->cssVariables()),
        ];
    }

    /**
     * Crea un tema en base de datos, limpiando el flag default previo si el nuevo lo marca como tal.
     */
    public function createTheme(array $data, array $variables): Theme
    {
        if (! empty($data['is_default'])) {
            $this->themeRepository->clearDefaultFlag();
        }

        return $this->themeRepository->create([
            ...$data,
            'variables' => ThemeVariables::merge($variables),
        ]);
    }

    /**
     * Actualiza un tema existente fusionando variables CSS y gestionando el flag de tema default.
     */
    public function updateTheme(Theme $theme, array $data, array $variables): Theme
    {
        if (! empty($data['is_default'])) {
            $this->themeRepository->clearDefaultFlag();
        }

        return $this->themeRepository->update($theme, [
            ...$data,
            'variables' => ThemeVariables::merge($variables),
        ]);
    }

    /**
     * Elimina una combinación de colores creada en el panel (source=database).
     * Los temas de carpeta no se pueden eliminar; los usuarios afectados vuelven al predeterminado.
     */
    public function deleteTheme(Theme $theme): void
    {
        if ($theme->isFolderTheme()) {
            throw ValidationException::withMessages([
                'theme' => ['Los temas instalados desde carpeta no se eliminan aquí. Desactívalos o quita la carpeta del proyecto.'],
            ]);
        }

        if ($theme->is_default) {
            throw ValidationException::withMessages([
                'theme' => ['No puedes eliminar el tema predeterminado. Asigna otro como predeterminado antes.'],
            ]);
        }

        // Los usuarios con este tema activo pasan a usar el predeterminado del sistema
        $this->themeRepository->clearUserSelections($theme);
        $this->themeRepository->delete($theme);
    }

    /**
     * Devuelve todos los temas para administración, ordenados por default y nombre.
     */
    public function listForAdmin(): Collection
    {
        return $this->themeRepository->getAllForAdmin();
    }

    /**
     * Importa o actualiza temas desde carpetas del filesystem y reporta creados versus actualizados.
     */
    public function syncFolderThemes(): array
    {
        $found = $this->themeFolderScanner->scan();

        $created = [];
        $updated = [];

        foreach ($found as $entry) {
            $manifest = $entry['manifest'];

            [$theme, $wasCreated] = $this->themeRepository->upsertFolderTheme(
                slug: $manifest->slug,
                designAttributes: [
                    'name'           => $manifest->name,
                    'source'         => 'folder',
                    'description'    => $manifest->description,
                    'author'         => $manifest->author,
                    'version'        => $manifest->version,
                    'variables'      => ThemeVariables::merge($manifest->variables),
                    'custom_css'     => $entry['custom_css'],
                    'icon_pack_slug' => $manifest->iconPack,
                    // Nuevos campos de layout y logo sincronizados desde la carpeta
                    'layout_variant' => $manifest->layoutVariant,
                    'logo_url'       => $manifest->logoUrl,
                ],
                isDefault: false,
                requiresCreatorPlus: $manifest->requiresCreatorPlus,
            );

            if ($wasCreated && $manifest->isDefault) {
                $this->themeRepository->clearDefaultFlag();
                $theme->update(['is_default' => true]);
            }

            if ($wasCreated) {
                $created[] = $theme->name;
            } else {
                $updated[] = $theme->name;
            }
        }

        return [
            'path' => $this->themeFolderScanner->themesPath(),
            'created' => $created,
            'updated' => $updated,
            'total' => count($found),
        ];
    }

    /**
     * Expone las claves de variables CSS configurables para formularios de administración.
     */
    public function variableKeys(): array
    {
        return ThemeVariables::keys();
    }

    /**
     * Devuelve los valores por defecto del sistema para variables CSS de temas.
     */
    public function defaultVariables(): array
    {
        return ThemeVariables::defaults();
    }

    /**
     * Verifica si el usuario puede aplicar un tema concreto según Creator Plus y permisos de staff.
     */
    private function canUseTheme(User $user, Theme $theme): bool
    {
        if (! $user->canCustomizeAppearance()) {
            return false;
        }

        if ($theme->requires_creator_plus) {
            return $user->isCreatorPlus() || $user->isAdmin() || $user->hasStaffRank();
        }

        return true;
    }

    /**
     * Indica si el usuario puede acceder a temas premium (Creator Plus, admin o staff).
     */
    private function canUsePremiumThemes(User $user): bool
    {
        return $user->isCreatorPlus() || $user->isAdmin() || $user->hasStaffRank();
    }

    /**
     * Serializa un tema para selector de apariencia con vista previa de colores y estado bloqueado.
     */
    private function formatTheme(Theme $theme, User $user): array
    {
        return [
            'id' => $theme->id,
            'name' => $theme->name,
            'slug' => $theme->slug,
            'source' => $theme->source,
            'description' => $theme->description,
            'is_default' => $theme->is_default,
            'requires_creator_plus' => $theme->requires_creator_plus,
            'preview' => $this->previewForTheme($theme),
            'locked' => $theme->requires_creator_plus && ! $this->canUsePremiumThemes($user),
        ];
    }

    /**
     * Construye la vista previa con variables fusionadas y colores reales que se aplican al activar el tema.
     */
    private function previewForTheme(Theme $theme): array
    {
        $variables = ThemeVariables::merge($theme->cssVariables());
        $topbar = $variables['--color-topbar-gradient-from'] ?? '#0F766E';

        if ($theme->slug === 'inicio-login'
            || ($theme->custom_css && str_contains($theme->custom_css, 'linear-gradient(#262626'))) {
            $topbar = '#262626';
        }

        return [
            'brand' => $variables['--color-brand'] ?? '#0D9488',
            'brand_hover' => $variables['--color-brand-hover'] ?? '#0F766E',
            'background' => $variables['--bg-principal'] ?? '#F1F5F9',
            'surface' => $variables['--color-surface'] ?? '#FFFFFF',
            'topbar' => $topbar,
            'header' => $variables['--color-topbar-gradient-from'] ?? $variables['--color-brand'] ?? '#0D9488',
            'accent' => $variables['--color-accent'] ?? $variables['--color-brand'] ?? '#0D9488',
            'border' => $variables['--color-border'] ?? '#CBD5E1',
            'panel' => $variables['--color-panel'] ?? '#F0FDFA',
        ];
    }

    /**
     * Construye un tema en memoria con valores por defecto cuando no hay registro en base de datos.
     */
    private function fallbackTheme(): Theme
    {
        return new Theme([
            'name' => 'Menta Tecnológico',
            'slug' => 'mint',
            'variables' => ThemeVariables::defaults(),
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
