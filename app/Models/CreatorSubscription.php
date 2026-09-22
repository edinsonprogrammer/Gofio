<?php

/**
 * Modelo Eloquent de suscripciones Creator Plus.
 * Registra pagos, vigencia y estado de cada período.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'amount_paid',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Usuario suscriptor de Creator Plus.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
