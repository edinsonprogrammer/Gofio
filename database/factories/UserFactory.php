<?php

/**
 * Factory de Eloquent para generar usuarios de prueba con datos ficticios.
 */

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Genera atributos ficticios para un usuario de prueba.
     */
    public function definition(): array
    {
        $username = fake()->unique()->userName();

        return [
            'username' => $username,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'rango_id' => 1,
            'karma' => fake()->numberBetween(0, 500),
            'balance_monedas' => 0,
            'tipo_verificacion' => 'none',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Estado de factory que deja email_verified_at en null.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
