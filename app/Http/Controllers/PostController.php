<?php

/**
 * Controlador de visualización de publicaciones individuales en la interfaz web.
 */

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\PostService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
        private readonly SeoService $seoService,
    ) {}

    /**
     * GET /post/{slug} — responde con la vista Inertia Posts/Show o 404 si el post no es visible.
     */
    public function show(Request $request, string $slug): Response
    {
        $post = $this->postService->findVisiblePost($slug, auth()->user(), $request->ip());

        abort_unless($post, 404);

        $post->loadMissing(['user', 'category']);

        // Renderiza la vista detallada del post
        return Inertia::render('Posts/Show', [
            'post' => $this->postService->formatPost($post),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug', 'icon']),
            'seo' => $this->seoService->forPost($post),
        ]);
    }
}
