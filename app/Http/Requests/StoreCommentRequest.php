<?php

/**
 * Form Request de creación de comentarios: valida texto, imagen adjunta y respuestas anidadas.
 */

namespace App\Http\Requests;

use App\Models\Post;
use App\Support\CommentMediaUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    /**
     * Permite comentar a cualquier usuario autenticado en rutas protegidas.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para contenido de texto, URL de imagen y comentario padre opcional.
     */
    public function rules(): array
    {
        $postId = null;
        $slug = $this->route('slug');

        if (is_string($slug) && $slug !== '') {
            $postId = Post::query()->where('slug', $slug)->value('id');
        }

        return [
            'content' => ['nullable', 'string', 'max:5000'],
            'image_url' => ['nullable', 'string', 'url', 'max:512'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('comments', 'id')->where(function ($query) use ($postId) {
                    if ($postId !== null) {
                        $query->where('post_id', $postId);
                    }
                }),
            ],
        ];
    }

    /**
     * Exige al menos texto o imagen: un comentario vacío no es válido.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $content = trim((string) $this->input('content', ''));
            $image = trim((string) $this->input('image_url', ''));

            if ($content === '' && $image === '') {
                $validator->errors()->add('content', 'Escribe un comentario o adjunta una imagen.');
            }

            if ($image !== '' && CommentMediaUrl::sanitize($image) === null) {
                $validator->errors()->add('image_url', 'La imagen adjunta debe ser un archivo subido a Gofio o un GIF de GIPHY.');
            }
        });
    }
}
