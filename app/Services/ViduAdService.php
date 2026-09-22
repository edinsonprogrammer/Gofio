<?php

/**
 * Pausas publicitarias Vidu: configuración global, creativos con rotación aleatoria,
 * banners laterales y elegibilidad por video/espectador.
 */

namespace App\Services;

use App\Models\User;
use App\Models\ViduAdBanner;
use App\Models\ViduAdCreative;
use App\Models\ViduVideo;
use App\Repositories\SiteSettingsRepository;
use App\Support\ViduAdDefaults;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ViduAdService
{
    private const SETTINGS_KEY = 'vidu_ad_breaks';

    public function __construct(
        private readonly SiteSettingsRepository $siteSettings,
    ) {}

    /**
     * Devuelve la configuración global fusionada con los valores por defecto.
     */
    public function settings(): array
    {
        $stored = $this->siteSettings->get(self::SETTINGS_KEY, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        return array_merge(ViduAdDefaults::settings(), $stored);
    }

    /**
     * Persiste la configuración global de pausas publicitarias.
     */
    public function updateSettings(array $data): void
    {
        $current = $this->settings();

        $merged = array_merge($current, [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'apply_mode' => $data['apply_mode'] ?? $current['apply_mode'],
            'viewer_mode' => $data['viewer_mode'] ?? $current['viewer_mode'],
            'min_video_seconds' => max(60, (int) ($data['min_video_seconds'] ?? 60)),
            'trigger_min_seconds' => max(10, (int) ($data['trigger_min_seconds'] ?? 20)),
            'trigger_max_percent' => min(90, max(30, (int) ($data['trigger_max_percent'] ?? 75))),
            'sidebar_banners_enabled' => (bool) ($data['sidebar_banners_enabled'] ?? $current['sidebar_banners_enabled'] ?? false),
        ]);

        $this->siteSettings->set(self::SETTINGS_KEY, $merged);
    }

    // -----------------------------------------------------------------------
    // Creativos (videos publicitarios)
    // -----------------------------------------------------------------------

    /**
     * Sube un nuevo creativo publicitario y lo deja inactivo por defecto.
     * El admin debe activarlo explícitamente en el panel.
     */
    public function uploadCreative(UploadedFile $file, string $name, int $durationSeconds): ViduAdCreative
    {
        $path = $file->store('vidu/ads/'.date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);

        return ViduAdCreative::create([
            'name' => trim($name) !== '' ? trim($name) : 'Anuncio Vidu',
            'video_path' => $path,
            'video_url' => $url,
            'duration_seconds' => $durationSeconds,
            'is_active' => false,
        ]);
    }

    /**
     * Activa o desactiva un creativo; los activos rotan de forma aleatoria en el feed.
     */
    public function toggleCreative(ViduAdCreative $creative): bool
    {
        $newState = ! $creative->is_active;
        $creative->update(['is_active' => $newState]);

        return $newState;
    }

    /**
     * Elimina un creativo y su archivo del disco público.
     */
    public function deleteCreative(ViduAdCreative $creative): void
    {
        if ($creative->video_path && Storage::disk('public')->exists($creative->video_path)) {
            Storage::disk('public')->delete($creative->video_path);
        }

        $creative->delete();
    }

    /**
     * Devuelve todos los creativos activos para rotación.
     */
    public function activeCreatives()
    {
        return ViduAdCreative::query()->where('is_active', true)->orderBy('id')->get();
    }

    // -----------------------------------------------------------------------
    // Banners laterales
    // -----------------------------------------------------------------------

    /**
     * Sube un banner vertical para la barra lateral derecha del feed Vidu.
     */
    public function uploadBanner(UploadedFile $file, string $name, ?string $linkUrl = null): ViduAdBanner
    {
        $path = $file->store('vidu/banners/'.date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);

        return ViduAdBanner::create([
            'name' => trim($name) !== '' ? trim($name) : 'Banner Vidu',
            'image_path' => $path,
            'image_url' => $url,
            'link_url' => $linkUrl !== null && trim($linkUrl) !== '' ? trim($linkUrl) : null,
            'is_active' => true,
        ]);
    }

    /**
     * Activa o desactiva un banner lateral sin eliminarlo.
     */
    public function setBannerActive(ViduAdBanner $banner, bool $active): void
    {
        $banner->update(['is_active' => $active]);
    }

    /**
     * Elimina un banner lateral y su archivo del disco público.
     */
    public function deleteBanner(ViduAdBanner $banner): void
    {
        if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();
    }

    /**
     * Resuelve un banner lateral aleatorio estable para un video del feed.
     */
    public function resolveSidebarBanner(ViduVideo $video): ?array
    {
        $settings = $this->settings();

        if (! ($settings['sidebar_banners_enabled'] ?? false)) {
            return null;
        }

        $banners = ViduAdBanner::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($banners->isEmpty()) {
            return null;
        }

        $index = abs(crc32('vidu-sidebar-banner-'.$video->id)) % $banners->count();
        $banner = $banners->values()->get($index);

        return [
            'id' => $banner->id,
            'name' => $banner->name,
            'image_url' => $banner->image_url,
            'link_url' => $banner->link_url,
        ];
    }

    // -----------------------------------------------------------------------
    // Overrides por video
    // -----------------------------------------------------------------------

    /**
     * Actualiza el override de publicidad de un video concreto.
     */
    public function setVideoOverride(ViduVideo $video, ?bool $override): void
    {
        $video->update(['ads_override' => $override]);
    }

    /**
     * Aplica una acción masiva sobre el campo ads_override de los videos Vidu.
     */
    public function bulkUpdateVideos(string $action): int
    {
        $query = ViduVideo::query()->where('status', '!=', 'banned');

        return match ($action) {
            'enable_all' => $query->update(['ads_override' => true]),
            'disable_all' => $query->update(['ads_override' => false]),
            'reset_all' => $query->update(['ads_override' => null]),
            default => 0,
        };
    }

    // -----------------------------------------------------------------------
    // Elegibilidad y resolución de la pausa publicitaria
    // -----------------------------------------------------------------------

    /**
     * Indica si un video debe mostrar pausa publicitaria según reglas globales y override.
     * Los overrides manuales siempre tienen prioridad sobre la regla global.
     */
    public function videoEligibleForAds(ViduVideo $video): bool
    {
        // Override manual tiene prioridad absoluta.
        if ($video->ads_override === true) {
            return true;
        }

        if ($video->ads_override === false) {
            return false;
        }

        $settings = $this->settings();

        // Sistema desactivado: sin overrides activos no hay anuncios.
        if (! $settings['enabled']) {
            return false;
        }

        // Videos muy cortos no son elegibles.
        if ($video->duration_seconds < $settings['min_video_seconds']) {
            return false;
        }

        // Sin creativos activos no se puede mostrar ningún anuncio.
        if (! ViduAdCreative::query()->where('is_active', true)->exists()) {
            return false;
        }

        // Evalúa el modo de aplicación según el creador del video.
        return match ($settings['apply_mode'] ?? 'all') {
            'none' => false,
            'only_selected' => false, // solo los marcados manualmente (ya cubierto arriba)
            'creator_vip' => $video->user?->isCreatorPlus() ?? false,
            default => true, // 'all'
        };
    }

    /**
     * Resuelve los metadatos de la pausa publicitaria para el reproductor Vidu.
     * Tiene en cuenta el viewer_mode para excluir espectadores VIP si corresponde.
     */
    public function resolveAdBreak(ViduVideo $video, ?User $currentUser = null): ?array
    {
        if (! $this->videoEligibleForAds($video)) {
            return null;
        }

        $settings = $this->settings();

        // Filtra según quién está viendo: si solo_no_vip, los Creator Plus no ven anuncios.
        $viewerMode = $settings['viewer_mode'] ?? 'everyone';

        if ($viewerMode === 'non_vip' && $currentUser?->isCreatorPlus()) {
            return null;
        }

        // Elige un creativo activo de forma aleatoria pero estable por video.
        $creatives = $this->activeCreatives();

        if ($creatives->isEmpty()) {
            return null;
        }

        $index = abs(crc32('vidu-ad-creative-'.$video->id)) % $creatives->count();
        $creative = $creatives->values()->get($index);

        return [
            'cue_at_seconds' => $this->computeCueSeconds($video),
            'creative' => [
                'id' => $creative->id,
                'video_url' => $creative->video_url,
                'duration_seconds' => $creative->duration_seconds,
            ],
        ];
    }

    /**
     * Calcula un punto de pausa pseudoaleatorio estable por video.
     */
    public function computeCueSeconds(ViduVideo $video): int
    {
        $settings = $this->settings();
        $duration = max(1, (int) $video->duration_seconds);
        $min = min($duration - 10, max(10, (int) $settings['trigger_min_seconds']));
        $max = min(
            $duration - 5,
            max($min + 5, (int) floor($duration * ((int) $settings['trigger_max_percent'] / 100))),
        );

        if ($max <= $min) {
            return max(1, (int) floor($duration / 2));
        }

        $hash = crc32('vidu-ad-cue-'.$video->id);

        return $min + ($hash % ($max - $min + 1));
    }

    /**
     * Resumen legible del modo de aplicación para la vista admin.
     */
    public function applyModeLabel(string $mode): string
    {
        return match ($mode) {
            'none' => 'Desactivado (solo excepciones manuales)',
            'only_selected' => 'Solo videos marcados manualmente',
            'creator_vip' => 'Solo videos de creadores VIP (Creator Plus)',
            default => 'Todos los videos elegibles',
        };
    }
}
