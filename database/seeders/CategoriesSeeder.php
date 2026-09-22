<?php

/**
 * Seeder que crea las categorías temáticas del feed (Tecnología, Humor, etc.).
 */

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    /**
     * Crea diez categorías temáticas con nombre, slug e icono.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Películas y Series', 'icon' => 'fa-solid fa-tv'],
            ['name' => 'Tecnología', 'icon' => 'fa-solid fa-microchip'],
            ['name' => 'Ciencia y Apuntes', 'icon' => 'fa-solid fa-book'],
            ['name' => 'Arte e Imágenes', 'icon' => 'fa-solid fa-palette'],
            ['name' => 'Humor', 'icon' => 'fa-solid fa-face-laugh'],
            ['name' => 'Estilo de Vida', 'icon' => 'fa-solid fa-heart'],
            ['name' => 'Deportes', 'icon' => 'fa-solid fa-futbol'],
            ['name' => 'Música', 'icon' => 'fa-solid fa-music'],
            ['name' => 'Noticias', 'icon' => 'fa-solid fa-newspaper'],
            ['name' => 'Videojuegos', 'icon' => 'fa-solid fa-gamepad'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                ]
            );
        }
    }
}
