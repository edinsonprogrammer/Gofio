<script setup>
/**
 * Bloque animado de carga (skeleton) reutilizable con forma y etiqueta HTML configurables.
 */

defineProps({
    tag: {
        type: String,
        default: 'div',
    },
    rounded: {
        type: String,
        default: 'md',
    },
    inline: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <!-- Placeholder pulsante que imita el tamaño del contenido real -->
    <component
        :is="tag"
        class="gofio-skeleton"
        :class="[
            rounded === 'full' ? 'rounded-full' : rounded === 'sm' ? 'rounded-sm' : 'rounded-md',
            inline ? 'inline-block' : 'block',
        ]"
    />
</template>
