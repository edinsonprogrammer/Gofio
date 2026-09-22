<?php

/**
 * Modelo Eloquent de tickets de soporte enviados por usuarios al equipo de moderación.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'subject', 'category', 'priority', 'status',
        'body', 'admin_reply', 'assigned_to', 'admin_read', 'is_creator_plus_priority',
    ];

    protected function casts(): array
    {
        return ['admin_read' => 'boolean', 'is_creator_plus_priority' => 'boolean'];
    }

    /**
     * Usuario que abrió el ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Miembro del staff asignado al ticket.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
