<script setup>
/**
 * Renderizador unificado de iconos: soporta clases Font Awesome
 * e imágenes de paquetes personalizados del sistema Gofio.
 */

import { computed, useAttrs } from 'vue';
import { isPackIcon, packIconUrl } from '@/utils/gofioIcon';
import { normalizeFaIcon } from '@/utils/faIcon';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    icon: {
        type: String,
        default: 'fa-solid fa-circle',
    },
    variant: {
        type: String,
        default: 'auto',
    },
    alt: {
        type: String,
        default: '',
    },
});

const attrs = useAttrs();

/** Clases FA normalizadas cuando el icono no proviene de un paquete. */
const classes = computed(() => normalizeFaIcon(props.icon, props.variant));

/** Indica si el identificador corresponde a un asset de paquete de iconos. */
const isImage = computed(() => isPackIcon(props.icon));

/** URL pública del asset cuando es un icono de paquete. */
const imageSrc = computed(() => packIconUrl(props.icon));

/** Combina clases del componente con las recibidas por atributo (class). */
const mergedClass = computed(() => {
    if (isImage.value) {
        return attrs.class;
    }

    return [classes.value, attrs.class];
});
</script>

<template>
    <!-- Imagen de paquete personalizado o elemento <i> de Font Awesome -->
    <img
        v-if="isImage"
        :src="imageSrc"
        :alt="alt"
        class="gofio-icon-img"
        :class="mergedClass"
    />
    <i v-else :class="mergedClass" aria-hidden="true" />
</template>

<style scoped>
.gofio-icon-img {
    display: inline-block;
    width: 1em;
    height: 1em;
    object-fit: contain;
    vertical-align: -0.125em;
}
</style>
