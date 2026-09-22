<?php

namespace App\Services;

/**
 * Gestiona solicitudes de verificación de identidad: envío, revisión, revocación y estado del usuario.
 */

use App\Models\AppNotification;
use App\Models\IdentityVerificationRequest;
use App\Models\User;
use App\Repositories\VerificationRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    /**
     * Inyecta repositorio de solicitudes, gamificación y notificaciones.
     */
    public function __construct(
        private readonly VerificationRepository $verificationRepository,
        private readonly GamificationService $gamificationService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Devuelve el estado de verificación del usuario y el detalle de su última solicitud.
     */
    public function getStatus(User $user): array
    {
        $latest = $this->verificationRepository->findLatestForUser($user->id);

        return [
            'tipo_verificacion' => $user->tipo_verificacion,
            'has_identity_verification' => $user->hasIdentityVerification(),
            'is_creator_plus' => $user->isCreatorPlus(),
            'is_staff_verified' => $user->hasStaffAutoVerification() && $user->hasIdentityVerification(),
            'is_verified' => $user->isVerified(),
            'latest_request' => $latest ? $this->formatRequest($latest) : null,
            'can_submit' => $this->canSubmit($user),
            'can_subscribe_creator_plus' => true,
        ];
    }

    /**
     * Registra una nueva solicitud de verificación almacenando el documento en disco privado.
     */
    public function submit(User $user, array $data, UploadedFile $document): IdentityVerificationRequest
    {
        if (! $this->canSubmit($user)) {
            throw ValidationException::withMessages([
                'document' => ['No puedes enviar una nueva solicitud en este momento.'],
            ]);
        }

        $path = $document->store('verifications/'.date('Y/m'), 'local');

        return $this->verificationRepository->create([
            'user_id' => $user->id,
            'full_name' => $data['full_name'],
            'document_type' => $data['document_type'],
            'document_path' => $path,
            'user_notes' => $data['user_notes'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Aprueba una solicitud pendiente, actualiza el tipo de verificación y notifica al usuario.
     */
    public function approve(IdentityVerificationRequest $request, User $admin, ?string $adminNotes = null): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => ['Esta solicitud ya fue revisada.'],
            ]);
        }

        $request->update([
            'status' => 'approved',
            'admin_notes' => $adminNotes,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $user = $request->user;

        if ($user->tipo_verificacion === 'none') {
            $user->update(['tipo_verificacion' => 'user_verified']);
        }

        $this->notificationService->notify(
            $user,
            AppNotification::TYPE_VERIFIED,
            'Cuenta verificada',
            'Tu solicitud de verificación fue aprobada. ¡Bienvenido al club verificado!',
            '/configuracion/verificacion',
        );

        $this->gamificationService->syncAfterActivity($user->fresh());
    }

    /**
     * Rechaza una solicitud pendiente registrando las notas del administrador revisor.
     */
    public function reject(IdentityVerificationRequest $request, User $admin, ?string $adminNotes = null): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => ['Esta solicitud ya fue revisada.'],
            ]);
        }

        $request->update([
            'status' => 'rejected',
            'admin_notes' => $adminNotes,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Aprueba o rechaza varias solicitudes pendientes en una sola operación administrativa.
     * Devuelve cuántas se procesaron y cuántas se omitieron por ya estar revisadas.
     */
    public function bulkReview(User $admin, array $ids, string $action, ?string $adminNotes = null): array
    {
        if (! in_array($action, ['approve', 'reject'], true)) {
            throw ValidationException::withMessages([
                'action' => ['Acción de lote no válida.'],
            ]);
        }

        $requests = $this->verificationRepository->getPendingByIds($ids);
        $processed = 0;
        $skipped = 0;

        foreach ($requests as $request) {
            try {
                if ($action === 'approve') {
                    $this->approve($request, $admin, $adminNotes);
                } else {
                    $this->reject($request, $admin, $adminNotes);
                }
                $processed++;
            } catch (ValidationException) {
                $skipped++;
            }
        }

        return [
            'processed' => $processed,
            'skipped'   => $skipped,
            'total'     => count($ids),
        ];
    }

    /**
     * Revoca verificaciones aprobadas del usuario, ajusta su tipo y notifica la acción administrativa.
     */
    public function revokeIdentityVerification(User $user, User $admin, ?string $reason = null): bool
    {
        $user->refresh();

        $hasApproved = $user->hasApprovedIdentityVerification();
        $hasManualVerified = $user->tipo_verificacion === 'user_verified' && ! $user->isCreatorPlus();

        if (! $hasApproved && ! $hasManualVerified) {
            throw ValidationException::withMessages([
                'user' => ['Este usuario no tiene verificación por solicitud activa.'],
            ]);
        }

        $note = $reason ?: 'Verificación revocada por administración.';
        $this->verificationRepository->revokeApprovedRequests($user, $admin, $note);

        if (! $user->isCreatorPlus() && $user->tipo_verificacion === 'user_verified') {
            $user->update(['tipo_verificacion' => 'none']);
        }

        $this->notificationService->notify(
            $user->fresh(),
            AppNotification::TYPE_MODERATOR_ACTION,
            'Verificación revocada',
            'Tu verificación de identidad fue retirada por un administrador.'.($reason ? " Motivo: {$reason}" : ''),
            '/configuracion/verificacion',
            ['action' => 'verification_revoked'],
        );

        $this->gamificationService->syncAfterActivity($user->fresh());

        return true;
    }

    /**
     * Genera la URL segura de descarga del documento adjunto a una solicitud de verificación.
     */
    public function documentUrl(IdentityVerificationRequest $request): ?string
    {
        if (! Storage::disk('local')->exists($request->document_path)) {
            return null;
        }

        return route('admin.verifications.document', $request);
    }

    /**
     * Determina si el usuario puede enviar una nueva solicitud de verificación de identidad.
     */
    private function canSubmit(User $user): bool
    {
        if ($this->verificationRepository->hasPendingRequest($user->id)) {
            return false;
        }

        if (! $user->canSubmitIdentityVerification()) {
            return false;
        }

        return true;
    }

    /**
     * Serializa una solicitud de verificación para respuestas de API sin exponer la ruta del archivo.
     */
    private function formatRequest(IdentityVerificationRequest $request): array
    {
        return [
            'id' => $request->id,
            'full_name' => $request->full_name,
            'document_type' => $request->document_type,
            'status' => $request->status,
            'user_notes' => $request->user_notes,
            'admin_notes' => $request->admin_notes,
            'reviewed_at' => $request->reviewed_at?->toISOString(),
            'created_at' => $request->created_at?->toISOString(),
        ];
    }
}
