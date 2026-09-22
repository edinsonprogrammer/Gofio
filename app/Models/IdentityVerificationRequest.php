<?php

/**
 * Modelo Eloquent de solicitudes de verificación de identidad enviadas por usuarios.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityVerificationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'document_type',
        'document_path',
        'user_notes',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Usuario solicitante de la verificación.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Administrador que revisó la solicitud.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
