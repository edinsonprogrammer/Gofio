<?php

/**
 * Controlador admin de publicaciones: listado, moderación, publicación y destacado de posts.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostAdminController extends Controller
{
    public function __construct(
        private readonly PostAdminService $postAdminService,
    ) {}

    /**
     * GET /admin/posts — responde con la vista Inertia Admin/Posts/Index y el listado filtrable.
     */
    public function index(Request $request): Response
    {
        $status = $request->input('status');

        $posts = Post::query()
            ->with(['user:id,username', 'category:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->input('q'), fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Renderiza la tabla de moderación de publicaciones
        return Inertia::render('Admin/Posts/Index', [
            'posts' => $posts,
            'filters' => ['status' => $status, 'q' => $request->input('q')],
        ]);
    }

    /**
     * POST /admin/posts/{post}/banear — banea la publicación con motivo opcional y redirige de vuelta.
     */
    public function ban(Request $request, Post $post): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $this->postAdminService->setStatus(auth()->user(), $post, 'banned', $request->input('reason'));

        return back()->with('success', 'Publicación baneada.');
    }

    /**
     * POST /admin/posts/{post}/publicar — publica la publicación y redirige de vuelta.
     */
    public function publish(Post $post): RedirectResponse
    {
        $this->postAdminService->setStatus(auth()->user(), $post, 'published');

        return back()->with('success', 'Publicación publicada.');
    }

    /**
     * POST /admin/posts/{post}/destacar — alterna el estado destacado y redirige de vuelta.
     */
    public function toggleFeatured(Post $post): RedirectResponse
    {
        $this->postAdminService->toggleFeatured(auth()->user(), $post);

        return back()->with('success', 'Destacado actualizado.');
    }

    /**
     * POST /admin/posts/{post}/sticky — alterna el estado fijado en el feed y redirige de vuelta.
     */
    public function toggleSticky(Post $post): RedirectResponse
    {
        $this->postAdminService->toggleSticky(auth()->user(), $post);

        return back()->with('success', 'Estado de fijado actualizado.');
    }
}
