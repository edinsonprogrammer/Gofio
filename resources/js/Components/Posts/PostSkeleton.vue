<script setup>
/**
 * Placeholder de carga que simula la estructura de una tarjeta de publicación
 * (cabecera, cuerpo y barra de acciones).
 */

import SkeletonBlock from '@/Components/UI/SkeletonBlock.vue';
</script>

<template>
    <div class="gofio-box overflow-hidden" role="presentation" aria-hidden="true">
        <!-- Cabecera con avatar y metadatos del autor -->
        <div class="border-b border-fb-border bg-[#F5F6F7] px-4 py-3">
            <div class="flex items-center gap-2">
                <SkeletonBlock class="h-8 w-8 rounded-full" />
                <div class="space-y-2">
                    <SkeletonBlock class="h-3 w-24" />
                    <SkeletonBlock class="h-2 w-32" />
                </div>
            </div>
        </div>
        <!-- Cuerpo con título y párrafos simulados -->
        <div class="space-y-3 p-4">
            <SkeletonBlock class="h-5 w-3/4" />
            <SkeletonBlock class="h-3 w-full" />
            <SkeletonBlock class="h-3 w-full" />
            <SkeletonBlock class="h-3 w-[66%]" />
        </div>
        <!-- Barra inferior de reacciones -->
        <div class="border-t border-fb-border bg-[#F5F6F7] px-4 py-2">
            <SkeletonBlock class="h-4 w-28" />
        </div>
    </div>
</template>
