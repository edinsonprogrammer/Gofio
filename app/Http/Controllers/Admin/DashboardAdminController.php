<?php

/**
 * Controlador del panel de administración: dashboard con métricas y resumen general del sitio.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminPermissions;
use App\Services\AdminDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardAdminController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService,
    ) {}

    /**
     * GET /admin — responde con la vista Inertia Admin/Dashboard y las métricas del sitio.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Staff sin acceso al panel redirige a su primera pestaña permitida
        if ($user && ! $user->isAdmin() && ! $user->canAccessAdminTab('dashboard')) {
            return redirect(AdminPermissions::defaultHrefForUser($user));
        }

        $page = max(1, (int) $request->input('page', 1));

        return Inertia::render('Admin/Dashboard', $this->dashboardService->getDashboardData($page));
    }
}
