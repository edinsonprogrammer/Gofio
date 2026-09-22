<?php

/**
 * Comando Artisan que escanea /themes e importa o actualiza temas en la base de datos.
 */

namespace App\Console\Commands;

use App\Services\ThemeService;
use Illuminate\Console\Command;

class SyncThemeFolders extends Command
{
    protected $signature = 'gofio:themes:sync';

    protected $description = 'Escanea la carpeta /themes e instala o actualiza los temas encontrados en la base de datos.';

    /**
     * Escanea carpetas de temas y sincroniza registros en la base de datos.
     */
    public function handle(ThemeService $themeService): int
    {
        $result = $themeService->syncFolderThemes();

        $this->info("Carpeta escaneada: {$result['path']}");

        if ($result['total'] === 0) {
            $this->warn('No se encontraron carpetas de tema válidas (cada carpeta necesita un theme.json).');

            return self::SUCCESS;
        }

        foreach ($result['created'] as $name) {
            $this->line("  + Instalado: {$name}");
        }

        foreach ($result['updated'] as $name) {
            $this->line("  ~ Actualizado: {$name}");
        }

        $this->info("Listo. {$result['total']} tema(s) procesado(s).");

        return self::SUCCESS;
    }
}
