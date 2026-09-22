<script setup>
/**
 * Administración de paquetes de iconos personalizados subidos al sistema.
 */

import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

defineProps({
    iconPacks: { type: Array, default: () => [] },
    iconPacksPath: { type: String, default: '' },
});

const syncing = ref(false);
const expandedSlug = ref(null);


/** Sincroniza las carpetas del paquete de iconos con el servidor. */
const syncFolders = () => {
    syncing.value = true;
    router.post('/admin/iconos/sincronizar', {}, {
        preserveScroll: true,
        onFinish: () => { syncing.value = false; },
    });
};


/** Alterna la visibilidad o el estado activo del componente. */
const toggle = (slug) => {
    expandedSlug.value = expandedSlug.value === slug ? null : slug;
};
</script>

<template>
    <!-- Administración de paquetes de iconos -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header flex items-center justify-between">
                <span>Paquetes de iconos</span>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-fb-link hover:underline disabled:opacity-50"
                    :disabled="syncing"
                    @click="syncFolders"
                >
                    <FaIcon icon="fa-solid fa-rotate" :class="{ 'animate-spin': syncing }" />
                    {{ syncing ? 'Sincronizando...' : 'Sincronizar carpetas' }}
                </button>
            </div>

            <p class="border-b border-fb-border bg-[#F0FDFA] px-4 py-2 text-xs text-fb-muted">
                <FaIcon icon="fa-solid fa-folder-open" class="mr-1" />
                Crea un paquete copiando una carpeta con un <code class="rounded bg-white px-1 py-0.5">icon-pack.json</code>
                dentro de <code class="rounded bg-white px-1 py-0.5">{{ iconPacksPath || 'icon-packs/' }}</code> y pulsa
                «Sincronizar carpetas». Cada paquete asocia el <em>slug</em> de un rango o medalla con un icono
                de Font Awesome. Luego, en <strong>Temas / Skins</strong>, liga un tema a este paquete: al cambiar de
                tema, los iconos de rangos y medallas cambian con él.
            </p>

            <ul class="divide-y divide-fb-border">
                <li v-for="pack in iconPacks" :key="pack.slug" class="px-4 py-3 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-1.5 font-semibold">
                                {{ pack.name }}
                                <span class="rounded bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-800">
                                    <FaIcon icon="fa-solid fa-folder" class="mr-0.5" />{{ pack.slug }}
                                </span>
                                <span v-if="!pack.is_active" class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">
                                    Inactivo
                                </span>
                            </p>
                            <p class="text-xs text-fb-muted">
                                {{ pack.description }}
                                <template v-if="pack.author"> · por {{ pack.author }}</template>
                                <template v-if="pack.version"> · v{{ pack.version }}</template>
                                · {{ Object.keys(pack.ranks).length }} rango(s), {{ Object.keys(pack.medals).length }} medalla(s)
                            </p>
                        </div>
                        <button type="button" class="text-xs font-semibold text-fb-link hover:underline" @click="toggle(pack.slug)">
                            {{ expandedSlug === pack.slug ? 'Ocultar mapeo' : 'Ver mapeo' }}
                        </button>
                    </div>

                    <div v-if="expandedSlug === pack.slug" class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="mb-1 text-xs font-semibold text-fb-muted">Rangos</p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="(icon, slug) in pack.ranks"
                                    :key="slug"
                                    class="inline-flex items-center gap-1.5 rounded border border-fb-border bg-white px-2 py-1 text-xs"
                                >
                                    <FaIcon :icon="icon" /> {{ slug }}
                                </span>
                                <span v-if="!Object.keys(pack.ranks).length" class="text-xs text-fb-muted">Sin mapeos.</span>
                            </div>
                        </div>
                        <div>
                            <p class="mb-1 text-xs font-semibold text-fb-muted">Medallas</p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="(icon, slug) in pack.medals"
                                    :key="slug"
                                    class="inline-flex items-center gap-1.5 rounded border border-fb-border bg-white px-2 py-1 text-xs"
                                >
                                    <FaIcon :icon="icon" /> {{ slug }}
                                </span>
                                <span v-if="!Object.keys(pack.medals).length" class="text-xs text-fb-muted">Sin mapeos.</span>
                            </div>
                        </div>
                    </div>
                </li>
                <li v-if="!iconPacks.length" class="px-4 py-6 text-center text-sm text-fb-muted">
                    Todavía no hay paquetes de iconos instalados.
                </li>
            </ul>
        </div>
    </AdminLayout>
</template>
