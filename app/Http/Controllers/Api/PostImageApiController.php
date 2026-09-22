<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de subida de imágenes para publicaciones, con validación de permisos por rango.
 */

use App\Http\Controllers\Controller;
use App\Services\PostPermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PostImageApiController extends Controller
{
    /**
     * Inyecta el servicio de permisos de posts para validar límites de imagen según el rango del usuario.
     */
    public function __construct(
        private readonly PostPermissionService $postPermissionService,
    ) {}

    /**
     * POST /api/uploads/post-image — valida permisos y archivo; responde con JSON de la URL subida.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->postPermissionService->canUploadImage($user)) {
            throw ValidationException::withMessages([
                'image' => ['Tu rango no permite subir imágenes en posts.'],
            ]);
        }

        $maxKb = $this->postPermissionService->maxImageSizeKb($user);

        // Valida tipo y tamaño del archivo según el rango del usuario
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,bmp', 'max:'.$maxKb],
        ]);

        $path = $request->file('image')->store('posts/'.date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'success' => 1,
            'file' => [
                'url' => $url,
            ],
        ]);
    }
}
