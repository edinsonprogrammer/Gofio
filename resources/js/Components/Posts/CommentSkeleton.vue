<script setup>
/**
 * Esqueleto de hilos de comentarios con avatar, autor y acciones simuladas.
 */

import SkeletonBlock from '@/Components/UI/SkeletonBlock.vue';

defineProps({
    count: {
        type: Number,
        default: 3,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <!-- Lista de comentarios placeholder con espaciado normal o compacto -->
    <ul class="divide-y divide-fb-border" role="presentation" aria-hidden="true">
        <li v-for="n in count" :key="n" :class="compact ? 'px-4 py-3' : 'p-4'">
            <div class="flex gap-2">
                <SkeletonBlock class="h-8 w-8 shrink-0 rounded-full" />
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="flex items-center gap-2">
                        <SkeletonBlock class="h-3 w-24" />
                        <SkeletonBlock class="h-2 w-14" />
                    </div>
                    <SkeletonBlock class="h-3 w-full" />
                    <SkeletonBlock class="h-3 w-[80%]" />
                    <div class="flex gap-2 pt-1">
                        <SkeletonBlock class="h-3 w-10" />
                        <SkeletonBlock class="h-3 w-16" />
                    </div>
                </div>
            </div>
        </li>
    </ul>
</template>
