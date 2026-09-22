<?php

/**
 * Evento broadcast cuando un usuario recibe una propina en uno de sus posts.
 */

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TipReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $receiverId,
        public string $senderUsername,
        public float $amount,
        public float $netAmount,
        public int $postId,
        public string $postTitle,
    ) {}

    /**
     * Canal privado del receptor de la propina.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->receiverId),
        ];
    }

    /**
     * Nombre del evento WebSocket: tip.received.
     */
    public function broadcastAs(): string
    {
        return 'tip.received';
    }

    /**
     * Payload con remitente, montos y datos del post.
     */
    public function broadcastWith(): array
    {
        return [
            'sender_username' => $this->senderUsername,
            'amount' => $this->amount,
            'net_amount' => $this->netAmount,
            'post_id' => $this->postId,
            'post_title' => $this->postTitle,
        ];
    }
}
