<?php

/**
 * Modelo Eloquent del registro de acciones administrativas.
 * Audita operaciones del staff sobre usuarios y contenido.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'action', 'target_type', 'target_id',
        'reason', 'metadata', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Administrador que ejecutó la acción registrada.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
