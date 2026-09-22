<?php

/**
 * Panel admin de publicidad Vidu: banners laterales, creativos de video con rotación
 * aleatoria, configuración global y override por video.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ViduAdBanner;
use App\Models\ViduAdCreative;
use App\Models\ViduVideo;
use App\Services\AdminLogService;
use App\Services\ViduAdService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ViduAdAdminController extends Controller
{
    public function __construct(
        private readonly ViduAdService $viduAdService,
        private readonly AdminLogService $adminLogService,
    ) {}

    /**
     * GET /admin/vidu-publicidad — vista principal de publicidad Vidu.
     */
    public function index(Request $request): Response
    {
        $settings = $this->viduAdService->settings();

        $videos = ViduVideo::query()
            ->with(['user:id,username'])
            ->where('status', '!=', 'banned')
            ->when($request->input('q'), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('username', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('ads'), function ($query, $filter) {
                match ($filter) {
                    'with' => $query->where('ads_override', true),
                    'without' => $query->where('ads_override', false),
                    'default' => $query->whereNull('ads_override'),
                    default => null,
                };
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/ViduAds/Index', [
            'settings' => $settings,
            'creatives' => ViduAdCreative::query()->orderByDesc('created_at')->get(),
            'activeCreativesCount' => ViduAdCreative::query()->where('is_active', true)->count(),
            'banners' => ViduAdBanner::query()->orderByDesc('created_at')->get(),
            'activeBannersCount' => ViduAdBanner::query()->where('is_active', true)->count(),
            'videos' => $videos,
            'filters' => [
                'q' => $request->input('q'),
                'ads' => $request->input('ads'),
            ],
            'stats' => [
                'total_videos' => ViduVideo::query()->where('status', '!=', 'banned')->count(),
                'forced_on' => ViduVideo::query()->where('ads_override', true)->count(),
                'forced_off' => ViduVideo::query()->where('ads_override', false)->count(),
            ],
        ]);
    }

    /**
     * PUT /admin/vidu-publicidad/configuracion — guarda ajustes globales.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'apply_mode' => ['required', 'in:all,creator_vip,only_selected,none'],
            'viewer_mode' => ['required', 'in:everyone,non_vip'],
            'min_video_seconds' => ['required', 'integer', 'min:60', 'max:600'],
            'trigger_min_seconds' => ['required', 'integer', 'min:10', 'max:300'],
            'trigger_max_percent' => ['required', 'integer', 'min:30', 'max:90'],
            'sidebar_banners_enabled' => ['sometimes', 'boolean'],
        ]);

        $this->viduAdService->updateSettings($data);
        $this->adminLogService->log(auth()->user(), 'update_vidu_ads_settings', 'vidu_ad', null, $data['apply_mode']);

        return back()->with('success', 'Configuración de publicidad actualizada.');
    }

    // -----------------------------------------------------------------------
    // Creativos de video publicitario
    // -----------------------------------------------------------------------

    /**
     * POST /admin/vidu-publicidad/creativos — sube un nuevo video publicitario (inactivo por defecto).
     */
    public function uploadCreative(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:51200'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        $creative = $this->viduAdService->uploadCreative(
            $request->file('video'),
            $data['name'],
            (int) $data['duration_seconds'],
        );

        $this->adminLogService->log(auth()->user(), 'upload_vidu_ad_creative', 'vidu_ad', $creative->id, $creative->name);

        return back()->with('success', 'Video publicitario subido. Actívalo cuando esté listo.');
    }

    /**
     * PUT /admin/vidu-publicidad/creativos/{creative} — activa o desactiva un creativo.
     */
    public function toggleCreative(ViduAdCreative $creative): RedirectResponse
    {
        $newState = $this->viduAdService->toggleCreative($creative);
        $label = $newState ? 'activado' : 'desactivado';

        $this->adminLogService->log(auth()->user(), "creative_{$label}", 'vidu_ad', $creative->id, $creative->name);

        return back()->with('success', "Anuncio \"{$creative->name}\" {$label}.");
    }

    /**
     * DELETE /admin/vidu-publicidad/creativos/{creative} — elimina un creativo publicitario.
     */
    public function destroyCreative(ViduAdCreative $creative): RedirectResponse
    {
        $name = $creative->name;
        $id = $creative->id;
        $this->viduAdService->deleteCreative($creative);
        $this->adminLogService->log(auth()->user(), 'delete_vidu_ad_creative', 'vidu_ad', $id, $name);

        return back()->with('success', 'Video publicitario eliminado.');
    }

    // -----------------------------------------------------------------------
    // Banners laterales
    // -----------------------------------------------------------------------

    /**
     * POST /admin/vidu-publicidad/banners — sube un banner vertical lateral.
     */
    public function uploadBanner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'image' => ['required', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:8192'],
            'link_url' => ['nullable', 'url', 'max:500'],
        ]);

        $banner = $this->viduAdService->uploadBanner(
            $request->file('image'),
            $data['name'],
            $data['link_url'] ?? null,
        );

        $this->adminLogService->log(auth()->user(), 'upload_vidu_ad_banner', 'vidu_ad', $banner->id, $banner->name);

        return back()->with('success', 'Banner lateral subido y activado.');
    }

    /**
     * PUT /admin/vidu-publicidad/banners/{banner} — activa o desactiva un banner.
     */
    public function updateBanner(Request $request, ViduAdBanner $banner): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->viduAdService->setBannerActive($banner, (bool) $data['is_active']);

        return back()->with('success', 'Estado del banner actualizado.');
    }

    /**
     * DELETE /admin/vidu-publicidad/banners/{banner} — elimina un banner lateral.
     */
    public function destroyBanner(ViduAdBanner $banner): RedirectResponse
    {
        $name = $banner->name;
        $id = $banner->id;
        $this->viduAdService->deleteBanner($banner);
        $this->adminLogService->log(auth()->user(), 'delete_vidu_ad_banner', 'vidu_ad', $id, $name);

        return back()->with('success', 'Banner lateral eliminado.');
    }

    // -----------------------------------------------------------------------
    // Overrides por video
    // -----------------------------------------------------------------------

    /**
     * PUT /admin/vidu-publicidad/videos/{video} — override de publicidad en un video.
     */
    public function updateVideo(Request $request, ViduVideo $video): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:default,force_on,force_off'],
        ]);

        $override = match ($data['mode']) {
            'force_on' => true,
            'force_off' => false,
            default => null,
        };

        $this->viduAdService->setVideoOverride($video, $override);

        return back()->with('success', 'Regla de publicidad actualizada.');
    }

    /**
     * POST /admin/vidu-publicidad/videos/masivo — aplica reglas masivas de publicidad.
     */
    public function bulkVideos(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:enable_all,disable_all,reset_all'],
        ]);

        $count = $this->viduAdService->bulkUpdateVideos($data['action']);
        $this->adminLogService->log(auth()->user(), 'bulk_vidu_ads_videos', 'vidu_ad', null, $data['action']);

        return back()->with('success', "Regla aplicada a {$count} videos.");
    }
}
