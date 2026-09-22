<?php

/**
 * Form Request de reacciones a publicaciones: valida el tipo de reacción permitido.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReactPostRequest extends FormRequest
{
    /**
     * Requiere un usuario autenticado para reaccionar a publicaciones.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación para el identificador de reacción entre los tipos soportados.
     */
    public function rules(): array
    {
        return [
            'reaction' => ['required', 'string', 'in:like,excelente,lindo,desacuerdo,asombroso'],
        ];
    }
}
