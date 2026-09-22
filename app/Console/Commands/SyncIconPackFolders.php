<?php

/**
 * Comando Artisan que escanea /icon-packs e importa o actualiza paquetes de iconos.
 */

namespace App\Console\Commands;

use App\Services\IconPackService;
use Illuminate\Console\Command;

class SyncIconPackFolders extends Command
{
    protected $signature = 'gofio:icon-packs:sync';

    protected $description = 'Escanea la carpeta /icon-packs e instala o actualiza los paquetes de iconos encontrados.';

    /**
     * Escanea carpetas de icon packs y sincroniza registros en la base de datos.
     */
    public function handle(IconPackService $iconPackService): int
    {
        $result = $iconPackService->syncFolderPacks();

        $this->info("Carpeta escaneada: {$result['path']}");

        if ($result['total'] === 0) {
            $this->warn('No se encontraron paquetes de iconos válidos (cada carpeta necesita un icon-pack.json).');

            return self::SUCCESS;
        }

        foreach ($result['created'] as $name) {
            $this->line("  + Instalado: {$name}");
        }

        foreach ($result['updated'] as $name) {
            $this->line("  ~ Actualizado: {$name}");
        }

        $this->info("Listo. {$result['total']} paquete(s) procesado(s).");

        return self::SUCCESS;
    }
}
