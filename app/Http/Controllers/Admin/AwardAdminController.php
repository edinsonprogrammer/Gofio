<?php

/**
 * Controlador admin de premios y categorías de premios: CRUD, otorgamiento y revocación.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\AwardCategory;
use App\Models\User;
use App\Services\AdminLogService;
use App\Services\AwardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AwardAdminController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLogService,
        private readonly AwardService $awardService,
    ) {}

    /**
     * GET /admin/premios — responde con la vista Inertia Admin/Awards/Index y las categorías con sus premios.
     */
    public function index(): Response
    {
        // Renderiza el gestor de premios agrupados por categoría
        return Inertia::render('Admin/Awards/Index', [
            'categories' => AwardCategory::with('awards')->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * POST /admin/premios/categorias — valida y crea una nueva categoría de premios.
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        // Valida nombre único y orden de visualización
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:award_categories,name'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        AwardCategory::create([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Categoría de premio creada.');
    }

    /**
     * POST /admin/premios — valida y crea un nuevo premio dentro de una categoría.
     */
    public function storeAward(Request $request): RedirectResponse
    {
        // Valida categoría, nombre, icono y color del premio
        $data = $request->validate([
            'award_category_id' => ['required', 'exists:award_categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:191'],
            'color' => ['required', 'string', 'max:7'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Award::create([
            ...$data,
            'description' => $data['description'] ?? '',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Premio creado.');
    }

    /**
     * PUT /admin/premios/{award} — valida y actualiza el premio existente.
     */
    public function updateAward(Request $request, Award $award): RedirectResponse
    {
        // Valida campos editables del premio
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:191'],
            'color' => ['required', 'string', 'max:7'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $award->update($data);

        return back()->with('success', 'Premio actualizado.');
    }

    /**
     * POST /admin/premios/{award}/otorgar — otorga manualmente el premio a un usuario.
     */
    public function grant(Request $request, Award $award): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::findOrFail($data['user_id']);

        if (! $this->awardService->grant($user, $award, auth()->user(), $data['note'] ?? null)) {
            return back()->with('error', 'No se pudo otorgar el premio (ya lo tiene o está inactivo).');
        }

        $this->adminLogService->log(auth()->user(), 'grant_award', 'award', $award->id, $user->username);

        return back()->with('success', 'Premio otorgado.');
    }

    /**
     * DELETE /admin/premios/{award}/usuarios/{user} — revoca el premio de un usuario específico.
     */
    public function revoke(Award $award, User $user): RedirectResponse
    {
        $this->awardService->revoke($user, $award);

        return back()->with('success', 'Premio revocado.');
    }
}
