<?php

/**
 * Middleware de Inertia que comparte datos globales con todas las vistas del frontend.
 * Inyecta usuario autenticado, tema, permisos, flash y configuración de la app.
 */

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ReportReasons;
use App\Support\AdminPermissions;
use App\Services\GiphyService;
use App\Services\IconPackService;
use App\Services\PostPermissionService;
use App\Services\RankService;
use App\Services\UserActivityLimitService;
use App\Services\SeoService;
use App\Services\ThemeService;
use App\Services\TipPermissionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * GET cualquier página Inertia — responde con la versión del asset para invalidar caché del cliente.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * GET cualquier página Inertia — responde con el payload compartido (auth, tema, features, flash).
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            $user->loadMissing('rango');
        }
        $features = $request->attributes->get('gofio.features', [
            'ads_enabled' => true,
            'creator_plus' => false,
            'exclusive_theme' => false,
            'karma_multiplier' => 1,
        ]);

        $themeService = app(ThemeService::class);
        $rankService = app(RankService::class);
        $postPermissionService = app(PostPermissionService::class);
        $activityLimitService = app(UserActivityLimitService::class);
        $iconPackService = app(IconPackService::class);
        $tipPermissionService = app(TipPermissionService::class);
        $seoService = app(SeoService::class);

        return [
            ...parent::share($request),
            'appName' => $seoService->sharePayload()['site_title'] ?? config('app.name'),
            'site' => fn () => $seoService->sharePayload(),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'karma' => $user->karma,
                    'balance_monedas' => $user->balance_monedas,
                    'tipo_verificacion' => $user->tipo_verificacion,
                    'is_admin' => $user->isAdmin(),
                    'is_staff' => $user->isAdmin() || $user->hasStaffRank(),
                    'admin_tabs' => AdminPermissions::tabsForUser($user),
                    'is_creator_plus' => $user->isCreatorPlus(),
                    'can_customize_appearance' => $user->canCustomizeAppearance(),
                    'creator_plus_expires_at' => $user->creator_plus_expires_at?->toISOString(),
                    'avatar_url' => $user->avatar_url,
                    'banner_url' => $user->banner_url,
                    'rango' => $rankService->formatRango($user->rango),
                ] : null,
            ],
            'features' => $features,
            'postComposer' => fn () => $user ? $postPermissionService->forUser($user) : null,
            'commentComposer' => fn () => $user ? $this->commentComposerPayload($user, $activityLimitService, $postPermissionService) : null,
            'tipSettings' => fn () => [
                'enabled' => $tipPermissionService->tipsGloballyEnabled(),
                'platform_fee_percent' => $tipPermissionService->platformFeePercent(),
                'can_send' => $user ? $tipPermissionService->canSendTips($user) : false,
            ],
            'theme' => fn () => $themeService->sharePayload($user),
            'iconPack' => fn () => $iconPackService->activeMappingsForUser($user),
            'giphy' => fn () => [
                'configured' => app(GiphyService::class)->isConfigured(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'sessionWelcome' => fn () => $this->sessionWelcomePayload($request),
            'reportReasons' => ReportReasons::all(),
            'adminNav' => fn () => $user && ($user->isAdmin() || $user->hasStaffRank())
                ? AdminPermissions::navForUser($user)
                : [],
            'adminTabOptions' => fn () => AdminPermissions::groupedOptionsForForm(),
            'csrf_token' => fn () => csrf_token(),
        ];
    }

    /**
     * Arma el payload compartido del compositor de comentarios con cuota diaria y uso actual.
     */
    private function commentComposerPayload(
        User $user,
        UserActivityLimitService $activityLimitService,
        PostPermissionService $postPermissionService,
    ): array {
        $permissions = $postPermissionService->forUser($user);
        $maxComments = (int) $permissions['max_comments_per_day'];
        $commentsToday = $activityLimitService->countCommentsToday($user);

        return [
            'max_comments_per_day' => $maxComments,
            'comments_today' => $commentsToday,
            'can_create' => $commentsToday < $maxComments,
        ];
    }

    /**
     * Calcula el estado del overlay de bienvenida post-login para el frontend.
     */
    private function sessionWelcomePayload(Request $request): array
    {
        if (! $request->user()) {
            return ['active' => false, 'remaining_ms' => 0];
        }

        $startedAt = $request->session()->get('session_welcome_at');

        if (! is_numeric($startedAt)) {
            return ['active' => false, 'remaining_ms' => 0];
        }

        $elapsed = now()->timestamp - (int) $startedAt;
        $durationSeconds = 120;

        if ($elapsed >= $durationSeconds) {
            return ['active' => false, 'remaining_ms' => 0];
        }

        return [
            'active' => true,
            'remaining_ms' => ($durationSeconds - $elapsed) * 1000,
        ];
    }
}
