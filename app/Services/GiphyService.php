<?php

namespace App\Services;

/**
 * Cliente de la API de GIPHY para búsqueda y tendencias (proxy seguro desde el backend).
 */

use Illuminate\Support\Facades\Http;

class GiphyService
{
    /**
     * Devuelve GIFs en tendencia normalizados para el selector del frontend.
     */
    public function trending(int $limit = 24, int $offset = 0): array
    {
        return $this->request('/v1/gifs/trending', [
            'limit' => min(max($limit, 1), 50),
            'offset' => max($offset, 0),
            'rating' => 'g',
        ]);
    }

    /**
     * Busca GIFs por texto y devuelve resultados normalizados.
     */
    public function search(string $query, int $limit = 24, int $offset = 0): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        return $this->request('/v1/gifs/search', [
            'q' => $query,
            'limit' => min(max($limit, 1), 50),
            'offset' => max($offset, 0),
            'rating' => 'g',
            'lang' => 'es',
        ]);
    }

    /**
     * Indica si la integración está configurada con API key.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.giphy.api_key'));
    }

    /**
     * Ejecuta una petición a GIPHY y mapea la respuesta al formato interno de Gofio.
     */
    private function request(string $path, array $query): array
    {
        $apiKey = config('services.giphy.api_key');

        if (! $apiKey) {
            return [];
        }

        $response = Http::timeout(8)
            ->acceptJson()
            ->get('https://api.giphy.com'.$path, array_merge($query, [
                'api_key' => $apiKey,
            ]));

        if (! $response->successful()) {
            return [];
        }

        $items = $response->json('data');

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => $this->mapGif(is_array($item) ? $item : []),
            $items,
        )));
    }

    /**
     * Extrae URL segura, vista previa e identificador de un ítem de la API de GIPHY.
     */
    private function mapGif(array $item): ?array
    {
        $id = (string) ($item['id'] ?? '');

        if ($id === '') {
            return null;
        }

        $images = is_array($item['images'] ?? null) ? $item['images'] : [];
        $url = $this->pickImageUrl($images);

        if ($url === '') {
            return null;
        }

        $preview = $this->pickPreviewUrl($images) ?: $url;

        return [
            'id' => $id,
            'title' => mb_substr(trim(strip_tags((string) ($item['title'] ?? 'GIF'))), 0, 120) ?: 'GIF',
            'url' => $url,
            'preview_url' => $preview,
            'width' => (int) ($images['fixed_height']['width'] ?? $images['downsized']['width'] ?? 0),
            'height' => (int) ($images['fixed_height']['height'] ?? $images['downsized']['height'] ?? 0),
        ];
    }

    /**
     * Prioriza un tamaño equilibrado para incrustar en posts y comentarios.
     */
    private function pickImageUrl(array $images): string
    {
        foreach (['fixed_height', 'downsized_medium', 'downsized', 'original'] as $key) {
            $candidate = trim((string) ($images[$key]['url'] ?? ''));

            if ($candidate !== '' && \App\Support\GiphyUrl::isAllowed($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * Selecciona una imagen estática o ligera para la cuadrícula del buscador.
     */
    private function pickPreviewUrl(array $images): string
    {
        foreach (['fixed_height_still', 'downsized_still', 'preview_gif'] as $key) {
            $candidate = trim((string) ($images[$key]['url'] ?? ''));

            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_URL)) {
                return $candidate;
            }
        }

        return '';
    }
}
