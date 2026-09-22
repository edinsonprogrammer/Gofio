<?php

/**
 * Controlador de la página de inicio: renderiza el feed principal con las categorías disponibles.
 */

namespace App\Http\Controllers;

use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * GET / — responde con la vista Inertia Home y el listado de categorías.
     */
    public function __invoke(): Response
    {
        // Renderiza la página principal del feed
        return Inertia::render('Home', [
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug', 'icon']),
        ]);
    }
}
