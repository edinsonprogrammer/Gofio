<?php

/**
 * Comandos Artisan personalizados y tareas programadas del scheduler de Gofio.
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Comando de demostración incluido con Laravel
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas de mantenimiento automático de la plataforma
Schedule::command('gofio:expire-subscriptions')->daily();
Schedule::command('gofio:prune-notifications')->daily();
Schedule::command('gofio:flush-post-views')->everyMinute();
Schedule::command('gofio:seo:sitemap')->hourly();
