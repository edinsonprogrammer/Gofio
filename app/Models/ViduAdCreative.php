<?php

/**
 * Creativo de video publicitario para pausas en reels Vidu.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViduAdCreative extends Model
{
    protected $table = 'vidu_ad_creatives';

    protected $fillable = [
        'name',
        'video_path',
        'video_url',
        'duration_seconds',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
