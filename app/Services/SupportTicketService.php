<?php

namespace App\Services;

/**
 * Gestiona tickets de soporte prioritario exclusivos para suscriptores Creator Plus.
 */

use App\Models\AppNotification;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SupportTicketService
{
    /**
     * Inyecta el servicio de notificaciones para alertar al equipo de moderación.
     */
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Crea un ticket de alta prioridad para Creator Plus y notifica al staff de moderación.
     */
    public function createCreatorPlusPriorityTicket(User $user, string $subject, string $body): array
    {
        if (! $user->isCreatorPlus()) {
            throw ValidationException::withMessages([
                'subject' => ['Solo usuarios con Creator Plus activo pueden usar soporte prioritario.'],
            ]);
        }

        $subject = trim($subject);
        $body = trim($body);

        if ($subject === '' || $body === '') {
            throw ValidationException::withMessages([
                'subject' => ['El asunto y la descripción son obligatorios.'],
            ]);
        }

        $ticket = SupportTicket::query()->create([
            'user_id' => $user->id,
            'subject' => $subject,
            'category' => 'moderacion',
            'priority' => 'high',
            'status' => 'open',
            'body' => $body,
            'is_creator_plus_priority' => true,
            'admin_read' => false,
        ]);

        $this->notificationService->notifyStaff(
            AppNotification::TYPE_MODERATOR_ACTION,
            'Ticket prioritario Creator Plus',
            "@{$user->username} abrió soporte prioritario: {$subject}",
            '/admin/tickets',
            [
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'creator_plus_priority' => true,
            ],
        );

        return [
            'success' => true,
            'ticket_id' => $ticket->id,
            'message' => 'Tu solicitud prioritiva fue enviada. Moderación la revisará pronto.',
        ];
    }
}
