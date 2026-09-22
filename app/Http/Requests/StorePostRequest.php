<?php

/**
 * Form Request de creación de publicaciones: valida título, categoría y bloques de contenido.
 */

namespace App\Http\Requests;

use App\Services\PostPermissionService;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Requiere un usuario autenticado para crear publicaciones.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación adaptadas a los permisos de publicación del rango del usuario.
     */
    public function rules(): array
    {
        $permissions = app(PostPermissionService::class)->forUser($this->user());
        $maxTitle = (int) ($permissions['max_title_length'] ?? 120);

        return [
            'title' => ['required', 'string', 'min:3', 'max:'.$maxTitle],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'content' => ['required', 'array'],
            'content.blocks' => ['required', 'array', 'min:1'],
            'status' => ['sometimes', 'in:draft,published'],
            'tags' => ['nullable', 'string', 'max:128'],
            'block_comments' => ['sometimes', 'boolean'],
            'is_private' => ['sometimes', 'boolean'],
        ];
    }
}
