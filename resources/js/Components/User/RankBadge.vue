<script setup>
/**
 * Insignia visual del rango de un usuario con color, icono y efecto de brillo opcional.
 * Prioriza iconos del paquete activo sobre los definidos en el rango.
 */

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FaIcon from '@/Components/UI/FaIcon.vue';

const props = defineProps({
    rango: {
        type: Object,
        default: null,
    },
    size: {
        type: String,
        default: 'sm',
    },
    shine: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();

/** Icono final: override del paquete activo o icono del rango. */
const icon = computed(() => {
    const override = props.rango?.slug && page.props.iconPack?.ranks?.[props.rango.slug];
    return override || props.rango?.icon;
});
</script>

<template>
    <!-- Pastilla con color del rango, icono y nombre -->
    <span
        v-if="rango"
        class="rank-badge"
        :class="{
            'rank-badge--lg': size === 'lg',
            'rank-badge--shine': shine,
        }"
        :style="{ '--rank-color': rango.color, color: rango.color, backgroundColor: `${rango.color}18` }"
        :title="`Rango: ${rango.nombre}`"
    >
        <span v-if="shine" class="rank-badge__sheen" aria-hidden="true" />
        <FaIcon :icon="icon" class="rank-badge__icon" />
        <span class="rank-badge__label">{{ rango.nombre }}</span>
    </span>
</template>

<style scoped>
.rank-badge {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    overflow: hidden;
    border-radius: 9999px;
    padding: 0.25rem 0.65rem;
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1.2;
}

.rank-badge--lg {
    padding: 0.35rem 0.85rem;
    font-size: 0.875rem;
}

.rank-badge__icon {
    position: relative;
    z-index: 1;
    font-size: 0.85em;
}

.rank-badge__label {
    position: relative;
    z-index: 1;
}

.rank-badge--shine {
    border: 1px solid color-mix(in srgb, var(--rank-color) 38%, transparent);
    box-shadow:
        0 2px 10px color-mix(in srgb, var(--rank-color) 22%, transparent),
        inset 0 1px 0 rgba(255, 255, 255, 0.42);
}

.rank-badge__sheen {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        105deg,
        transparent 38%,
        rgba(255, 255, 255, 0.28) 50%,
        transparent 62%
    );
    transform: translateX(-120%);
    animation: rank-badge-shine 4s ease-in-out infinite;
    pointer-events: none;
}

@keyframes rank-badge-shine {
    0%, 72%, 100% {
        transform: translateX(-120%);
    }

    88% {
        transform: translateX(120%);
    }
}
</style>
