<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de subida de imágenes adjuntas a comentarios.
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CommentImageApiController extends Controller
{
    /**
     * POST /api/uploads/comment-image — valida el archivo y responde con JSON de la URL subida.
     */
    public function store(Request $request): JsonResponse
    {
        // Valida formato y tamaño máximo de la imagen
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,bmp', 'max:5120'],
        ]);

        $path = $request->file('image')->store('comments/'.date('Y/m'), 'public');

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => Storage::disk('public')->url($path),
            ],
        ]);
    }
}
