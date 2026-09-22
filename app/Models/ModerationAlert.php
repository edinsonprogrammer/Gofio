<?php

/**
 * Modelo Eloquent de alertas de moderación generadas por comportamiento sospechoso.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAlert extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'related_user_id',
        'type',
        'reason',
        'metadata',
        'status',
        'resolved_by',
        'resolved_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'resolved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Usuario principal relacionado con la alerta.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Segundo usuario implicado en la alerta, si aplica.
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    /**
     * Moderador que resolvió la alerta.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
