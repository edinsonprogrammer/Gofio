<script setup>
/**
 * Esqueleto de filas de lista con avatar y líneas de texto para estados de carga.
 */

import SkeletonBlock from '@/Components/UI/SkeletonBlock.vue';

/** Cantidad de filas placeholder a renderizar. */
defineProps({
    count: {
        type: Number,
        default: 4,
    },
});
</script>

<template>
    <!-- Lista de filas simuladas con avatar circular y tres líneas de texto -->
    <ul class="space-y-2 p-2" role="presentation" aria-hidden="true">
        <li v-for="n in count" :key="n" class="flex gap-3 rounded-lg px-2 py-2">
            <SkeletonBlock class="h-9 w-9 shrink-0 rounded-full" />
            <div class="min-w-0 flex-1 space-y-2">
                <SkeletonBlock class="h-3 w-32" />
                <SkeletonBlock class="h-2 w-full" />
                <SkeletonBlock class="h-2 w-[66%]" />
            </div>
        </li>
    </ul>
</template>
