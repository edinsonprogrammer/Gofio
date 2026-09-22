<?php

namespace App\Services;

/**
 * Transforma denuncias de contenido en estructuras enriquecidas para el panel de administración.
 */

use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Post;
use App\Models\User;
use App\Support\ReportReasons;

class ReportFormatterService
{
    /**
     * Serializa una denuncia con resumen del contenido, autor, URL y datos editables para moderación.
     */
    public function formatForAdmin(ContentReport $report): array
    {
        $report->loadMissing(['reporter:id,username', 'reportable']);

        $reportable = $report->reportable;
        $author = $this->resolveAuthor($reportable);

        return [
            'id' => $report->id,
            'reason' => $report->reason,
            'reason_label' => ReportReasons::label($report->reason),
            'details' => $report->details,
            'status' => $report->status,
            'admin_notes' => $report->admin_notes,
            'created_at' => $report->created_at?->toISOString(),
            'reporter' => $report->reporter ? [
                'id' => $report->reporter->id,
                'username' => $report->reporter->username,
            ] : null,
            'reportable_type' => class_basename($report->reportable_type),
            'reportable_id' => $report->reportable_id,
            'summary' => $this->summarize($reportable),
            'content_url' => $this->contentUrl($reportable),
            'author' => $author,
            'editable' => $this->editablePayload($reportable),
        ];
    }

    /**
     * Devuelve los campos editables del contenido denunciado según su tipo (post o comentario).
     */
    private function editablePayload(mixed $reportable): ?array
    {
        if ($reportable instanceof Post) {
            return [
                'title' => $reportable->title,
                'content' => $reportable->content,
            ];
        }

        if ($reportable instanceof Comment) {
            return [
                'content' => $reportable->content,
            ];
        }

        return null;
    }

    /**
     * Resuelve el autor del contenido denunciado como par id/username.
     */
    private function resolveAuthor(mixed $reportable): ?array
    {
        if ($reportable instanceof User) {
            return [
                'id' => $reportable->id,
                'username' => $reportable->username,
            ];
        }

        if ($reportable instanceof Post) {
            $reportable->loadMissing('user:id,username');

            return $reportable->user ? [
                'id' => $reportable->user->id,
                'username' => $reportable->user->username,
            ] : null;
        }

        if ($reportable instanceof Comment) {
            $reportable->loadMissing('user:id,username');

            return $reportable->user ? [
                'id' => $reportable->user->id,
                'username' => $reportable->user->username,
            ] : null;
        }

        return null;
    }

    /**
     * Genera un texto breve que describe el contenido denunciado para listados administrativos.
     */
    private function summarize(mixed $reportable): string
    {
        if ($reportable instanceof Post) {
            return $reportable->title ?? 'Publicación #'.$reportable->id;
        }

        if ($reportable instanceof Comment) {
            $text = trim(strip_tags((string) $reportable->content));

            return $text !== '' ? mb_substr($text, 0, 120) : 'Comentario #'.$reportable->id;
        }

        if ($reportable instanceof User) {
            return '@'.$reportable->username;
        }

        return 'Contenido #'.($reportable->id ?? '?');
    }

    /**
     * Construye la ruta relativa al contenido denunciado dentro de la aplicación.
     */
    private function contentUrl(mixed $reportable): ?string
    {
        if ($reportable instanceof Post) {
            return '/post/'.$reportable->slug;
        }

        if ($reportable instanceof Comment) {
            $reportable->loadMissing('post:id,slug');

            return $reportable->post ? '/post/'.$reportable->post->slug.'#comment-'.$reportable->id : null;
        }

        if ($reportable instanceof User) {
            return '/perfil/'.$reportable->username;
        }

        return null;
    }
}
