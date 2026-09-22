<?php

/**
 * Modelo Eloquent de transacciones de monedas entre usuarios (propinas, comisiones).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'post_id',
        'amount',
        'platform_fee',
        'type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Usuario que envió las monedas.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Usuario que recibió las monedas.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Post asociado a la propina, si aplica.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
