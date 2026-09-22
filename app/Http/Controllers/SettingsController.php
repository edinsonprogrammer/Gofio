<?php

/**
 * Controlador de ajustes de cuenta: perfil, verificación, Creator Plus y apariencia.
 */

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateProfileRequest;
use App\Services\ProfileService;
use App\Services\SubscriptionService;
use App\Services\ThemeService;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService,
        private readonly SubscriptionService $subscriptionService,
        private readonly ThemeService $themeService,
        private readonly ProfileService $profileService,
    ) {}

    /**
     * GET /configuracion/perfil — responde con la vista Inertia Settings/Profile y los datos del perfil.
     */
    public function profile(): Response
    {
        // Renderiza el formulario de edición de perfil
        return Inertia::render('Settings/Profile', [
            'profile' => $this->profileService->formatForSettings(auth()->user()),
        ]);
    }

    /**
     * PUT /configuracion/perfil — valida y persiste los cambios del perfil; redirige al perfil público.
     */
    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $this->profileService->updateProfile(
            $request->user(),
            $request->validated(),
        );

        // Redirige al perfil actualizado con mensaje de éxito
        return redirect()
            ->route('profile.show', $user->username)
            ->with('success', 'Tu perfil se actualizó correctamente.');
    }

    /**
     * GET /configuracion/verificacion — responde con la vista Inertia Settings/Verification y el estado actual.
     */
    public function verification(): Response
    {
        $user = auth()->user();

        // Renderiza la página de solicitud de verificación de identidad
        return Inertia::render('Settings/Verification', [
            'status' => $this->verificationService->getStatus($user),
            'documentTypes' => [
                ['value' => 'id_card', 'label' => 'Documento de identidad'],
                ['value' => 'passport', 'label' => 'Pasaporte'],
                ['value' => 'driver_license', 'label' => 'Licencia de conducir'],
            ],
        ]);
    }

    /**
     * GET /configuracion/creator-plus — responde con la vista Inertia Settings/CreatorPlus y el estado de suscripción.
     */
    public function creatorPlus(): Response
    {
        $user = auth()->user();

        // Renderiza la página de gestión de suscripción Creator Plus
        return Inertia::render('Settings/CreatorPlus', [
            'subscription' => $this->subscriptionService->getStatus($user),
        ]);
    }

    /**
     * GET /configuracion/apariencia — responde con la vista Inertia Settings/Appearance o redirige si no tiene acceso.
     */
    public function appearance(): Response|RedirectResponse
    {
        $user = auth()->user();

        if (! $user->canCustomizeAppearance()) {
            // Redirige a Creator Plus si el usuario no tiene el beneficio
            return redirect()
                ->route('settings.creator-plus')
                ->with('error', 'La personalización de apariencia es un beneficio exclusivo de Creator Plus.');
        }

        // Renderiza el selector de temas visuales
        return Inertia::render('Settings/Appearance', [
            'appearance' => $this->themeService->getAppearanceStatus($user),
        ]);
    }
}
