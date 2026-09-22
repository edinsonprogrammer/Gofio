<?php

namespace App\Services;

/**
 * Crea comentarios en posts, aplica límites de actividad y dispara karma y notificaciones.
 */

use App\Models\AppNotification;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Support\CommentMediaUrl;
use Illuminate\Database\Eloquent\Collection;

class CommentService
{
    /**
     * Inyecta repositorios y servicios de permisos, karma y efectos secundarios.
     */
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly PostRepository $postRepository,
        private readonly RankService $rankService,
        private readonly AsyncSideEffects $asyncSideEffects,
        private readonly UserActivityLimitService $activityLimitService,
        private readonly KarmaRuleService $karmaRuleService,
    ) {}

    /**
     * Valida contenido, crea el comentario, incrementa contadores y notifica al autor del post.
     */
    public function create(User $user, Post $post, array $data): Comment
    {
        if ($post->block_comments) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'content' => ['Los comentarios están deshabilitados en este post.'],
            ]);
        }

        $content = trim(strip_tags((string) ($data['content'] ?? '')));
        $rawImageUrl = isset($data['image_url']) ? trim((string) $data['image_url']) : '';
        $imageUrl = $rawImageUrl !== '' ? CommentMediaUrl::sanitize($rawImageUrl) : null;

        if ($rawImageUrl !== '' && $imageUrl === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'image_url' => ['La imagen adjunta debe ser un archivo subido a Gofio o un GIF de GIPHY.'],
            ]);
        }

        if ($content === '' && empty($imageUrl)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'content' => ['Escribe un comentario o adjunta una imagen.'],
            ]);
        }

        $this->activityLimitService->assertCanComment($user);

        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;

        if ($parentId !== null) {
            $parent = $this->commentRepository->findById($parentId);

            if (! $parent || $parent->post_id !== $post->id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'parent_id' => ['El comentario padre no pertenece a este post.'],
                ]);
            }
        }

        $comment = $this->commentRepository->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => $content,
            'image_url' => $imageUrl ?: null,
        ]);

        $this->postRepository->incrementCommentsCount($post);
        $this->karmaRuleService->evaluateCommentCreated($user, $comment->id);
        $this->asyncSideEffects->queueGamificationSync($user);

        // Notificación al autor del post
        $post->loadMissing('user');
        $preview = $content !== '' ? $content : 'una imagen';
        $this->asyncSideEffects->queueNotification(
            $post->user,
            AppNotification::TYPE_COMMENT_RECEIVED,
            'Nuevo comentario',
            "@{$user->username} comentó en tu post «{$post->title}»: ".str($preview)->limit(80),
            "/post/{$post->slug}",
            [
                'actor_id' => $user->id,
                'post_id' => $post->id,
                'post_slug' => $post->slug,
                'comment_id' => $comment->id,
            ],
        );

        return $comment->load('user.rango');
    }

    /**
     * Obtiene los comentarios de nivel superior de un post con sus respuestas cargadas.
     */
    public function getForPost(Post $post): Collection
    {
        return $this->commentRepository->getTopLevelForPost($post->id);
    }

    /**
     * Serializa un comentario y sus respuestas anidadas para la API o la vista.
     */
    public function formatComment(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'image_url' => $comment->image_url,
            'points_count' => $comment->points_count,
            'parent_id' => $comment->parent_id,
            'created_at' => $comment->created_at?->toISOString(),
            'user' => [
                'id' => $comment->user->id,
                'username' => $comment->user->username,
                'avatar_url' => $comment->user->avatar_url,
                'is_staff' => $comment->user->hasStaffRank(),
                'rango' => $this->rankService->formatRango($comment->user->rango),
            ],
            'replies' => $comment->relationLoaded('replies')
                ? $comment->replies->map(fn (Comment $reply) => $this->formatComment($reply))->values()->all()
                : [],
        ];
    }
}
