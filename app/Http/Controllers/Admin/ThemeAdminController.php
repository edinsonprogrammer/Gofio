<?php

/**
 * Controlador admin de temas visuales: listado, sincronización desde carpetas y CRUD de temas.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Services\IconPackService;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ThemeAdminController extends Controller
{
    public function __construct(
        private readonly ThemeService $themeService,
        private readonly IconPackService $iconPackService,
    ) {}

    /**
     * GET /admin/temas — responde con la vista Inertia Admin/Themes/Index y el catálogo de temas.
     */
    public function index(): Response
    {
        $sync = $this->themeService->syncFolderThemes();

        // Renderiza el gestor de temas con resultado de sincronización
        return Inertia::render('Admin/Themes/Index', [
            'themes' => $this->themeService->listForAdmin()->map(fn (Theme $theme) => [
                'id'                    => $theme->id,
                'name'                  => $theme->name,
                'slug'                  => $theme->slug,
                'source'                => $theme->source,
                'author'                => $theme->author,
                'version'               => $theme->version,
                'description'           => $theme->description,
                'is_default'            => $theme->is_default,
                'requires_creator_plus' => $theme->requires_creator_plus,
                'is_active'             => $theme->is_active,
                'variables'             => $theme->cssVariables(),
                'has_custom_css'        => filled($theme->custom_css),
                'icon_pack_slug'        => $theme->icon_pack_slug,
                'layout_variant'        => $theme->layout_variant ?? 'default',
                'logo_url'              => $theme->logo_url,
                'users_count'           => $theme->users()->count(),
            ]),
            'variableKeys' => $this->themeService->variableKeys(),
            'variableGroups' => \App\Support\ThemeVariables::groups(),
            'colorGroups' => \App\Support\ThemeVariables::colorGroups(),
            'variableSectors' => \App\Support\ThemeVariables::sectorLabels(),
            'defaults' => $this->themeService->defaultVariables(),
            'themesPath' => $sync['path'],
            'lastSync' => $sync,
            'iconPacks' => $this->iconPackService->listForAdmin()->map(fn ($pack) => [
                'slug' => $pack->slug,
                'name' => $pack->name,
            ])->values(),
        ]);
    }

    /**
     * POST /admin/temas/sincronizar — importa temas desde el directorio de carpetas y redirige de vuelta.
     */
    public function sync(): RedirectResponse
    {
        $result = $this->themeService->syncFolderThemes();

        if ($result['total'] === 0) {
            return back()->with('error', "No se encontraron carpetas de tema válidas en {$result['path']}.");
        }

        $parts = [];
        if ($result['created']) {
            $parts[] = 'instalados: '.implode(', ', $result['created']);
        }
        if ($result['updated']) {
            $parts[] = 'actualizados: '.implode(', ', $result['updated']);
        }

        return back()->with('success', 'Temas sincronizados ('.implode(' · ', $parts).').');
    }

    /**
     * POST /admin/temas — valida y crea un nuevo tema; redirige de vuelta con mensaje de éxito.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTheme($request);

        $this->themeService->createTheme(
            $this->combinationPayload($data, source: 'database'),
            $data['variables'] ?? [],
        );

        return back()->with('success', 'Tema creado correctamente.');
    }

    /**
     * PUT /admin/temas/{theme} — valida y actualiza el tema existente; redirige de vuelta.
     */
    public function update(Request $request, Theme $theme): RedirectResponse
    {
        $data = $this->validateTheme($request, $theme->id);

        // Los temas de carpeta solo permiten cambiar flags de administración; la paleta vive en theme.json
        if ($theme->isFolderTheme()) {
            $this->themeService->updateTheme(
                $theme,
                [
                    'is_default'            => $data['is_default'] ?? false,
                    'requires_creator_plus' => $data['requires_creator_plus'] ?? false,
                    'is_active'             => $data['is_active'] ?? true,
                ],
                $theme->cssVariables(),
            );
        } else {
            $this->themeService->updateTheme(
                $theme,
                $this->combinationPayload($data),
                $data['variables'] ?? [],
            );
        }

        return back()->with('success', "Tema «{$theme->name}» actualizado.");
    }

    /**
     * DELETE /admin/temas/{theme} — elimina una combinación de colores creada en el panel.
     */
    public function destroy(Theme $theme): RedirectResponse
    {
        $name = $theme->name;
        $this->themeService->deleteTheme($theme);

        return back()->with('success', "Combinación de colores «{$name}» eliminada.");
    }

    /**
     * Valida los campos del formulario de creación o edición de un tema.
     */
    private function validateTheme(Request $request, ?int $themeId = null): array
    {
        // Valida metadatos y variables CSS de color; la estructura de página no se modifica desde combinaciones
        return $request->validate([
            'name'               => ['required', 'string', 'max:80'],
            'slug'               => ['required', 'string', 'max:80', 'alpha_dash', 'unique:themes,slug,'.($themeId ?? 'NULL')],
            'description'        => ['nullable', 'string', 'max:255'],
            'is_default'         => ['boolean'],
            'requires_creator_plus' => ['boolean'],
            'is_active'          => ['boolean'],
            'variables'          => ['array'],
            'variables.*'        => ['nullable', 'string', 'max:512'],
        ]);
    }

    /**
     * Arma el payload de una combinación de colores sin alterar layout, logo ni iconos.
     */
    private function combinationPayload(array $data, ?string $source = null): array
    {
        $payload = [
            'name'                  => $data['name'],
            'slug'                  => $data['slug'],
            'description'           => $data['description'] ?? null,
            'is_default'            => $data['is_default'] ?? false,
            'requires_creator_plus' => $data['requires_creator_plus'] ?? false,
            'is_active'             => $data['is_active'] ?? true,
            'layout_variant'        => 'default',
            'logo_url'              => null,
            'icon_pack_slug'        => null,
        ];

        if ($source !== null) {
            $payload['source'] = $source;
        }

        return $payload;
    }
}
