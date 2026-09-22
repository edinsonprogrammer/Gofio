<?php

/**
 * Comando Artisan que persiste en base de datos las visitas de posts acumuladas en caché.
 */

namespace App\Console\Commands;

use App\Services\PostViewBufferService;
use Illuminate\Console\Command;

class FlushPostViewsCommand extends Command
{
    protected $signature = 'gofio:flush-post-views';

    protected $description = 'Persist buffered post view counts from cache to the database';

    /**
     * Vuelca contadores de visitas desde caché hacia la tabla posts.
     */
    public function handle(PostViewBufferService $buffer): int
    {
        $flushed = $buffer->flush();

        $this->info("Flushed {$flushed} buffered post views.");

        return self::SUCCESS;
    }
}
