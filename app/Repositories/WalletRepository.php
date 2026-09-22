<?php

namespace App\Repositories;

/**
 * Operaciones de billetera: cuenta plataforma, transacciones y historial por usuario.
 */

use App\Models\User;
use App\Models\WalletTransaction;

class WalletRepository
{
    /**
     * Localiza la cuenta de usuario configurada como receptora de comisiones de plataforma.
     */
    public function findPlatformUser(): ?User
    {
        return User::where('username', config('gofio.platform_username'))->first();
    }

    /**
     * Registra una nueva transacción de billetera con los datos del movimiento.
     */
    public function createTransaction(array $data): WalletTransaction
    {
        return WalletTransaction::create($data);
    }

    /**
     * Obtiene las transacciones recientes donde el usuario participa como emisor o receptor.
     */
    public function getTransactionsForUser(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return WalletTransaction::query()
            ->where(fn ($q) => $q->where('sender_id', $userId)->orWhere('receiver_id', $userId))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
