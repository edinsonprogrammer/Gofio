<?php

namespace App\Services;

/**
 * Resuelve y valida los permisos de creación de posts según rango, rol premium y configuración del sitio.
 */

use App\Models\Post;
use App\Models\RolRango;
use App\Models\User;
use App\Repositories\SiteSettingsRepository;
use Illuminate\Validation\ValidationException;

class PostPermissionService
{
    /**
     * Inyecta el repositorio de ajustes globales del sitio.
     */
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
    ) {}

    /**
     * Calcula el mapa completo de permisos de posts para un usuario, incluyendo uso diario actual.
     */
    public function forUser(User $user): array
    {
        $user->loadMissing('rango');

        $permissions = $this->resolveFromRango($user->rango);

        if ($user->isCreatorPlus()) {
            $permissions = $this->applyCreatorPlusBoost($permissions);
        }

        if ($user->isAdmin() || $user->hasStaffRank()) {
            $permissions = $this->applyStaffBoost($permissions, $user->isAdmin());
        }

        $permissions['posts_today'] = $this->countPostsToday($user);
        $permissions['can_create'] = $permissions['posts_today'] < $permissions['max_posts_per_day'];
        $permissions['max_comments_per_day'] = (int) ($permissions['max_comments_per_day'] ?? $this->siteDefaultCommentsLimit());

        return $permissions;
    }

    /**
     * Valida que el usuario pueda crear un post con los datos enviados o lanza excepción de validación.
     */
    public function assertCanCreate(User $user, array $data): void
    {
        $permissions = $this->forUser($user);

        if (! $permissions['can_create']) {
            throw ValidationException::withMessages([
                'title' => ["Has alcanzado tu límite diario de {$permissions['max_posts_per_day']} posts."],
            ]);
        }

        $titleMax = (int) $permissions['max_title_length'];
        $title = (string) ($data['title'] ?? '');

        if (mb_strlen($title) > $titleMax) {
            throw ValidationException::withMessages([
                'title' => ["El título no puede superar {$titleMax} caracteres para tu rango."],
            ]);
        }

        $status = $data['status'] ?? 'published';
        if ($status === 'draft' && ! ($permissions['options']['draft'] ?? false)) {
            throw ValidationException::withMessages([
                'status' => ['Tu rango no permite guardar borradores.'],
            ]);
        }

        if (($data['block_comments'] ?? false) && ! ($permissions['options']['block_comments'] ?? false)) {
            throw ValidationException::withMessages([
                'block_comments' => ['Tu rango no permite bloquear comentarios.'],
            ]);
        }

        if (($data['is_private'] ?? false) && ! ($permissions['options']['private'] ?? false)) {
            throw ValidationException::withMessages([
                'is_private' => ['Tu rango no permite posts privados.'],
            ]);
        }

        if (! empty($data['tags']) && ! ($permissions['options']['tags'] ?? false)) {
            throw ValidationException::withMessages([
                'tags' => ['Tu rango no permite etiquetas en posts.'],
            ]);
        }

        $blocks = $data['content']['blocks'] ?? $data['content'] ?? [];
        $this->assertContentAllowed($permissions, is_array($blocks) ? $blocks : []);
    }

    /**
     * Expone los permisos base predefinidos para un nombre de rango (uso en administración).
     */
    public function defaultsForRankName(string $nombre): array
    {
        return $this->defaultPermissionsByName($nombre);
    }

    /**
     * Indica si el usuario puede subir imágenes en posts según herramientas y cuota permitida.
     */
    public function canUploadImage(User $user): bool
    {
        $permissions = $this->forUser($user);

        return in_array('image', $permissions['tools'], true)
            && ($permissions['max_images_per_post'] ?? 0) > 0;
    }

    /**
     * Devuelve el tamaño máximo en KB permitido para imágenes del usuario.
     */
    public function maxImageSizeKb(User $user): int
    {
        return (int) ($this->forUser($user)['max_image_size_kb'] ?? 5120);
    }

    /**
     * Verifica que los bloques de contenido respeten herramientas, límites de imágenes y longitud de texto.
     */
    private function assertContentAllowed(array $permissions, array $blocks): void
    {
        $allowedTools = $permissions['tools'];
        $imageCount = 0;
        $gifCount = 0;
        $maxImages = (int) ($permissions['max_images_per_post'] ?? 0);
        $maxGifs = (int) ($permissions['max_gifs_per_post'] ?? 5);
        $maxContentLength = (int) ($permissions['max_content_length'] ?? 5000);
        $contentLength = 0;

        foreach ($blocks as $block) {
            if (! is_array($block) || empty($block['type'])) {
                continue;
            }

            $type = $block['type'];

            if (in_array($type, ['paragraph', 'header', 'quote'], true)) {
                $contentLength += mb_strlen(trim(strip_tags((string) ($block['data']['text'] ?? ''))));
            }

            if ($type === 'image') {
                $imageCount++;
                if (! in_array('image', $allowedTools, true)) {
                    throw ValidationException::withMessages([
                        'content' => ['Tu rango no permite insertar imágenes en posts.'],
                    ]);
                }

                continue;
            }

            if ($type === 'gif') {
                $gifCount++;
                if (! in_array('gif', $allowedTools, true)) {
                    throw ValidationException::withMessages([
                        'content' => ['Tu rango no permite insertar GIFs de GIPHY en posts.'],
                    ]);
                }

                continue;
            }

            if ($type === 'embed' && ! in_array('embed', $allowedTools, true)) {
                throw ValidationException::withMessages([
                    'content' => ['Tu rango no permite insertar embeds.'],
                ]);
            }

            if (! in_array($type, $allowedTools, true)) {
                throw ValidationException::withMessages([
                    'content' => ["Tu rango no permite usar el bloque «{$type}»."],
                ]);
            }
        }

        if ($imageCount > $maxImages) {
            throw ValidationException::withMessages([
                'content' => ["Tu rango permite máximo {$maxImages} imagen(es) por post."],
            ]);
        }

        if ($gifCount > $maxGifs) {
            throw ValidationException::withMessages([
                'content' => ["Tu rango permite máximo {$maxGifs} GIF(s) de GIPHY por post."],
            ]);
        }

        if ($contentLength > $maxContentLength) {
            throw ValidationException::withMessages([
                'content' => ["Tu rango permite un máximo de {$maxContentLength} caracteres por post (llevas {$contentLength})."],
            ]);
        }
    }

    /**
     * Cuenta cuántos posts ha creado el usuario desde el inicio del día actual.
     */
    private function countPostsToday(User $user): int
    {
        return Post::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * Obtiene permisos desde el rango del usuario, fusionando overrides JSON y defaults del sitio.
     */
    private function resolveFromRango(?RolRango $rango): array
    {
        $defaults = $this->defaultPermissionsByName($rango?->nombre ?? 'Newbie');

        if ($rango?->post_permissions) {
            $permissions = $this->normalizePermissions($rango->post_permissions, $defaults);
        } else {
            $permissions = $defaults;
        }

        return $this->ensureGifTool($this->applySiteDefaultsForStartingRank($rango, $permissions));
    }

    /**
     * Garantiza la herramienta gif cuando el rango ya permite imágenes (compatibilidad con permisos antiguos).
     */
    private function ensureGifTool(array $permissions): array
    {
        $tools = $permissions['tools'] ?? [];

        if (in_array('image', $tools, true) && ! in_array('gif', $tools, true)) {
            $permissions['tools'][] = 'gif';
        }

        if (! array_key_exists('max_gifs_per_post', $permissions)) {
            $permissions['max_gifs_per_post'] = 5;
        }

        return $permissions;
    }

    /**
     * Aplica límites globales del sitio al rango inicial cuando no hay override explícito en el rango.
     */
    private function applySiteDefaultsForStartingRank(?RolRango $rango, array $permissions): array
    {
        $defaultRankId = (int) $this->siteSettingsRepository->get('default_rango_id', 1);
        $isStartingRank = $rango === null
            || $rango->id === $defaultRankId
            || strtolower($rango->nombre) === 'newbie';

        if (! $isStartingRank) {
            return $permissions;
        }

        $overrides = $rango?->post_permissions ?? [];
        if (is_string($overrides)) {
            $overrides = json_decode($overrides, true) ?: [];
        }

        if (! is_array($overrides)) {
            $overrides = [];
        }

        if (! array_key_exists('max_posts_per_day', $overrides)) {
            $permissions['max_posts_per_day'] = (int) $this->siteSettingsRepository->get('max_posts_per_day', 10);
        }

        if (! array_key_exists('max_comments_per_day', $overrides)) {
            $permissions['max_comments_per_day'] = (int) $this->siteSettingsRepository->get('max_comments_per_day', 30);
        }

        return $permissions;
    }

    /**
     * Fusiona permisos personalizados del rango sobre la plantilla por defecto, decodificando JSON si aplica.
     */
    private function normalizePermissions(array|string|null $permissions, array $defaults): array
    {
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        return array_replace_recursive($defaults, is_array($permissions) ? $permissions : []);
    }

    /**
     * Devuelve la matriz de permisos predefinida según el nombre del rango de gamificación.
     */
    private function defaultPermissionsByName(string $nombre): array
    {
        return match ($nombre) {
            'Administrador' => $this->buildPermissions(
                tools: ['header', 'paragraph', 'code', 'image', 'gif', 'embed', 'quote', 'list', 'delimiter'],
                headerLevels: [2, 3, 4],
                embedServices: ['youtube', 'twitter', 'tiktok', 'instagram', 'vimeo'],
                maxImages: 20,
                maxImageKb: 51200,
                maxGifs: 10,
                maxTitle: 120,
                maxContentLength: 10000,
                maxPostsDay: 50,
                maxCommentsDay: 200,
                options: ['tags' => true, 'block_comments' => true, 'draft' => true, 'private' => true],
            ),
            'Moderador' => $this->buildPermissions(
                tools: ['header', 'paragraph', 'code', 'image', 'gif', 'embed', 'quote', 'list', 'delimiter'],
                headerLevels: [2, 3, 4],
                embedServices: ['youtube', 'twitter', 'tiktok', 'instagram'],
                maxImages: 18,
                maxImageKb: 30720,
                maxGifs: 8,
                maxTitle: 100,
                maxContentLength: 8000,
                maxPostsDay: 40,
                maxCommentsDay: 150,
                options: ['tags' => true, 'block_comments' => true, 'draft' => true, 'private' => true],
            ),
            'Diamond' => $this->buildPermissions(
                tools: ['header', 'paragraph', 'code', 'image', 'gif', 'embed', 'quote', 'list', 'delimiter'],
                headerLevels: [2, 3, 4],
                embedServices: ['youtube', 'twitter', 'tiktok', 'instagram'],
                maxImages: 15,
                maxImageKb: 25600,
                maxGifs: 8,
                maxTitle: 80,
                maxContentLength: 7000,
                maxPostsDay: 30,
                maxCommentsDay: 100,
                options: ['tags' => true, 'block_comments' => true, 'draft' => true, 'private' => false],
            ),
            'Platinum' => $this->buildPermissions(
                tools: ['header', 'paragraph', 'code', 'image', 'gif', 'embed', 'quote', 'list', 'delimiter'],
                headerLevels: [2, 3, 4],
                embedServices: ['youtube', 'twitter', 'tiktok'],
                maxImages: 12,
                maxImageKb: 15360,
                maxGifs: 6,
                maxTitle: 70,
                maxContentLength: 6000,
                maxPostsDay: 25,
                maxCommentsDay: 80,
                options: ['tags' => true, 'block_comments' => true, 'draft' => false, 'private' => false],
            ),
            'Advanced' => $this->buildPermissions(
                tools: ['header', 'paragraph', 'code', 'image', 'gif', 'embed', 'quote', 'list', 'delimiter'],
                headerLevels: [2, 3, 4],
                embedServices: ['youtube', 'twitter'],
                maxImages: 10,
                maxImageKb: 20480,
                maxGifs: 5,
                maxTitle: 65,
                maxContentLength: 5500,
                maxPostsDay: 20,
                maxCommentsDay: 60,
                options: ['tags' => true, 'block_comments' => false, 'draft' => false, 'private' => false],
            ),
            'Usuario' => $this->buildPermissions(
                tools: ['header', 'paragraph', 'code', 'image', 'gif', 'quote', 'list', 'delimiter'],
                headerLevels: [2, 3],
                embedServices: [],
                maxImages: 5,
                maxImageKb: 10240,
                maxGifs: 3,
                maxTitle: 60,
                maxContentLength: 5000,
                maxPostsDay: 15,
                maxCommentsDay: 40,
                options: ['tags' => false, 'block_comments' => false, 'draft' => false, 'private' => false],
            ),
            'Newbie' => $this->buildStartingRankPermissions(),
            default => $this->buildStartingRankPermissions(),
        };
    }

    /**
     * Construye permisos restrictivos del rango inicial leyendo límites diarios desde ajustes del sitio.
     */
    private function buildStartingRankPermissions(): array
    {
        return $this->buildPermissions(
            tools: ['header', 'paragraph', 'code', 'image', 'gif'],
            headerLevels: [2, 3],
            embedServices: [],
            maxImages: 2,
            maxImageKb: 5120,
            maxGifs: 2,
            maxTitle: 60,
            maxContentLength: 3000,
            maxPostsDay: (int) $this->siteSettingsRepository->get('max_posts_per_day', 10),
            maxCommentsDay: (int) $this->siteSettingsRepository->get('max_comments_per_day', 30),
            options: ['tags' => false, 'block_comments' => false, 'draft' => false, 'private' => false],
        );
    }

    /**
     * Lee el límite diario de comentarios configurado globalmente en el sitio.
     */
    private function siteDefaultCommentsLimit(): int
    {
        return (int) $this->siteSettingsRepository->get('max_comments_per_day', 30);
    }

    /**
     * Arma la estructura normalizada de permisos de post a partir de parámetros individuales.
     */
    private function buildPermissions(
        array $tools,
        array $headerLevels,
        array $embedServices,
        int $maxImages,
        int $maxImageKb,
        int $maxGifs,
        int $maxTitle,
        int $maxContentLength,
        int $maxPostsDay,
        int $maxCommentsDay,
        array $options,
    ): array {
        return [
            'max_title_length' => $maxTitle,
            'max_content_length' => $maxContentLength,
            'max_posts_per_day' => $maxPostsDay,
            'max_comments_per_day' => $maxCommentsDay,
            'max_images_per_post' => $maxImages,
            'max_gifs_per_post' => $maxGifs,
            'max_image_size_kb' => $maxImageKb,
            'tools' => $tools,
            'header_levels' => $headerLevels,
            'embed_services' => $embedServices,
            'options' => $options,
        ];
    }

    /**
     * Amplía permisos para suscriptores Creator Plus: más imágenes, borradores y embeds básicos.
     */
    private function applyCreatorPlusBoost(array $permissions): array
    {
        $permissions['max_images_per_post'] = min(
            ($permissions['max_images_per_post'] ?? 0) + 3,
            25
        );
        $permissions['options']['draft'] = true;

        if (! in_array('embed', $permissions['tools'], true)) {
            $permissions['tools'][] = 'embed';
        }

        if (! in_array('gif', $permissions['tools'], true)) {
            $permissions['tools'][] = 'gif';
        }

        foreach (['youtube', 'twitter', 'tiktok'] as $service) {
            if (! in_array($service, $permissions['embed_services'], true)) {
                $permissions['embed_services'][] = $service;
            }
        }

        return $permissions;
    }

    /**
     * Eleva permisos de staff y administradores habilitando opciones avanzadas y mayor cuota diaria.
     */
    private function applyStaffBoost(array $permissions, bool $isAdmin): array
    {
        $permissions['options']['tags'] = true;
        $permissions['options']['block_comments'] = true;
        $permissions['options']['draft'] = true;
        $permissions['options']['private'] = true;
        $permissions['max_posts_per_day'] = $isAdmin ? 50 : 40;

        return $permissions;
    }
}
