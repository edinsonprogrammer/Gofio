<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de publicaciones: feed paginado, creación, votos, reacciones y comentarios.
 */

use App\Http\Controllers\Controller;
use App\Http\Requests\ReactPostRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StorePostRequest;
use App\Models\Category;
use App\Repositories\PostRepository;
use App\Services\CommentService;
use App\Services\PostService;
use App\Services\ReactionService;
use App\Services\VoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostApiController extends Controller
{
    /**
     * Inyecta repositorio y servicios del feed de Gofio: posts, comentarios, votos y reacciones.
     */
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly PostService $postService,
        private readonly CommentService $commentService,
        private readonly VoteService $voteService,
        private readonly ReactionService $reactionService,
    ) {}

    /**
     * GET /api/posts — responde con JSON del feed paginado filtrable por categoría.
     */
    public function index(Request $request): JsonResponse
    {
        $categoryId = null;

        if ($request->filled('categoria')) {
            $category = Category::query()
                ->where('slug', $request->string('categoria')->toString())
                ->first();

            if (! $category) {
                return response()->json([
                    'message' => 'Categoría no encontrada.',
                ], 404);
            }

            $categoryId = $category->id;
        } elseif ($request->filled('category_id')) {
            $categoryId = Category::query()
                ->whereKey($request->integer('category_id'))
                ->value('id');
        }

        $paginator = $this->postRepository->getPublishedFeed(
            perPage: (int) $request->integer('per_page', 10),
            page: (int) $request->integer('page', 1),
            categoryId: $categoryId,
        );

        $items = collect($paginator->items());
        $summaries = $this->reactionService->summariesForPosts(
            $items->pluck('id')->all(),
            $request->user(),
        );

        return response()->json([
            'data' => $items->map(fn ($post) => $this->postService->formatPost(
                $post,
                $summaries[$post->id] ?? null,
                forFeed: true,
            )),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'category_id' => $categoryId,
            ],
        ]);
    }

    /**
     * GET /api/posts/composer-config — responde con JSON de permisos del editor de posts del usuario.
     */
    public function composerConfig(Request $request): JsonResponse
    {
        return response()->json([
            'data' => app(\App\Services\PostPermissionService::class)->forUser($request->user()),
        ]);
    }

    /**
     * POST /api/posts — valida el cuerpo y responde con JSON del post creado (201).
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->postService->create($request->user(), $request->validated());

        return response()->json([
            'data' => $this->postService->formatPost($post),
        ], 201);
    }

    /**
     * POST /api/posts/{post}/view — registra una visita única al post desde el feed.
     */
    public function recordView(Request $request, int $post): JsonResponse
    {
        $postModel = $this->postRepository->findById($post);

        if (! $postModel || $postModel->status !== 'published' || $postModel->is_private) {
            return response()->json(['message' => 'Post no encontrado.'], 404);
        }

        $result = $this->postService->recordFeedView(
            $postModel,
            $request->user(),
            $request->ip(),
        );

        return response()->json($result);
    }

    /**
     * GET /api/posts/{slug} — responde con JSON del post o 404 si no existe.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $post = $this->postService->show($slug, $request->user(), $request->ip());

        if (! $post) {
            return response()->json(['message' => 'Post no encontrado.'], 404);
        }

        return response()->json([
            'data' => $this->postService->formatPost($post),
        ]);
    }

    /**
     * GET /api/posts/{slug}/comments — responde con JSON del listado de comentarios del post.
     */
    public function comments(string $slug): JsonResponse
    {
        $post = $this->postRepository->findBySlug($slug);

        if (! $post) {
            return response()->json(['message' => 'Post no encontrado.'], 404);
        }

        $comments = $this->commentService->getForPost($post);

        return response()->json([
            'data' => $comments->map(fn ($c) => $this->commentService->formatComment($c)),
        ]);
    }

    /**
     * POST /api/posts/{slug}/comments — valida el comentario y responde con JSON del comentario creado (201).
     */
    public function storeComment(StoreCommentRequest $request, string $slug): JsonResponse
    {
        $post = $this->postRepository->findBySlug($slug);

        if (! $post) {
            return response()->json(['message' => 'Post no encontrado.'], 404);
        }

        $comment = $this->commentService->create($request->user(), $post, $request->validated());

        return response()->json([
            'data' => $this->commentService->formatComment($comment),
        ], 201);
    }

    /**
     * POST /api/posts/{post}/vote — responde con JSON del resultado del voto o 404 si el post no es votable.
     */
    public function vote(Request $request, int $post): JsonResponse
    {
        $postModel = $this->postRepository->findById($post);

        if (! $postModel || ! $this->postService->viewerCanInteractWithPublishedPost($postModel, $request->user())) {
            return response()->json(['message' => 'Post no encontrado.'], 404);
        }

        $result = $this->voteService->votePost($request->user(), $postModel);

        return response()->json($result);
    }

    /**
     * POST /api/posts/{post}/react — valida la reacción y responde con JSON del resultado o 404.
     */
    public function react(ReactPostRequest $request, int $post): JsonResponse
    {
        $postModel = $this->postRepository->findById($post);

        if (! $postModel || ! $this->postService->viewerCanInteractWithPublishedPost($postModel, $request->user())) {
            return response()->json(['message' => 'Post no encontrado.'], 404);
        }

        $result = $this->reactionService->react(
            $request->user(),
            $postModel,
            $request->validated('reaction'),
        );

        return response()->json($result);
    }
}
