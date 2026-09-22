<?php

/**
 * Modelo Eloquent del historial de eventos que otorgan o restan karma a un usuario.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserKarmaEvent extends Model
{
    protected $fillable = [
        'user_id',
        'rule_key',
        'reference_type',
        'reference_id',
        'karma_awarded',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'karma_awarded' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Usuario al que se le otorgó o restó karma.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
