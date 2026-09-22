<?php

/**
 * Controlador admin de la economía de propinas: analíticas, comisión de plataforma y depósitos manuales.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\SiteSettingsRepository;
use App\Services\AdminLogService;
use App\Services\WalletAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TipAdminController extends Controller
{
    public function __construct(
        private readonly WalletAdminService $walletAdminService,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly AdminLogService $adminLogService,
    ) {}

    /**
     * GET /admin/propinas — responde con la vista Inertia Admin/Tips/Index y las analíticas de propinas.
     */
    public function index(): Response
    {
        // Renderiza el panel de economía de propinas con movimientos paginados aparte
        return Inertia::render('Admin/Tips/Index', [
            'analytics' => $this->walletAdminService->analytics(),
            'transactions' => $this->walletAdminService->recentTransactionsPaginated(25),
        ]);
    }

    /**
     * PUT /admin/propinas/comision — valida y actualiza el porcentaje de comisión de la plataforma.
     */
    public function updateFee(Request $request): RedirectResponse
    {
        // Valida el porcentaje de comisión entre 0 y 50
        $data = $request->validate([
            'platform_fee_percent' => ['required', 'numeric', 'min:0', 'max:50'],
        ]);

        $this->siteSettingsRepository->set('platform_fee_percent', (float) $data['platform_fee_percent']);
        $this->adminLogService->log(auth()->user(), 'update_tip_fee', 'settings', null, 'Comisión propinas: '.$data['platform_fee_percent'].'%');

        return back()->with('success', 'Comisión de plataforma actualizada.');
    }

    /**
     * POST /admin/propinas/depositos — valida y acredita monedas manualmente a un usuario.
     */
    public function registerDeposit(Request $request): RedirectResponse
    {
        // Valida usuario destino y monto del depósito
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $this->walletAdminService->registerDeposit($user, (float) $data['amount'], auth()->user());

        $this->adminLogService->log(
            auth()->user(),
            'register_coin_deposit',
            'wallet',
            $user->id,
            "Depósito de {$data['amount']} monedas a {$user->username}",
        );

        return back()->with('success', "Se acreditaron {$data['amount']} monedas a {$user->username}.");
    }
}
