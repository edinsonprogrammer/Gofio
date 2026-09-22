<?php

namespace App\Services;

/**
 * Agrega métricas y datos recientes para el panel principal de administración.
 */

use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\ModerationAlert;
use App\Models\Post;
use App\Models\SupportTicket;
use App\Models\User;
use App\Repositories\AdminLogRepository;
use App\Repositories\SiteSettingsRepository;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Inyecta repositorios de logs y configuración del sitio.
     */
    public function __construct(
        private readonly AdminLogRepository $adminLogRepository,
        private readonly SiteSettingsRepository $siteSettingsRepository,
    ) {}

    /**
     * Devuelve contadores globales, historial paginado de admin y flags de configuración clave.
     */
    public function getDashboardData(int $page = 1): array
    {
        return [
            'stats' => [
                'users_total' => User::count(),
                'users_banned' => User::where('is_banned', true)->count(),
                'posts_total' => Post::count(),
                'posts_banned' => Post::where('status', 'banned')->count(),
                'posts_featured' => Post::where('is_featured', true)->count(),
                'comments_total' => Comment::count(),
                'reports_pending' => ContentReport::where('status', 'pending')->count(),
                'alerts_open' => ModerationAlert::where('status', 'open')->count(),
                'verifications_pending' => DB::table('identity_verification_requests')->where('status', 'pending')->count(),
                'tickets_open' => SupportTicket::whereIn('status', ['open', 'in_progress'])->count(),
            ],
            'recent_logs' => $this->adminLogRepository
                ->paginate(10, $page)
                ->through(fn ($log) => [
                    'id' => $log->id,
                    'admin' => $log->admin?->username,
                    'staff_rank' => $log->admin?->rango?->nombre,
                    'action' => $log->action,
                    'target_type' => $log->target_type,
                    'target_id' => $log->target_id,
                    'reason' => $log->reason,
                    'created_at' => $log->created_at?->toISOString(),
                ]),
            'settings' => [
                'offline_mode' => (bool) $this->siteSettingsRepository->get('offline_mode'),
                'registration_enabled' => (bool) $this->siteSettingsRepository->get('registration_enabled'),
            ],
        ];
    }
}
