<?php

/**
 * Controlador admin de tickets de soporte: listado priorizado y actualización de estado.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\AdminLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketAdminController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLogService,
    ) {}

    /**
     * GET /admin/tickets — responde con la vista Inertia Admin/Tickets/Index y el listado priorizado.
     */
    public function index(): Response
    {
        // Renderiza la bandeja de tickets de soporte
        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => SupportTicket::query()
                ->with('user:id,username')
                ->orderByDesc('is_creator_plus_priority')
                ->orderByRaw("FIELD(status, 'open', 'in_progress', 'closed')")
                ->orderByDesc('priority')
                ->latest()
                ->paginate(20),
        ]);
    }

    /**
     * PUT /admin/tickets/{ticket} — valida y actualiza el estado, respuesta y prioridad del ticket.
     */
    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        // Valida estado, respuesta del admin y prioridad opcional
        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,closed'],
            'admin_reply' => ['nullable', 'string', 'max:2000'],
            'priority' => ['nullable', 'in:low,normal,high'],
        ]);

        $ticket->update([
            ...$data,
            'admin_read' => true,
            'assigned_to' => auth()->id(),
        ]);

        $this->adminLogService->log(auth()->user(), 'ticket_update', 'ticket', $ticket->id);

        return back()->with('success', 'Ticket actualizado.');
    }
}
