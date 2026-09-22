<?php

/**
 * Controlador de búsqueda global: consulta posts y usuarios por término de búsqueda.
 */

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\PostService;
use App\Services\RankService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly PostService $postService,
        private readonly RankService $rankService,
    ) {}

    /**
     * GET /buscar?q= — responde con la vista Inertia Search/Index y resultados de posts y usuarios.
     */
    public function index(Request $request): Response
    {
        $query = (string) $request->input('q', '');
        $results = $this->searchService->search($query);

        // Renderiza la página de resultados de búsqueda
        return Inertia::render('Search/Index', [
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug', 'icon']),
            'query' => $results['query'],
            'posts' => $results['posts']->map(fn ($post) => $this->postService->formatPost($post))->values(),
            'users' => $results['users']->map(fn ($user) => [
                'id' => $user->id,
                'username' => $user->username,
                'nick' => $user->nick,
                'karma' => $user->karma,
                'avatar_url' => $user->avatar_url,
                'rango' => $this->rankService->formatRango($user->rango),
            ])->values(),
            'minLength' => 2,
        ]);
    }
}
