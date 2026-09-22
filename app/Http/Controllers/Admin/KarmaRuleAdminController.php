<?php

namespace App\Http\Controllers\Admin;

/**
 * Controlador CRUD para las reglas de karma desde el panel de administración.
 * Las reglas del sistema (is_system=true) solo permiten editar parámetros, no eliminar.
 */

use App\Http\Controllers\Controller;
use App\Models\KarmaRule;
use App\Services\KarmaRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KarmaRuleAdminController extends Controller
{
    /**
     * Inyecta el servicio de karma para invalidar caché tras cambios.
     */
    public function __construct(
        private readonly KarmaRuleService $karmaRuleService,
    ) {}

    /**
     * GET /admin/karma — lista paginada de reglas ordenadas por sort_order.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/KarmaRules/Index', [
            'rules'        => KarmaRule::orderBy('sort_order')->orderBy('id')->paginate(25),
            'triggerTypes' => KarmaRule::triggerTypes(),
            'dedupModes'   => KarmaRule::dedupModes(),
        ]);
    }

    /**
     * POST /admin/karma — crea una nueva regla personalizada validando unicidad de key
     * y que el trigger_type sea uno de los soportados por el motor.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key'          => ['required', 'string', 'max:80', 'unique:karma_rules,key', 'regex:/^[a-z0-9_]+$/'],
            'trigger_type' => ['required', 'string', 'in:' . implode(',', array_keys(KarmaRule::triggerTypes()))],
            'label'        => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:255'],
            'karma_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'enabled'      => ['boolean'],
            'dedup_mode'   => ['required', 'in:once,per_reference,unlimited'],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            // Condiciones específicas por trigger
            'conditions.min_reactions' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'conditions.min_likes'     => ['nullable', 'integer', 'min:1', 'max:100000'],
            'conditions.every'         => ['nullable', 'integer', 'min:1', 'max:100000'],
            'conditions.max_per_day'   => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        // Las reglas creadas desde el admin nunca son de sistema
        $data['is_system'] = false;
        $data['conditions'] = $this->buildConditions($request);

        KarmaRule::create($data);

        // Invalida la caché para que la nueva regla empiece a aplicarse
        $this->karmaRuleService->flushCache();

        return back()->with('success', 'Regla de karma creada correctamente.');
    }

    /**
     * PUT /admin/karma/{rule} — actualiza los parámetros de una regla.
     * Las reglas del sistema no permiten cambiar key, trigger_type ni is_system.
     */
    public function update(Request $request, KarmaRule $rule): RedirectResponse
    {
        $data = $request->validate([
            'label'        => ['required', 'string', 'max:120'],
            'description'  => ['nullable', 'string', 'max:255'],
            'karma_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'enabled'      => ['boolean'],
            'dedup_mode'   => ['required', 'in:once,per_reference,unlimited'],
            'sort_order'   => ['nullable', 'integer', 'min:0'],
            // Condiciones específicas
            'conditions.min_reactions' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'conditions.min_likes'     => ['nullable', 'integer', 'min:1', 'max:100000'],
            'conditions.every'         => ['nullable', 'integer', 'min:1', 'max:100000'],
            'conditions.max_per_day'   => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        // Las reglas del sistema no pueden cambiar trigger_type ni key
        if (! $rule->is_system) {
            $triggerValidated = $request->validate([
                'trigger_type' => ['required', 'string', 'in:' . implode(',', array_keys(KarmaRule::triggerTypes()))],
            ]);
            $data['trigger_type'] = $triggerValidated['trigger_type'];
        }

        $data['conditions'] = $this->buildConditions($request);

        $rule->update($data);
        $this->karmaRuleService->flushCache();

        return back()->with('success', 'Regla "' . $rule->label . '" actualizada.');
    }

    /**
     * DELETE /admin/karma/{rule} — elimina una regla personalizada.
     * Protege las reglas del sistema contra eliminación.
     */
    public function destroy(KarmaRule $rule): RedirectResponse
    {
        if ($rule->is_system) {
            return back()->with('error', 'Las reglas del sistema no se pueden eliminar. Puedes desactivarlas.');
        }

        $label = $rule->label;
        $rule->delete();
        $this->karmaRuleService->flushCache();

        return back()->with('success', 'Regla "' . $label . '" eliminada.');
    }

    /**
     * Construye el array de conditions a partir del request, excluyendo nulos.
     */
    private function buildConditions(Request $request): ?array
    {
        $raw = $request->input('conditions', []);

        if (! is_array($raw)) {
            return null;
        }

        // Filtra solo los valores no nulos y no vacíos
        $conditions = collect($raw)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        return empty($conditions) ? null : $conditions;
    }
}
