<?php

/**
 * Form Request de envío de mensajes de chat: valida el cuerpo y el tipo de mensaje.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Permite enviar mensajes a participantes autenticados de una conversación.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el texto del mensaje y el tipo opcional (texto, imagen o enlace).
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['sometimes', 'in:text,image,link'],
        ];
    }
}
