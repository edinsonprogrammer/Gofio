<?php

/**
 * Form Request de actualización administrativa de usuarios.
 * Restringe campos editables según administrador global o moderador con pestaña usuarios.
 */

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserAdminRequest extends FormRequest
{
    /**
     * Permite la acción a admins globales o staff con acceso a la pestaña usuarios.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isAdmin() || $user->canAccessAdminTab('users'));
    }

    /**
     * Normaliza el nick antes de validar cuando viene en la petición.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('nick')) {
            return;
        }

        $this->merge(['nick' => (new User)->normalizeNick($this->input('nick'))]);
    }

    /**
     * Reglas de validación: campos completos para admin global; moderación limitada para staff.
     */
    public function rules(): array
    {
        $target = $this->route('user');
        $targetId = $target instanceof User ? $target->id : $target;

        $nickRules = [
            'nullable',
            'string',
            'min:3',
            'max:20',
            'regex:/^[A-Za-z0-9_]+$/',
            Rule::unique('users', 'nick')->ignore($targetId),
        ];

        if ($this->user()?->isAdmin()) {
            return [
                'karma' => ['nullable', 'integer', 'min:0'],
                'balance_monedas' => ['nullable', 'numeric', 'min:0'],
                'rango_id' => ['nullable', 'integer', 'exists:roles_rangos,id'],
                'tipo_verificacion' => ['nullable', 'in:none,user_verified,creator_plus'],
                'is_admin' => ['nullable', 'boolean'],
                'is_active' => ['nullable', 'boolean'],
                'nick' => $nickRules,
            ];
        }

        return [
            'karma' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'nick' => $nickRules,
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'nick.regex' => 'El nick solo puede tener letras, números y guion bajo.',
            'nick.unique' => 'Ese @nick ya está en uso, elige otro.',
        ];
    }
}
