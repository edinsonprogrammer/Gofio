<?php

namespace App\Repositories;

/**
 * Consultas y operaciones de persistencia sobre el modelo de usuarios.
 */

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    /**
     * Busca un usuario activo por su dirección de correo electrónico.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Busca un usuario por su nombre de usuario único.
     */
    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    /**
     * Obtiene un usuario por ID incluyendo su relación de rango.
     */
    public function findById(int $id): ?User
    {
        return User::with('rango')->find($id);
    }

    /**
     * Crea y persiste un nuevo registro de usuario con los datos proporcionados.
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Comprueba si ya existe un usuario con el username indicado.
     */
    public function usernameExists(string $username): bool
    {
        return User::where('username', $username)->exists();
    }

    /**
     * Comprueba si ya existe un usuario con el email indicado.
     */
    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Devuelve usuarios sugeridos ordenados por karma descendente, paginados.
     */
    public function getSuggestedUsers(int $limit = 5): LengthAwarePaginator
    {
        return User::with('rango')
            ->orderByDesc('karma')
            ->paginate($limit);
    }
}
