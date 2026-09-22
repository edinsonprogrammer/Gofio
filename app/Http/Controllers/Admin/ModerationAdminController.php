<?php

/**
 * Controlador admin de moderación: gestión de denuncias de contenido y alertas automáticas.
 */

namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\ContentReport;

use App\Models\ModerationAlert;

use App\Services\ReportAdminService;

use App\Services\ReportFormatterService;

use App\Support\ReportReasons;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Inertia\Inertia;

use Inertia\Response;



class ModerationAdminController extends Controller

{

    public function __construct(

        private readonly ReportAdminService $reportAdminService,

        private readonly ReportFormatterService $reportFormatterService,

    ) {}



    /**
     * GET /admin/moderacion — responde con la vista Inertia Admin/Moderation/Index y denuncias pendientes.
     */
    public function index(): Response

    {

        $reports = ContentReport::query()

            ->with(['reporter:id,username', 'reportable'])

            ->where('status', 'pending')

            ->latest()

            ->paginate(15);



        // Renderiza el panel de moderación con denuncias y alertas abiertas
        return Inertia::render('Admin/Moderation/Index', [

            'reports' => $reports->through(fn (ContentReport $report) => $this->reportFormatterService->formatForAdmin($report)),

            'alerts' => ModerationAlert::query()

                ->with(['user:id,username', 'relatedUser:id,username'])

                ->where('status', 'open')

                ->latest('created_at')

                // Usa query key distinto para no colisionar con la paginación de denuncias
                ->paginate(15, ['*'], 'alerts_page'),

            'reportReasons' => ReportReasons::all(),

        ]);

    }



    /**
     * POST /admin/moderacion/denuncias/{report} — marca la denuncia como revisada o descartada.
     */
    public function resolveReport(Request $request, ContentReport $report): RedirectResponse

    {

        // Valida el nuevo estado y notas opcionales del moderador
        $request->validate([

            'status' => ['required', 'in:reviewed,dismissed'],

            'admin_notes' => ['nullable', 'string', 'max:500'],

        ]);



        $this->reportAdminService->resolveReport(

            auth()->user(),

            $report,

            $request->input('status'),

            $request->input('admin_notes'),

        );



        return back()->with('success', 'Denuncia procesada.');

    }



    /**
     * POST /admin/moderacion/denuncias/{report}/accion — aplica una acción moderadora sobre el contenido denunciado.
     */
    public function applyAction(Request $request, ContentReport $report): RedirectResponse

    {

        // Valida la acción, notas y duración de suspensión opcional
        $data = $request->validate([

            'action' => ['required', 'in:dismiss,reviewed,delete_content,suspend_author'],

            'admin_notes' => ['nullable', 'string', 'max:500'],

            'suspend_days' => ['nullable', 'integer', 'min:1', 'max:3650'],

        ]);



        $this->reportAdminService->takeAction(

            auth()->user(),

            $report,

            $data['action'],

            $data['admin_notes'] ?? null,

            isset($data['suspend_days']) ? (int) $data['suspend_days'] : null,

        );



        $messages = [

            'dismiss' => 'Denuncia descartada.',

            'reviewed' => 'Denuncia marcada como revisada.',

            'delete_content' => 'Contenido eliminado y denuncia cerrada.',

            'suspend_author' => 'Autor suspendido y denuncia cerrada.',

        ];



        return back()->with('success', $messages[$data['action']] ?? 'Acción aplicada.');

    }



    /**
     * POST /admin/moderacion/denuncias/{report}/editar — edita el contenido denunciado y cierra la denuncia.
     */
    public function editContent(Request $request, ContentReport $report): RedirectResponse

    {

        // Valida título, contenido y notas opcionales del moderador
        $data = $request->validate([

            'title' => ['nullable', 'string', 'max:255'],

            'content' => ['nullable', 'string', 'max:10000'],

            'admin_notes' => ['nullable', 'string', 'max:500'],

        ]);



        $this->reportAdminService->editContent(

            auth()->user(),

            $report,

            $data['title'] ?? null,

            $data['content'] ?? null,

            $data['admin_notes'] ?? null,

        );



        return back()->with('success', 'Contenido editado y denuncia cerrada.');

    }



    /**
     * POST /admin/moderacion/alertas/{alert} — cierra una alerta automática de moderación.
     */
    public function resolveAlert(Request $request, ModerationAlert $alert): RedirectResponse

    {

        $request->validate(['status' => ['required', 'in:resolved,dismissed']]);

        $this->reportAdminService->resolveAlert(auth()->user(), $alert, $request->input('status'));



        return back()->with('success', 'Alerta cerrada.');

    }

}


