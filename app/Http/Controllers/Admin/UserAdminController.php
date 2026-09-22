<?php

/**
 * Controlador admin de usuarios: listado, búsqueda, suspensión y gestión de verificación y Creator Plus.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserAdminRequest;
use App\Models\RolRango;
use App\Models\User;
use App\Services\UserAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserAdminController extends Controller
{
    public function __construct(
        private readonly UserAdminService $userAdminService,
    ) {}

    /**
     * GET /admin/usuarios — responde con la vista Inertia Admin/Users/Index y el listado paginado.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('q');

        $users = User::query()
            ->with('rango:id,nombre')
            ->when($search, fn ($q) => $q->where('username', 'like', "%{$search}%")
                ->orWhere('nick', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Renderiza la tabla de gestión de usuarios
        return Inertia::render('Admin/Users/Index', [
            'users' => $users->through(fn (User $user) => [
                ...$user->toArray(),
                'display_verification' => $user->displayVerificationTipo(),
                'is_creator_plus' => $user->isCreatorPlus(),
                'has_identity_verification' => $user->hasIdentityVerification(),
                'creator_plus_expires_at' => $user->creator_plus_expires_at?->toISOString(),
            ]),
            'filters' => ['q' => $search],
            'rangos' => RolRango::orderBy('id')->get(['id', 'nombre']),
        ]);
    }

    /**
     * GET /admin/usuarios/buscar?q= — responde con JSON de hasta 10 usuarios coincidentes (autocompletado).
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));

        if (mb_strlen($query) < 1) {
            return response()->json(['data' => []]);
        }

        $like = '%'.ltrim($query, '@').'%';

        $users = User::query()
            ->where(function ($q) use ($like) {
                $q->where('username', 'like', $like)->orWhere('nick', 'like', $like);
            })
            ->orderBy('username')
            ->limit(10)
            ->get(['id', 'username', 'nick', 'avatar_url', 'karma']);

        return response()->json(['data' => $users]);
    }

    /**
     * POST /admin/usuarios/{user}/banear — valida motivo y duración; suspende al usuario y redirige de vuelta.
     */
    public function ban(Request $request, User $user): RedirectResponse
    {
        // Valida motivo obligatorio y duración opcional en días
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $this->userAdminService->banUser(
            auth()->user(),
            $user,
            $request->input('reason'),
            $request->input('days'),
        );

        return back()->with('success', "@{$user->username} suspendido.");
    }

    /**
     * POST /admin/usuarios/{user}/desbanear — reactiva al usuario suspendido y redirige de vuelta.
     */
    public function unban(User $user): RedirectResponse
    {
        $this->userAdminService->unbanUser(auth()->user(), $user);

        return back()->with('success', "@{$user->username} reactivado.");
    }

    /**
     * PUT /admin/usuarios/{user} — valida y actualiza karma, saldo, rango y permisos del usuario.
     */
    public function update(UpdateUserAdminRequest $request, User $user): RedirectResponse
    {
        // La autorización y reglas por rol están en UpdateUserAdminRequest; el servicio bloquea escalada de privilegios.
        $this->userAdminService->updateUser($request->user(), $user, $request->validated());

        return back()->with('success', "Usuario @{$user->username} actualizado.");
    }

    /**
     * POST /admin/usuarios/{user}/quitar-verificado — revoca la verificación de identidad del usuario.
     */
    public function revokeVerification(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->userAdminService->revokeVerification(auth()->user(), $user, $data['reason'] ?? null);

        return back()->with('success', "Verificación de @{$user->username} revocada.");
    }

    /**
     * POST /admin/usuarios/{user}/quitar-creator-plus — revoca la suscripción Creator Plus del usuario.
     */
    public function revokeCreatorPlus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->userAdminService->revokeCreatorPlus(auth()->user(), $user, $data['reason'] ?? null);

        return back()->with('success', "Creator Plus de @{$user->username} retirado.");
    }
}
