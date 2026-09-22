<?php

/**
 * Procesa, redimensiona y almacena el logo del sitio (reemplazable sin duplicar archivos).
 */

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SiteLogoService
{
    /** Alto máximo alineado al wordmark del header (~2.15rem). */
    private const MAX_HEIGHT = 36;

    /** Ancho máximo para que quepa junto al buscador. */
    private const MAX_WIDTH = 200;

    private const STORAGE_DIR = 'site';

    /**
     * Guarda un logo nuevo, eliminando cualquier versión anterior.
     */
    public function store(UploadedFile $file): string
    {
        $this->deleteStoredFiles();

        if ($this->isSvg($file)) {
            $path = $file->storeAs(self::STORAGE_DIR, 'logo.svg', 'public');

            return Storage::disk('public')->url($path);
        }

        $relativePath = self::STORAGE_DIR.'/logo.png';
        $this->processRasterToPng($file, $relativePath);

        return Storage::disk('public')->url($relativePath);
    }

    /**
     * Elimina archivos de logo del disco público.
     */
    public function deleteStoredFiles(): void
    {
        $disk = Storage::disk('public');

        foreach (['logo.png', 'logo.svg', 'logo.webp', 'logo.jpg', 'logo.jpeg', 'logo.gif', 'logo.ico'] as $name) {
            $path = self::STORAGE_DIR.'/'.$name;

            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    private function isSvg(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $extension = strtolower((string) $file->getClientOriginalExtension());

        return $mime === 'image/svg+xml' || $extension === 'svg';
    }

    /**
     * Redimensiona una imagen raster manteniendo proporción y la guarda como PNG.
     */
    private function processRasterToPng(UploadedFile $file, string $relativePath): void
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer la imagen del logo.');
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('Formato de imagen no soportado o archivo corrupto.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 1 || $height < 1) {
            imagedestroy($image);

            throw new RuntimeException('La imagen del logo no tiene dimensiones válidas.');
        }

        [$newWidth, $newHeight] = $this->fitDimensions($width, $height);

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        $fullPath = Storage::disk('public')->path($relativePath);
        $directory = dirname($fullPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            imagedestroy($canvas);

            throw new RuntimeException('No se pudo crear la carpeta del logo.');
        }

        if (! imagepng($canvas, $fullPath, 9)) {
            imagedestroy($canvas);

            throw new RuntimeException('No se pudo guardar el logo procesado.');
        }

        imagedestroy($canvas);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function fitDimensions(int $width, int $height): array
    {
        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height, 1);

        return [
            max(1, (int) round($width * $ratio)),
            max(1, (int) round($height * $ratio)),
        ];
    }
}
