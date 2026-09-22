<?php

/**
 * Form Request de solicitud de verificación de identidad: valida datos personales y documento adjunto.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitVerificationRequest extends FormRequest
{
    /**
     * Permite enviar la solicitud al usuario autenticado que aún no está verificado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para nombre completo, tipo de documento y archivo adjunto.
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'document_type' => ['required', Rule::in(['id_card', 'passport', 'driver_license'])],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'user_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mensajes de error personalizados en español para la carga del documento.
     */
    public function messages(): array
    {
        return [
            'document.required' => 'Debes adjuntar un documento de identidad.',
            'document.mimes' => 'El documento debe ser JPG, PNG o PDF.',
        ];
    }
}
