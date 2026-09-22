<?php

namespace App\Services;

/**
 * Ejecuta acciones de moderación sobre denuncias y alertas desde el panel administrativo.
 */

use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\ModerationAlert;
use App\Models\Post;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReportAdminService
{
    /**
     * Inyecta servicios de auditoría, moderación de posts y gestión de usuarios.
     */
    public function __construct(
        private readonly AdminLogService $adminLogService,
        private readonly PostAdminService $postAdminService,
        private readonly UserAdminService $userAdminService,
    ) {}

    /**
     * Marca una denuncia pendiente como resuelta o descartada registrando notas del revisor.
     */
    public function resolveReport(User $admin, ContentReport $report, string $status, ?string $notes = null): void
    {
        if ($report->status !== 'pending') {
            throw ValidationException::withMessages([
                'report' => ['Esta denuncia ya fue procesada.'],
            ]);
        }

        $report->update([
            'status' => $status,
            'reviewed_by' => $admin->id,
            'admin_notes' => $notes,
        ]);

        $this->adminLogService->log($admin, 'report_'.$status, 'report', $report->id, $notes);
    }

    /**
     * Aplica una acción de moderación sobre el contenido denunciado y cierra la denuncia.
     */
    public function takeAction(User $moderator, ContentReport $report, string $action, ?string $notes = null, ?int $suspendDays = null): void
    {
        if ($report->status !== 'pending') {
            throw ValidationException::withMessages([
                'report' => ['Esta denuncia ya fue procesada.'],
            ]);
        }

        $report->loadMissing('reportable');

        $reason = $notes ?: 'Denuncia #'.$report->id.' — '.($report->details ?: 'sin detalle adicional');

        match ($action) {
            'dismiss' => null,
            'delete_content' => $this->deleteReportedContent($moderator, $report, $reason),
            'suspend_author' => $this->suspendAuthor($moderator, $report, $reason, $suspendDays),
            'reviewed' => null,
            default => throw ValidationException::withMessages([
                'action' => ['Acción de moderación no válida.'],
            ]),
        };

        $status = $action === 'dismiss' ? 'dismissed' : 'reviewed';

        $report->update([
            'status' => $status,
            'reviewed_by' => $moderator->id,
            'admin_notes' => $notes,
        ]);

        $this->adminLogService->log($moderator, 'report_action_'.$action, 'report', $report->id, $notes);
    }

    /**
     * Cierra una alerta de moderación automática registrando quién la resolvió.
     */
    public function resolveAlert(User $admin, ModerationAlert $alert, string $status): void
    {
        $alert->update([
            'status' => $status,
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
        ]);

        $this->adminLogService->log($admin, 'alert_'.$status, 'alert', $alert->id);
    }

    /**
     * Edita el título o contenido denunciado y marca la denuncia como revisada.
     */
    public function editContent(User $moderator, ContentReport $report, ?string $title = null, ?string $content = null, ?string $notes = null): void
    {
        if ($report->status !== 'pending') {
            throw ValidationException::withMessages([
                'report' => ['Esta denuncia ya fue procesada.'],
            ]);
        }

        $report->loadMissing('reportable');
        $reportable = $report->reportable;

        if ($reportable instanceof Post) {
            $updates = array_filter([
                'title' => $title,
                'content' => $content,
            ], fn ($value) => $value !== null && $value !== '');

            if ($updates === []) {
                throw ValidationException::withMessages([
                    'content' => ['Indica el título o contenido a editar.'],
                ]);
            }

            $reportable->update($updates);
        } elseif ($reportable instanceof Comment) {
            if ($content === null || trim($content) === '') {
                throw ValidationException::withMessages([
                    'content' => ['Indica el nuevo texto del comentario.'],
                ]);
            }

            $reportable->update(['content' => $content]);
        } else {
            throw ValidationException::withMessages([
                'action' => ['Solo se pueden editar publicaciones y comentarios.'],
            ]);
        }

        $report->update([
            'status' => 'reviewed',
            'reviewed_by' => $moderator->id,
            'admin_notes' => $notes,
        ]);

        $this->adminLogService->log($moderator, 'report_edit_content', 'report', $report->id, $notes);
    }

    /**
     * Elimina o banea el contenido denunciado según sea post, comentario o usuario.
     */
    private function deleteReportedContent(User $moderator, ContentReport $report, string $reason): void
    {
        $reportable = $report->reportable;

        if ($reportable instanceof Post) {
            $this->postAdminService->setStatus($moderator, $reportable, 'banned', $reason);

            return;
        }

        if ($reportable instanceof Comment) {
            $reportable->delete();

            return;
        }

        if ($reportable instanceof User) {
            throw ValidationException::withMessages([
                'action' => ['Para usuarios denunciados usa la acción de suspender autor.'],
            ]);
        }

        throw ValidationException::withMessages([
            'action' => ['No se pudo eliminar el contenido denunciado.'],
        ]);
    }

    /**
     * Suspende al autor del contenido denunciado durante los días indicados.
     */
    private function suspendAuthor(User $moderator, ContentReport $report, string $reason, ?int $suspendDays): void
    {
        $author = $this->resolveAuthor($report->reportable);

        if (! $author) {
            throw ValidationException::withMessages([
                'action' => ['No se encontró al autor del contenido denunciado.'],
            ]);
        }

        if ($author->isAdmin()) {
            throw ValidationException::withMessages([
                'action' => ['No puedes suspender a un administrador.'],
            ]);
        }

        $this->userAdminService->banUser(
            $moderator,
            $author,
            $reason,
            $suspendDays ?: 7,
        );
    }

    /**
     * Obtiene el usuario autor del contenido denunciado según su tipo polimórfico.
     */
    private function resolveAuthor(mixed $reportable): ?User
    {
        if ($reportable instanceof User) {
            return $reportable;
        }

        if ($reportable instanceof Post) {
            return $reportable->user;
        }

        if ($reportable instanceof Comment) {
            return $reportable->user;
        }

        return null;
    }
}
