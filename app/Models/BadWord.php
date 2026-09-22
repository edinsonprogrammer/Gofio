<?php

/**
 * Modelo Eloquent de palabras prohibidas para filtrado de contenido.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BadWord extends Model
{
    protected $fillable = ['word', 'action'];
}
