<?php

namespace App\Services;

/**
 * Orquesta la creación, consulta y serialización de posts con permisos, karma y efectos secundarios.
 */

use App\Models\AppNotification;
use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;
use App\Support\ContentSanitizer;
use Illuminate\Support\Str;

class PostService
{
    /**
     * Inyecta dependencias de persistencia, permisos, reacciones y efectos asíncronos.
     */
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly ContentSanitizer $contentSanitizer,
        private readonly RankService $rankService,
        private readonly PostPermissionService $postPermissionService,
        private readonly ReactionService $reactionService,
        private readonly NotificationService $notificationService,
        private readonly KarmaRuleService $karmaRuleService,
        private readonly TipPermissionService $tipPermissionService,
        private readonly AsyncSideEffects $asyncSideEffects,
        private readonly PostViewService $postViewService,
    ) {}

    /**
     * Crea un post validando permisos, sanitizando contenido y disparando gamificación y notificaciones.
     */
    public function create(User $user, array $data): Post
    {
        $this->postPermissionService->assertCanCreate($user, $data);

        $blocks = $data['content']['blocks'] ?? $data['content'] ?? [];
        $allowedTools = $this->postPermissionService->forUser($user)['tools'];
        $sanitized = $this->contentSanitizer->sanitize(
            is_array($blocks) ? $blocks : [],
            $allowedTools,
        );

        if ($sanitized === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'content' => ['El contenido del post no es válido o está vacío.'],
            ]);
        }

        $slug = $this->generateUniqueSlug($data['title']);

        $post = $this->postRepository->create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'slug' => $slug,
            'content' => ['blocks' => $sanitized],
            'tags' => isset($data['tags']) ? trim((string) $data['tags']) : null,
            'block_comments' => (bool) ($data['block_comments'] ?? false),
            'is_private' => (bool) ($data['is_private'] ?? false),
            'status' => $data['status'] ?? 'published',
        ]);

        $this->karmaRuleService->evaluatePostCreated($user, $post);
        $this->asyncSideEffects->queueGamificationSync($user);

        if (($post->status ?? 'published') === 'published' && ! $post->is_private) {
            $post->loadMissing('user');
            \App\Jobs\NotifyFollowersJob::dispatch(
                $user->id,
                AppNotification::TYPE_POST_FROM_FOLLOWING,
                'Nuevo post',
                "@{$user->username} publicó: {$post->title}",
                "/post/{$post->slug}",
                ['post_id' => $post->id, 'post_slug' => $post->slug],
            );

            app(\App\Services\SeoService::class)->notifyPublishedPost($post);
        }

        return $post;
    }

    /**
     * Busca un post publicado por slug y registra la vista única si es visible públicamente.
     */
    public function show(string $slug, ?User $viewer = null, ?string $ipAddress = null): ?Post
    {
        $post = $this->postRepository->findBySlug($slug);

        if ($post && $post->status === 'published' && ! $post->is_private) {
            $this->postViewService->record($post, $viewer, $ipAddress);
        }

        return $post;
    }

    /**
     * Resuelve un post por slug respetando privacidad, estado y rol del visitante.
     */
    public function findVisiblePost(string $slug, ?User $viewer, ?string $ipAddress = null): ?Post
    {
        $post = $this->postRepository->findBySlugIncludingPrivate($slug);

        if (! $post || $post->status === 'banned') {
            return null;
        }

        if ($post->is_private && ! $this->viewerCanAccessPrivatePost($post, $viewer)) {
            return null;
        }

        if ($post->status === 'published' && ! $post->is_private) {
            $this->postViewService->record($post, $viewer, $ipAddress);
        }

        return $post;
    }

    /**
     * Comprueba si un visitante puede ver un post privado (autor, admin o staff).
     */
    public function viewerCanAccessPrivatePost(Post $post, ?User $viewer): bool
    {
        return $post->user_id === $viewer?->id
            || $viewer?->isAdmin()
            || $viewer?->hasStaffRank();
    }

    /**
     * Comprueba si el visitante puede interactuar con un post publicado (voto, reacción, etc.).
     */
    public function viewerCanInteractWithPublishedPost(Post $post, ?User $viewer): bool
    {
        if ($post->status !== 'published') {
            return false;
        }

        if ($post->is_private && ! $this->viewerCanAccessPrivatePost($post, $viewer)) {
            return false;
        }

        return true;
    }

    /**
     * Registra una vista desde el feed o API y devuelve si se contabilizó y el total visible.
     *
     * @return array{recorded: bool, views_count: int}
     */
    public function recordFeedView(Post $post, ?User $viewer, ?string $ipAddress): array
    {
        if ($post->status !== 'published' || $post->is_private) {
            return [
                'recorded' => false,
                'views_count' => $this->postViewService->displayCount($post),
            ];
        }

        $recorded = $this->postViewService->record($post, $viewer, $ipAddress);

        return [
            'recorded' => $recorded,
            'views_count' => $this->postViewService->displayCount($post),
        ];
    }

    /**
     * Serializa un post para API o feed, incluyendo autor, categoría, reacciones y permiso de propina.
     */
    public function formatPost(Post $post, ?array $reactions = null, ?User $viewer = null, bool $forFeed = false): array
    {
        $viewer ??= auth()->user();

        $content = $post->content;

        if ($forFeed) {
            $content = $this->truncateContentForFeed($content ?? $this->feedPreviewFromTitle($post->title));
        }

        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $content,
            'points_count' => $post->points_count,
            'comments_count' => $post->comments_count,
            'views_count' => $this->postViewService->displayCount($post),
            'tips_total' => (float) ($post->tips_total ?? 0),
            'tips_count' => (int) ($post->tips_count ?? 0),
            'can_tip' => $viewer ? $this->tipPermissionService->canTipPost($viewer, $post) : false,
            'status' => $post->status,
            'tags' => $post->tags,
            'block_comments' => $post->block_comments,
            'is_private' => $post->is_private,
            'user_id' => $post->user_id,
            'created_at' => $post->created_at?->toISOString(),
            'reactions' => $reactions ?? $this->reactionService->summariesForPosts(
                [$post->id],
                auth()->user(),
            )[$post->id] ?? [
                'summary' => array_fill_keys(\App\Models\PostReaction::TYPES, 0),
                'total' => 0,
                'user_reaction' => null,
            ],
            'user' => [
                'id' => $post->user->id,
                'username' => $post->user->username,
                'avatar_url' => $post->user->avatar_url,
                'tipo_verificacion' => $post->user->displayVerificationTipo(),
                'is_creator_plus' => $post->user->isCreatorPlus(),
                'rango' => $this->rankService->formatRango($post->user->rango),
            ],
            'category' => $post->category?->only(['id', 'name', 'slug', 'icon']),
        ];
    }

    /**
     * Genera un slug URL único a partir del título, añadiendo sufijo numérico si ya existe.
     */
    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 50, ''));
        $slug = $base;
        $counter = 1;

        while ($this->postRepository->slugExists($slug)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Recorta el contenido del post a los primeros bloques para vista previa en el feed.
     */
    private function truncateContentForFeed(?array $content): ?array
    {
        if (! is_array($content)) {
            return $content;
        }

        $blocks = $content['blocks'] ?? $content;

        if (! is_array($blocks)) {
            return $content;
        }

        $limited = array_slice($blocks, 0, 4);

        return ['blocks' => $limited];
    }

    /**
     * Construye un bloque de párrafo mínimo a partir del título cuando no hay contenido disponible.
     */
    private function feedPreviewFromTitle(string $title): array
    {
        return [
            'blocks' => [
                [
                    'type' => 'paragraph',
                    'data' => ['text' => Str::limit($title, 180)],
                ],
            ],
        ];
    }
}
