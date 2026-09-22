<?php

/**
 * Form Request de envío de propinas: valida el monto a transferir al autor de una publicación.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTipRequest extends FormRequest
{
    /**
     * Permite enviar propinas a cualquier usuario autenticado con saldo suficiente.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el monto de la propina dentro del rango permitido.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
        ];
    }
}
