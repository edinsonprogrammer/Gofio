<?php

/**
 * Controlador admin de medallas de logro: CRUD, asignación manual y revocación.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medal;
use App\Models\User;
use App\Services\AdminLogService;
use App\Services\MedalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MedalAdminController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLogService,
        private readonly MedalService $medalService,
    ) {}

    /**
     * GET /admin/medallas — responde con la vista Inertia Admin/Medals/Index y el catálogo de medallas.
     */
    public function index(): Response
    {
        // Renderiza el gestor de medallas
        return Inertia::render('Admin/Medals/Index', [
            'medals' => Medal::withCount('users')->orderBy('sort_order')->paginate(20),
        ]);
    }

    /**
     * POST /admin/medallas — valida y crea una nueva medalla con condición de obtención.
     */
    public function store(Request $request): RedirectResponse
    {
        // Valida título único, icono, color y condición de desbloqueo
        $data = $request->validate([
            'title' => ['required', 'string', 'max:40', 'unique:medals,title'],
            'description' => ['nullable', 'string', 'max:120'],
            'icon' => ['required', 'string', 'max:191'],
            'color' => ['required', 'string', 'max:7'],
            'condition_type' => ['required', 'in:karma,posts,comments,verified,manual'],
            'condition_value' => ['required', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Medal::create([
            ...$data,
            'description' => $data['description'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        MedalService::flushActiveMedalsCache();

        return back()->with('success', 'Medalla creada.');
    }

    /**
     * PUT /admin/medallas/{medal} — valida y actualiza la medalla existente.
     */
    public function update(Request $request, Medal $medal): RedirectResponse
    {
        // Valida campos editables excluyendo el título duplicado de otras medallas
        $data = $request->validate([
            'title' => ['required', 'string', 'max:40', 'unique:medals,title,'.$medal->id],
            'description' => ['nullable', 'string', 'max:120'],
            'icon' => ['required', 'string', 'max:191'],
            'color' => ['required', 'string', 'max:7'],
            'condition_type' => ['required', 'in:karma,posts,comments,verified,manual'],
            'condition_value' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $medal->update($data);

        MedalService::flushActiveMedalsCache();

        return back()->with('success', 'Medalla actualizada.');
    }

    /**
     * POST /admin/medallas/{medal}/asignar — otorga manualmente la medalla a un usuario.
     */
    public function assign(Request $request, Medal $medal): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::findOrFail($data['user_id']);

        if (! $this->medalService->grantManual($user, $medal, auth()->user(), $data['note'] ?? null)) {
            return back()->with('error', 'El usuario ya tiene esta medalla.');
        }

        $this->adminLogService->log(auth()->user(), 'grant_medal', 'medal', $medal->id, $user->username);

        return back()->with('success', 'Medalla asignada.');
    }

    /**
     * DELETE /admin/medallas/{medal}/usuarios/{user} — revoca la medalla de un usuario específico.
     */
    public function revoke(Medal $medal, User $user): RedirectResponse
    {
        $this->medalService->revoke($user, $medal);

        return back()->with('success', 'Medalla revocada.');
    }
}
