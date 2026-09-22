<?php

namespace App\Services;

/**
 * Recibe denuncias de contenido o usuarios, valida reglas y alerta al equipo de moderación.
 */

use App\Models\AppNotification;
use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Post;
use App\Models\User;
use App\Support\ReportReasons;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ReportService
{
    /**
     * Inyecta el servicio de notificaciones para avisar al staff.
     */
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Crea una denuncia pendiente tras validar motivo, existencia del contenido y duplicados.
     */
    public function submit(
        User $reporter,
        string $modelClass,
        int $reportableId,
        string $reason,
        ?string $details = null,
    ): ContentReport {
        if (! ReportReasons::isValid($reason)) {
            throw ValidationException::withMessages([
                'reason' => ['Motivo de denuncia no válido.'],
            ]);
        }

        $reportable = $modelClass::query()->find($reportableId);

        if (! $reportable) {
            throw ValidationException::withMessages([
                'reportable_id' => ['Contenido no encontrado.'],
            ]);
        }

        if ($reportable instanceof User && $reportable->id === $reporter->id) {
            throw ValidationException::withMessages([
                'reportable_id' => ['No puedes reportarte a ti mismo.'],
            ]);
        }

        if ($reportable instanceof Post && $reportable->user_id === $reporter->id) {
            throw ValidationException::withMessages([
                'reportable_id' => ['No puedes denunciar tu propia publicación.'],
            ]);
        }

        if ($reportable instanceof Comment && $reportable->user_id === $reporter->id) {
            throw ValidationException::withMessages([
                'reportable_id' => ['No puedes denunciar tu propio comentario.'],
            ]);
        }

        $alreadyPending = ContentReport::query()
            ->where('reporter_id', $reporter->id)
            ->where('reportable_type', $modelClass)
            ->where('reportable_id', $reportableId)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            throw ValidationException::withMessages([
                'reason' => ['Ya enviaste una denuncia pendiente sobre este contenido.'],
            ]);
        }

        $report = ContentReport::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $modelClass,
            'reportable_id' => $reportableId,
            'reason' => $reason,
            'details' => $details,
            'status' => 'pending',
        ]);

        if ($reportable instanceof Comment) {
            $reportable->loadMissing('post');
        }

        $this->notifyStaffForReport($report, $reportable, $reporter);

        return $report;
    }

    /**
     * Envía notificación al staff con contexto del tipo de contenido denunciado y su motivo.
     */
    private function notifyStaffForReport(ContentReport $report, Model $reportable, User $reporter): void
    {
        $label = match (true) {
            $reportable instanceof User => 'usuario',
            $reportable instanceof Post => 'publicación',
            $reportable instanceof Comment => 'comentario',
            default => 'contenido',
        };

        $type = match (true) {
            $reportable instanceof User => AppNotification::TYPE_USER_REPORTED,
            $reportable instanceof Post => AppNotification::TYPE_POST_REPORTED,
            default => AppNotification::TYPE_CONTENT_REPORTED,
        };

        $title = match (true) {
            $reportable instanceof User => 'Usuario denunciado',
            $reportable instanceof Post => 'Publicación denunciada',
            default => 'Comentario denunciado',
        };

        $reasonLabel = ReportReasons::label($report->reason);
        $body = "Nueva denuncia de {$label} ({$reasonLabel}) por @{$reporter->username}.";

        $this->notificationService->notifyStaff(
            $type,
            $title,
            $body,
            '/admin/moderacion',
            [
                'report_id' => $report->id,
                'actor_id' => $report->reporter_id,
                'reason' => $report->reason,
            ],
        );
    }
}
