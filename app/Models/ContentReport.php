<?php

/**
 * Modelo Eloquent de denuncias de contenido.
 * Relación polimórfica con posts, comentarios o usuarios reportados.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentReport extends Model
{
    protected $fillable = [
        'reporter_id', 'reportable_type', 'reportable_id', 'reason',
        'details', 'status', 'reviewed_by', 'admin_notes',
    ];

    /**
     * Usuario que realizó la denuncia.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Moderador que revisó la denuncia.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Entidad denunciada (post, comentario o usuario) vía relación polimórfica.
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
