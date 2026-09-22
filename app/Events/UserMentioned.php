<?php

/**
 * Evento broadcast cuando un usuario es mencionado en un post o comentario.
 */

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMentioned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $mentionedUserId,
        public string $mentionedBy,
        public string $context,
        public string $preview,
    ) {}

    /**
     * Canal privado del usuario mencionado.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->mentionedUserId),
        ];
    }

    /**
     * Nombre del evento WebSocket: user.mentioned.
     */
    public function broadcastAs(): string
    {
        return 'user.mentioned';
    }

    /**
     * Payload con autor de la mención, contexto y vista previa.
     */
    public function broadcastWith(): array
    {
        return [
            'mentioned_by' => $this->mentionedBy,
            'context' => $this->context,
            'preview' => $this->preview,
        ];
    }
}
