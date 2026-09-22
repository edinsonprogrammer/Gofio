<?php

/**
 * Service provider principal de la aplicación.
 * Registra servicios y configura el arranque de Gofio.
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    /**
     * Registra bindings de servicios en el contenedor de Laravel.
     */
    public function register(): void
    {

    }

    /**
     * Configura comportamiento global al arrancar la aplicación.
     */
    public function boot(): void
    {

    }
}
