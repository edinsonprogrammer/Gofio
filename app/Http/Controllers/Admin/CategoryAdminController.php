<?php

/**
 * Controlador admin de categorías de contenido: creación, edición y eliminación.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AdminLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryAdminController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLogService,
    ) {}

    /**
     * GET /admin/categorias — responde con la vista Inertia Admin/Categories/Index y el listado completo.
     */
    public function index(): Response
    {
        // Renderiza el gestor de categorías
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::orderBy('name')->paginate(25),
        ]);
    }

    /**
     * POST /admin/categorias — valida y crea una nueva categoría; redirige de vuelta con mensaje de éxito.
     */
    public function store(Request $request): RedirectResponse
    {
        // Valida nombre único e icono obligatorio
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:categories,name'],
            'icon' => ['required', 'string', 'max:191'],
        ]);

        Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $data['icon'],
        ]);

        $this->adminLogService->log(auth()->user(), 'create_category', 'category', null, $data['name']);

        return back()->with('success', 'Categoría creada.');
    }

    /**
     * PUT /admin/categorias/{category} — valida y actualiza la categoría; redirige de vuelta.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        // Valida nombre único excluyendo la categoría actual
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:categories,name,'.$category->id],
            'icon' => ['required', 'string', 'max:191'],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $data['icon'],
        ]);

        $this->adminLogService->log(auth()->user(), 'update_category', 'category', $category->id, $data['name']);

        return back()->with('success', 'Categoría actualizada.');
    }

    /**
     * DELETE /admin/categorias/{category} — elimina la categoría si no tiene posts asociados.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->posts()->exists()) {
            return back()->with('error', 'No se puede eliminar: tiene posts asociados.');
        }

        $name = $category->name;
        $category->delete();

        $this->adminLogService->log(auth()->user(), 'delete_category', 'category', null, $name);

        return back()->with('success', 'Categoría eliminada.');
    }
}
