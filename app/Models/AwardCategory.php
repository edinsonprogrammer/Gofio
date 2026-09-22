<?php

/**
 * Modelo Eloquent de categorías que agrupan premios por tipo.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardCategory extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Premios agrupados en esta categoría, ordenados por sort_order.
     */
    public function awards(): HasMany
    {
        return $this->hasMany(Award::class)->orderBy('sort_order');
    }
}
