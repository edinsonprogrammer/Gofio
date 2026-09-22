<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de subida de avatar y banner del perfil del usuario autenticado.
 */

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileImageApiController extends Controller
{
    /**
     * Inyecta el servicio de perfil para persistir avatar y banner tras la subida de imágenes.
     */
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    /**
     * POST /api/uploads/profile-avatar — valida la imagen, la almacena y responde con JSON de la URL actualizada.
     */
    public function storeAvatar(Request $request): JsonResponse
    {
        // Valida formato y tamaño del avatar
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,bmp', 'max:5120'],
        ]);

        $user = $request->user();
        $path = $request->file('image')->store('profiles/avatars/'.date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);

        $this->profileService->updateAvatar($user, $url);

        return response()->json([
            'success' => 1,
            'file' => ['url' => $url],
        ]);
    }

    /**
     * POST /api/uploads/profile-banner — valida la imagen, la almacena y responde con JSON de la URL actualizada.
     */
    public function storeBanner(Request $request): JsonResponse
    {
        // Valida formato y tamaño del banner (límite mayor que el avatar)
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,bmp', 'max:8192'],
        ]);

        $user = $request->user();
        $path = $request->file('image')->store('profiles/banners/'.date('Y/m'), 'public');
        $url = Storage::disk('public')->url($path);

        $this->profileService->updateBanner($user, $url);

        return response()->json([
            'success' => 1,
            'file' => ['url' => $url],
        ]);
    }
}
