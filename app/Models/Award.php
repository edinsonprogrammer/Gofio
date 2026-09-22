<?php

/**
 * Modelo Eloquent de premios otorgables a usuarios por logros o reconocimientos especiales.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Award extends Model
{
    protected $fillable = [
        'award_category_id',
        'name',
        'description',
        'icon',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Categoría a la que pertenece el premio.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AwardCategory::class, 'award_category_id');
    }

    /**
     * Usuarios que han recibido este premio.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['granted_by', 'note', 'granted_at']);
    }
}
