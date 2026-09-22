<?php

/**
 * Form Request de actualización de nick: valida el identificador público @nick del usuario.
 */

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNickRequest extends FormRequest
{
    /**
     * Permite el cambio de nick solo al usuario autenticado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el nick al formato estándar antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['nick' => User::normalizeNick($this->input('nick'))]);
    }

    /**
     * Reglas de validación para el nick: formato, longitud y unicidad.
     */
    public function rules(): array
    {
        return [
            'nick' => [
                'nullable',
                'string',
                'min:3',
                'max:20',
                'regex:/^[A-Za-z0-9_]+$/',
                Rule::unique('users', 'nick')->ignore($this->user()?->id),
            ],
        ];
    }

    /**
     * Mensajes de error personalizados en español para el cambio de nick.
     */
    public function messages(): array
    {
        return [
            'nick.regex' => 'El nick solo puede tener letras, números y guion bajo.',
            'nick.unique' => 'Ese @nick ya está en uso, elige otro.',
        ];
    }
}
