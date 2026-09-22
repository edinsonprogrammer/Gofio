<?php

/**
 * Form Request de actualización de perfil: valida datos personales, redes sociales y cambio de contraseña.
 */

namespace App\Http\Requests\Settings;

use App\Models\User;
use App\Support\GiphyUrl;
use App\Support\StorageMediaUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Permite la edición solo al usuario autenticado dueño del perfil.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza campos vacíos a null y formatea el nick antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $nullable = ['country', 'country_code', 'age', 'bio', 'bio_gif_url', 'whatsapp', 'instagram', 'facebook', 'social_x', 'password', 'password_confirmation', 'current_password'];

        $merged = ['nick' => User::normalizeNick($this->input('nick'))];

        foreach ($nullable as $field) {
            if ($this->input($field) === '') {
                $merged[$field] = null;
            }
        }

        if (isset($merged['bio']) || $this->has('bio')) {
            $merged['bio'] = isset($merged['bio']) && $merged['bio'] === null
                ? null
                : trim((string) $this->input('bio'));
        }

        $this->merge($merged);
    }

    /**
     * Reglas de validación para perfil, contacto, redes sociales e imágenes de avatar y banner.
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
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->id),
            ],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'country' => ['nullable', 'string', 'max:80'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'age' => ['nullable', 'integer', 'min:13', 'max:120'],
            'bio' => ['nullable', 'string', 'max:300'],
            'bio_gif_url' => ['nullable', 'string', 'max:512'],
            'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s+\-()]+$/'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'facebook' => ['nullable', 'string', 'max:100'],
            'social_x' => ['nullable', 'string', 'max:100'],
            'avatar_url' => ['nullable', 'string', 'url', 'max:512'],
            'banner_url' => ['nullable', 'string', 'url', 'max:512'],
            // Posición del banner en porcentaje (0–100).
            'banner_offset_x' => ['nullable', 'integer', 'min:0', 'max:100'],
            'banner_offset_y' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * Valida que la URL del GIF de biografía provenga de la CDN oficial de GIPHY.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $gifUrl = trim((string) $this->input('bio_gif_url', ''));

            if ($gifUrl !== '' && ! GiphyUrl::isAllowed($gifUrl)) {
                $validator->errors()->add('bio_gif_url', 'El GIF de biografía debe ser una URL válida de GIPHY.');
            }

            $avatarUrl = trim((string) $this->input('avatar_url', ''));

            if ($avatarUrl !== '' && StorageMediaUrl::sanitizeStorageOnly($avatarUrl) === null) {
                $validator->errors()->add('avatar_url', 'La URL del avatar debe apuntar a un archivo almacenado en este sitio.');
            }

            $bannerUrl = trim((string) $this->input('banner_url', ''));

            if ($bannerUrl !== '' && StorageMediaUrl::sanitizeStorageOnly($bannerUrl) === null) {
                $validator->errors()->add('banner_url', 'La URL del banner debe apuntar a un archivo almacenado en este sitio.');
            }
        });
    }

    /**
     * Mensajes de error personalizados en español para el formulario de perfil.
     */
    public function messages(): array
    {
        return [
            'nick.regex' => 'El nick solo puede tener letras, números y guion bajo.',
            'nick.unique' => 'Ese @nick ya está en uso, elige otro.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'whatsapp.regex' => 'El WhatsApp solo puede contener números y símbolos de teléfono.',
        ];
    }
}
