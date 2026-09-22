<?php

/**
 * Evento broadcast cuando se envía un mensaje en una conversación privada.
 */

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public int $recipientId,
    ) {
        $this->message->load('user');
    }

    /**
     * Canales privados del chat y del destinatario.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->message->conversation_id),
            new PrivateChannel('user.'.$this->recipientId),
        ];
    }

    /**
     * Nombre del evento WebSocket: message.sent.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Payload con el mensaje serializado y datos del remitente.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'body' => $this->message->body,
                'type' => $this->message->type,
                'created_at' => $this->message->created_at?->toISOString(),
                'user' => [
                    'id' => $this->message->user->id,
                    'username' => $this->message->user->username,
                    'avatar_url' => $this->message->user->avatar_url,
                ],
            ],
        ];
    }
}
