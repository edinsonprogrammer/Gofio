<?php

/**
 * Controlador admin de rangos de usuario: CRUD, asignación manual y desbloqueo de promoción automática.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RolRango;
use App\Models\User;
use App\Support\AdminPermissions;
use App\Services\AdminLogService;
use App\Services\PostPermissionService;
use App\Services\RankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RankAdminController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLogService,
        private readonly RankService $rankService,
        private readonly PostPermissionService $postPermissionService,
    ) {}

    /**
     * GET /admin/rangos — responde con la vista Inertia Admin/Ranks/Index y el listado de rangos con permisos.
     */
    public function index(): Response
    {
        // Renderiza el gestor de rangos y usuarios con rango bloqueado
        return Inertia::render('Admin/Ranks/Index', [
            'ranks' => RolRango::withCount('users')->orderBy('puntos_requeridos')->get()
                ->map(fn (RolRango $rango) => [
                    ...$rango->toArray(),
                    'admin_permissions' => $rango->admin_permissions ?? [],
                    'max_content_length' => $rango->post_permissions['max_content_length'] ?? null,
                    'max_posts_per_day' => $rango->post_permissions['max_posts_per_day'] ?? null,
                    'max_comments_per_day' => $rango->post_permissions['max_comments_per_day'] ?? null,
                    'can_send_tips' => $rango->post_permissions['can_send_tips'] ?? true,
                    'can_receive_tips' => $rango->post_permissions['can_receive_tips'] ?? true,
                    'default_max_content_length' => $this->postPermissionService->defaultsForRankName($rango->nombre)['max_content_length'],
                    'default_max_posts_per_day' => $this->postPermissionService->defaultsForRankName($rango->nombre)['max_posts_per_day'],
                    'default_max_comments_per_day' => $this->postPermissionService->defaultsForRankName($rango->nombre)['max_comments_per_day'],
                ]),
            // Pagina usuarios con rango bloqueado para evitar desbordamiento cuando crezca la lista
            'lockedUsers' => User::with('rango')
                ->where('rango_locked', true)
                ->orderBy('username')
                ->paginate(20, ['id', 'username', 'karma', 'rango_id'], 'locked_page')
                ->through(fn (User $u) => [
                    'id' => $u->id,
                    'username' => $u->username,
                    'karma' => $u->karma,
                    'rango_nombre' => $u->rango?->nombre,
                ]),
        ]);
    }

    /**
     * POST /admin/rangos — valida y crea un nuevo rango con permisos de publicación.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRank($request);
        $data['post_permissions'] = $this->extractPostPermissions($data);
        $data['admin_permissions'] = $this->extractAdminPermissions($data);

        $rank = RolRango::create([
            ...$data,
            'slug' => $data['slug'] ?? null,
        ]);

        $this->adminLogService->log(auth()->user(), 'create_rank', 'rank', $rank->id, $rank->nombre);

        return back()->with('success', "Rango «{$rank->nombre}» creado.");
    }

    /**
     * PUT /admin/rangos/{rank} — valida y actualiza el rango existente.
     */
    public function update(Request $request, RolRango $rank): RedirectResponse
    {
        $data = $this->validateRank($request, $rank->id);
        $data['post_permissions'] = $this->extractPostPermissions($data, $rank);
        $data['admin_permissions'] = $this->extractAdminPermissions($data, $rank);

        $rank->update($data);

        $this->adminLogService->log(auth()->user(), 'update_rank', 'rank', $rank->id, $rank->nombre);

        return back()->with('success', 'Rango actualizado.');
    }

    /**
     * Extrae los permisos de publicación del payload y los separa del resto de atributos del rango.
     */
    private function extractPostPermissions(array &$data, ?RolRango $existing = null): ?array
    {
        $keys = ['max_content_length', 'max_posts_per_day', 'max_comments_per_day', 'can_send_tips', 'can_receive_tips'];
        $overrides = is_array($existing?->post_permissions) ? $existing->post_permissions : [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            unset($data[$key]);

            if ($value === '' || $value === null) {
                unset($overrides[$key]);

                continue;
            }

            if (in_array($key, ['can_send_tips', 'can_receive_tips'], true)) {
                $overrides[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            $overrides[$key] = (int) $value;
        }

        return $overrides === [] ? null : $overrides;
    }

    /**
     * Normaliza los permisos de pestañas del panel admin para rangos staff.
     */
    private function extractAdminPermissions(array &$data, ?RolRango $existing = null): ?array
    {
        $isStaff = filter_var($data['is_staff'] ?? $existing?->is_staff ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $isStaff) {
            unset($data['admin_permissions']);

            return null;
        }

        $permissions = $data['admin_permissions'] ?? $existing?->admin_permissions ?? AdminPermissions::moderatorDefaults();
        unset($data['admin_permissions']);

        if (! is_array($permissions)) {
            $permissions = [];
        }

        $permissions = array_values(array_unique(array_intersect($permissions, AdminPermissions::allTabKeys())));

        return $permissions === [] ? AdminPermissions::moderatorDefaults() : $permissions;
    }

    /**
     * DELETE /admin/rangos/{rank} — elimina el rango reasignando usuarios al rango de respaldo disponible.
     */
    public function destroy(RolRango $rank): RedirectResponse
    {
        $fallback = RolRango::query()
            ->where('id', '!=', $rank->id)
            ->where('auto_promote', true)
            ->where('is_staff', false)
            ->orderBy('puntos_requeridos')
            ->first();

        $affectedUsers = $rank->users()->count();

        if ($affectedUsers > 0 && ! $fallback) {
            return back()->with('error', 'No se puede eliminar: no hay otro rango disponible para reasignar a sus usuarios.');
        }

        if ($fallback) {
            $rank->users()->update(['rango_id' => $fallback->id, 'rango_locked' => false]);
        }

        $name = $rank->nombre;
        $rank->delete();

        $this->adminLogService->log(auth()->user(), 'delete_rank', 'rank', null, $name);

        return back()->with('success', "Rango «{$name}» eliminado".($affectedUsers > 0 ? " ({$affectedUsers} usuario(s) reasignado(s))." : '.'));
    }

    /**
     * POST /admin/rangos/{rank}/asignar — asigna manualmente el rango a un usuario y bloquea la auto-promoción.
     */
    public function assign(Request $request, RolRango $rank): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $this->rankService->assignManually($user, $rank, auth()->user());

        $this->adminLogService->log(auth()->user(), 'assign_rank', 'rank', $rank->id, $user->username);

        return back()->with('success', "Rango «{$rank->nombre}» asignado a {$user->username} (bloqueado, no se auto-promocionará).");
    }

    /**
     * POST /admin/rangos/usuarios/{user}/desbloquear — permite que el rango del usuario vuelva a calcularse por karma.
     */
    public function unlock(User $user): RedirectResponse
    {
        $this->rankService->unlock($user);

        $this->adminLogService->log(auth()->user(), 'unlock_rank', 'rank', $user->rango_id, $user->username);

        return back()->with('success', "Se desbloqueó el rango de {$user->username}: volverá a calcularse por karma.");
    }

    /**
     * Valida los campos del formulario de creación o edición de un rango.
     */
    private function validateRank(Request $request, ?int $rankId = null): array
    {
        // Valida atributos del rango y permisos opcionales de publicación
        return $request->validate([
            'nombre' => ['required', 'string', 'max:40'],
            'slug' => ['nullable', 'string', 'max:60', 'alpha_dash', 'unique:roles_rangos,slug,'.($rankId ?? 'NULL')],
            'color' => ['required', 'string', 'max:7'],
            'icon' => ['required', 'string', 'max:191'],
            'puntos_requeridos' => ['required', 'integer', 'min:0'],
            'poder_voto' => ['required', 'integer', 'min:1', 'max:10'],
            'limite_voto_diario' => ['required', 'integer', 'min:1', 'max:100'],
            'max_content_length' => ['nullable', 'integer', 'min:100', 'max:20000'],
            'max_posts_per_day' => ['nullable', 'integer', 'min:1', 'max:500'],
            'max_comments_per_day' => ['nullable', 'integer', 'min:1', 'max:500'],
            'can_send_tips' => ['sometimes', 'boolean'],
            'can_receive_tips' => ['sometimes', 'boolean'],
            'is_staff' => ['boolean'],
            'auto_promote' => ['boolean'],
            'admin_permissions' => ['nullable', 'array'],
            'admin_permissions.*' => ['string', 'in:'.implode(',', AdminPermissions::allTabKeys())],
        ]);
    }
}
