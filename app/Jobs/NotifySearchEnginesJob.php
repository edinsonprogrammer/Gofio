<?php

/**
 * Job en cola que notifica a buscadores (IndexNow) tras publicar contenido indexable.
 */

namespace App\Jobs;

use App\Services\SeoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class NotifySearchEnginesJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $url  URL absoluta indexable.
     * @param  string  $type  Tipo de recurso (post, profile, etc.).
     * @param  int|null  $referenceId  ID de referencia opcional para logs.
     */
    public function __construct(
        public string $url,
        public string $type = 'url',
        public ?int $referenceId = null,
    ) {}

    /**
     * Ejecuta el ping a IndexNow y servicios compatibles.
     */
    public function handle(SeoService $seoService): void
    {
        $seoService->submitUrlToSearchEngines($this->url);
    }
}
