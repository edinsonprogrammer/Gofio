<?php

/**
 * Form Request de inicio de sesión: valida credenciales de acceso al formulario de login.
 */

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Permite el intento de login a cualquier visitante no autenticado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para correo/usuario, contraseña y opción recordar sesión.
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensajes de error personalizados en español para el formulario de login.
     */
    public function messages(): array
    {
        return [
            'login.required' => 'Ingresa tu correo o nombre de usuario.',
            'password.required' => 'Ingresa tu contraseña.',
        ];
    }
}
