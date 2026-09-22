<?php

/**
 * Form Request de registro: valida los datos de creación de una nueva cuenta de usuario.
 */

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Permite el registro a cualquier visitante cuando la ruta está habilitada.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza el nick antes de aplicar las reglas de validación.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('nick')) {
            $this->merge(['nick' => User::normalizeNick($this->input('nick'))]);
        }
    }

    /**
     * Reglas de validación para username, nick, email y contraseña con confirmación.
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:20', 'alpha_dash', Rule::unique('users', 'username')],
            'nick' => ['nullable', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9_]+$/', Rule::unique('users', 'nick')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Mensajes de error personalizados en español para el formulario de registro.
     */
    public function messages(): array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'nick.regex' => 'El nick solo puede tener letras, números y guion bajo.',
            'nick.unique' => 'Ese @nick ya está en uso, elige otro.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
