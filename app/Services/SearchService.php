<?php

namespace App\Services;

/**
 * Busca posts publicados y usuarios activos por término de texto con límites configurables.
 */

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;

class SearchService
{
    private const MIN_QUERY_LENGTH = 2;

    /**
     * Ejecuta búsqueda paralela en posts y usuarios; devuelve vacío si el término es demasiado corto.
     */
    public function search(string $query, int $postLimit = 20, int $userLimit = 10): array
    {
        $query = trim($query);

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return [
                'query' => $query,
                'posts' => collect(),
                'users' => collect(),
            ];
        }

        $term = ltrim($query, '@');
        $like = '%'.$term.'%';

        $posts = Post::query()
            ->published()
            ->with(['user.rango', 'category'])
            ->where(function ($builder) use ($like) {
                $builder->where('title', 'like', $like)
                    ->orWhere('tags', 'like', $like);
            })
            ->orderByDesc('points_count')
            ->orderByDesc('created_at')
            ->limit($postLimit)
            ->get();

        $users = User::query()
            ->where('is_active', true)
            ->where('is_banned', false)
            ->where(function ($builder) use ($like, $term) {
                $builder->where('username', 'like', $like)
                    ->orWhere('nick', 'like', $like);

                if ($term !== '') {
                    $builder->orWhere('nick', $term)
                        ->orWhere('username', $term);
                }
            })
            ->with('rango')
            ->orderBy('username')
            ->limit($userLimit)
            ->get();

        return [
            'query' => $query,
            'posts' => $posts,
            'users' => $users,
        ];
    }
}
