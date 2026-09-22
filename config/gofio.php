<?php

/**
 * Configuración específica de Gofio: comisiones, usuario plataforma y precios Creator Plus.
 */

return [
    'platform_fee_percent' => (float) env('GOFIO_PLATFORM_FEE', 10),
    'platform_username' => env('GOFIO_PLATFORM_USERNAME', '_gofio_platform'),
    'creator_plus_monthly_price' => (float) env('GOFIO_CREATOR_PLUS_PRICE', 50),
    'creator_plus_days' => (int) env('GOFIO_CREATOR_PLUS_DAYS', 30),
    // Máximo ajuste de karma (±) que un moderador staff puede aplicar por acción.
    'moderator_karma_delta_limit' => (int) env('GOFIO_MODERATOR_KARMA_DELTA', 500),
];
