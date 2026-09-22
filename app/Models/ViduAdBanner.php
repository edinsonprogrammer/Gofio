<?php

/**
 * Banner publicitario vertical para la barra lateral derecha de Vidu Reels.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViduAdBanner extends Model
{
    protected $table = 'vidu_ad_banners';

    protected $fillable = [
        'name',
        'image_path',
        'image_url',
        'link_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
