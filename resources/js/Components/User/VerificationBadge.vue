<script setup>
/**
 * Insignia SVG de verificación de cuenta: distingue entre usuario verificado
 * y suscriptor Creator Plus con distintos colores y tamaños.
 */

defineProps({
    tipo: {
        type: String,
        default: 'none',
    },
    size: {
        type: String,
        default: 'sm',
    },
});

/** Mapa de tamaños en píxeles según la variante solicitada. */
const sizePx = {
    xs: 14,
    sm: 16,
    md: 20,
    lg: 28,
    xl: 34,
    title: 24,
};

/** Trayectoria SVG compartida del distintivo tipo rosette con check. */
const rosettePath = 'M22.5 12c0-1.58-.875-2.95-2.148-3.6.154-.435.238-.905.238-1.4 0-2.21-1.71-3.998-3.818-3.998-.47 0-.92.084-1.336.25C14.818 2.415 13.51 1.5 12 1.5s-2.816.917-3.437 2.25c-.415-.166-.866-.25-1.336-.25-2.11 0-3.818 1.79-3.818 4 0 .494.083.964.237 1.4-1.272.65-2.147 2.018-2.147 3.6 0 1.495.782 2.798 1.942 3.486-.02.17-.032.34-.032.514 0 2.21 1.708 4 3.818 4 .47 0 .92-.086 1.335-.25.62 1.334 1.926 2.25 3.437 2.25 1.512 0 2.818-.916 3.437-2.25.415.163.865.248 1.336.248 2.11 0 3.818-1.79 3.818-4 0-.174-.012-.344-.033-.513 1.158-.687 1.943-1.99 1.943-3.484zm-6.616-3.334l-4.334 6.5c-.145.217-.382.334-.625.334-.143 0-.288-.04-.416-.126l-.115-.094-2.415-2.415c-.293-.293-.293-.767 0-1.06s.767-.294 1.06 0l1.877 1.877 3.778-5.667c.235-.353.708-.448 1.06-.213.353.235.447.708.213 1.06z';
</script>

<template>
    <!-- Distintivo azul degradado para suscriptores Creator Plus -->
    <span
        v-if="tipo === 'creator_plus'"
        class="inline-flex shrink-0 items-center align-middle"
        :class="size === 'title' ? 'ml-1' : 'ml-1.5'"
        title="Creator Plus"
    >
        <svg
            :width="sizePx[size]"
            :height="sizePx[size]"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <defs>
                <linearGradient :id="`creator-blue-${size}`" x1="15%" y1="0%" x2="85%" y2="100%">
                    <stop offset="0%" stop-color="#93C5FD" />
                    <stop offset="35%" stop-color="#1877F2" />
                    <stop offset="100%" stop-color="#1E3A8A" />
                </linearGradient>
            </defs>
            <path :fill="`url(#creator-blue-${size})`" :d="rosettePath" />
        </svg>
    </span>

    <!-- Distintivo cian para cuentas verificadas estándar -->
    <span
        v-else-if="tipo === 'user_verified'"
        class="inline-flex shrink-0 items-center align-middle"
        :class="size === 'title' ? 'ml-1' : 'ml-1.5'"
        title="Cuenta verificada"
    >
        <svg
            :width="sizePx[size]"
            :height="sizePx[size]"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <path fill="#00EAFF" :d="rosettePath" />
        </svg>
    </span>
</template>
