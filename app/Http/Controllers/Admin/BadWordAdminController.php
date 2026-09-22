<?php

/**
 * Controlador admin del filtro de palabras prohibidas: listado, alta y baja de términos.
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BadWord;
use App\Services\AdminLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BadWordAdminController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLogService,
    ) {}
    /**
     * GET /admin/palabras — responde con la vista Inertia Admin/BadWords/Index y el listado paginado.
     */
    public function index(): Response
    {
        // Renderiza el gestor de palabras filtradas
        return Inertia::render('Admin/BadWords/Index', [
            'words' => BadWord::orderBy('word')->paginate(30),
        ]);
    }

    /**
     * POST /admin/palabras — valida y registra una nueva palabra con su acción (bloquear o filtrar).
     */
    public function store(Request $request): RedirectResponse
    {
        // Valida palabra única y tipo de acción
        $data = $request->validate([
            'word' => ['required', 'string', 'max:80', 'unique:bad_words,word'],
            'action' => ['required', 'in:block,filter'],
        ]);

        BadWord::create($data);

        $this->adminLogService->log(auth()->user(), 'create_badword', 'badword', null, $data['word']);

        return back()->with('success', 'Palabra añadida.');
    }

    /**
     * DELETE /admin/palabras/{badWord} — elimina la palabra del filtro y redirige de vuelta.
     */
    public function destroy(BadWord $badWord): RedirectResponse
    {
        $word = $badWord->word;
        $badWord->delete();

        $this->adminLogService->log(auth()->user(), 'delete_badword', 'badword', null, $word);

        return back()->with('success', 'Palabra eliminada.');
    }
}
