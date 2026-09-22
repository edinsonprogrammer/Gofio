<?php

/**
 * Seeder que crea posts de bienvenida y ejemplo para el usuario admin.
 */

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostsSeeder extends Seeder
{
    /**
     * Crea posts de bienvenida y consejos de karma para el usuario admin.
     */
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();

        if (! $admin) {
            return;
        }

        $techCategory = Category::where('slug', 'tecnologia')->first();
        $humorCategory = Category::where('slug', 'humor')->first();

        if (! $techCategory || ! $humorCategory) {
            return;
        }

        $posts = [
            [
                'title' => '¡Bienvenido a Gofio!',
                'slug' => 'bienvenido-a-gofio',
                'category_id' => $techCategory->id,
                'points_count' => 15,
                'content' => [
                    'blocks' => [
                        ['type' => 'header', 'data' => ['text' => 'Tu nueva red social', 'level' => 2]],
                        ['type' => 'paragraph', 'data' => ['text' => 'Gofio combina el espíritu comunitario de las redes clásicas con un diseño inspirado en Facebook 2008-2010. Publica, comenta y gana karma.']],
                        ['type' => 'paragraph', 'data' => ['text' => 'Esta es la Fase 2: editor de bloques, feed infinito, votos y comentarios anidados.']],
                    ],
                ],
            ],
            [
                'title' => 'Tips para ganar karma',
                'slug' => 'tips-para-ganar-karma',
                'category_id' => $humorCategory->id,
                'points_count' => 8,
                'content' => [
                    'blocks' => [
                        ['type' => 'paragraph', 'data' => ['text' => 'Publica contenido de calidad, participa en la comunidad y recibe votos de otros usuarios.']],
                        ['type' => 'code', 'data' => ['code' => "console.log('¡Hola Gofio!');"]],
                    ],
                ],
            ],
        ];

        foreach ($posts as $postData) {
            Post::updateOrCreate(
                ['slug' => $postData['slug']],
                array_merge($postData, [
                    'user_id' => $admin->id,
                    'comments_count' => 0,
                    'views_count' => rand(10, 100),
                    'status' => 'published',
                ])
            );
        }
    }
}
