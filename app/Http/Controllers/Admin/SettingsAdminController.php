<?php

/**
 * Controlador admin de configuración global: ajustes del sitio, karma, propinas y registro de actividad.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\AdminLogRepository;
use App\Repositories\SiteSettingsRepository;
use App\Services\AdminLogService;
use App\Services\SiteLogoService;
use App\Support\SiteSettingsDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsAdminController extends Controller
{
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly AdminLogService $adminLogService,
        private readonly AdminLogRepository $adminLogRepository,
        private readonly SiteLogoService $siteLogoService,
    ) {}

    /**
     * GET /admin/configuracion — responde con la vista Inertia Admin/Settings/Index y los ajustes actuales.
     */
    public function index(): Response
    {
        // Renderiza el formulario de configuración global del sitio
        return Inertia::render('Admin/Settings/Index', [
            'settings' => $this->siteSettingsRepository->all(),
            'defaults' => SiteSettingsDefaults::all(),
            'logs' => $this->adminLogRepository->paginate(20),
        ]);
    }

    /**
     * PUT /admin/configuracion — valida y persiste los ajustes; redirige de vuelta con mensaje de éxito.
     */
    public function update(Request $request): RedirectResponse
    {
        // Valida todos los campos de configuración del sitio
        $data = $request->validate([
            'site_title' => ['required', 'string', 'max:60'],
            'site_slogan' => ['nullable', 'string', 'max:120'],
            'site_email' => ['required', 'email'],
            'site_copyright' => ['nullable', 'string', 'max:255'],
            'seo_meta_title' => ['nullable', 'string', 'max:120'],
            'seo_meta_description' => ['nullable', 'string', 'max:500'],
            'seo_meta_keywords' => ['nullable', 'string', 'max:500'],
            'seo_og_image' => ['nullable', 'string', 'max:500'],
            'seo_twitter_handle' => ['nullable', 'string', 'max:80'],
            'seo_google_site_verification' => ['nullable', 'string', 'max:120'],
            'seo_bing_site_verification' => ['nullable', 'string', 'max:120'],
            'seo_indexnow_enabled' => ['sometimes', 'boolean'],
            'seo_robots_extra' => ['nullable', 'string', 'max:2000'],
            'seo_organization_name' => ['nullable', 'string', 'max:120'],
            'seo_organization_country' => ['nullable', 'string', 'max:8'],
            'registration_enabled' => ['sometimes', 'boolean'],
            'offline_mode' => ['sometimes', 'boolean'],
            'offline_message' => ['nullable', 'string', 'max:255'],
            'welcome_message' => ['nullable', 'string', 'max:500'],
            'max_posts_per_day' => ['integer', 'min:1', 'max:500'],
            'max_comments_per_day' => ['integer', 'min:1', 'max:500'],
            'max_votes_per_day' => ['integer', 'min:1', 'max:500'],
            'featured_votes_threshold' => ['integer', 'min:1'],
            'allow_tips' => ['sometimes', 'boolean'],
            'allow_uploads' => ['sometimes', 'boolean'],
            'platform_fee_percent' => ['numeric', 'min:0', 'max:50'],
            'default_rango_id' => ['integer', 'min:1'],
        ]);

        $data['registration_enabled'] = $request->boolean('registration_enabled');
        $data['offline_mode'] = $request->boolean('offline_mode');
        $data['allow_tips'] = $request->boolean('allow_tips');
        $data['allow_uploads'] = $request->boolean('allow_uploads');
        $data['seo_indexnow_enabled'] = $request->boolean('seo_indexnow_enabled');

        // Las reglas de karma se persisten en la tabla karma_rules, no en site_settings
        unset($data['karma_rules']);

        $this->siteSettingsRepository->setMany($data);
        $this->adminLogService->log(auth()->user(), 'update_settings', 'settings', null, 'Configuración general');

        return back()->with('success', 'Configuración guardada.');
    }

    /**
     * POST /admin/configuracion/logo — sube y redimensiona el logo del sitio.
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,bmp,svg,ico', 'max:8192'],
        ]);

        try {
            $url = $this->siteLogoService->store($request->file('logo'));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'No se pudo procesar el logo. Prueba con PNG, JPG, WebP o SVG.');
        }

        $this->siteSettingsRepository->set('site_logo_url', $url);
        $this->adminLogService->log(auth()->user(), 'update_settings', 'settings', null, 'Logo del sitio actualizado');

        return back()->with('success', 'Logo actualizado correctamente.');
    }

    /**
     * DELETE /admin/configuracion/logo — elimina el logo y vuelve al wordmark.
     */
    public function removeLogo(): RedirectResponse
    {
        $this->siteLogoService->deleteStoredFiles();
        $this->siteSettingsRepository->set('site_logo_url', '');
        $this->adminLogService->log(auth()->user(), 'update_settings', 'settings', null, 'Logo del sitio eliminado');

        return back()->with('success', 'Logo eliminado. Se muestra el nombre del sitio.');
    }
}
