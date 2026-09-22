<script setup>

/**

 * Selector visual de temas con preview fiel a los colores aplicados,

 * bloqueo por Creator Plus y aplicación instantánea vía API.

 */



import { ref } from 'vue';

import { router } from '@inertiajs/vue3';



const props = defineProps({

    themes: {

        type: Array,

        default: () => [],

    },

    activeThemeId: {

        type: Number,

        default: null,

    },

});



const loadingId = ref(null);

const error = ref('');



/** Solicita al servidor activar el tema seleccionado y recarga la página. */

const select = async (theme) => {

    if (theme.locked || theme.id === props.activeThemeId) {

        return;

    }



    loadingId.value = theme.id;

    error.value = '';



    try {

        await window.axios.post('/api/themes/select', { theme_id: theme.id });

        router.reload();

    } catch (e) {

        error.value = e.response?.data?.message

            || e.response?.data?.errors?.theme_id?.[0]

            || 'No se pudo cambiar el tema.';

    } finally {

        loadingId.value = null;

    }

};

</script>



<template>

    <div>

        <p v-if="error" class="mb-3 text-xs text-red-600">{{ error }}</p>



        <!-- Cuadrícula de tarjetas con miniatura del topbar, paneles y botón -->

        <div class="grid gap-3 sm:grid-cols-2">

            <button

                v-for="theme in themes"

                :key="theme.id"

                type="button"

                class="gofio-box overflow-hidden text-left transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"

                :class="{

                    'ring-2 ring-brandColor': theme.id === activeThemeId,

                }"

                :disabled="theme.locked || loadingId === theme.id"

                @click="select(theme)"

            >

                <div

                    class="flex h-[5.25rem] flex-col border-b"

                    :style="{ borderColor: theme.preview.border, background: theme.preview.background }"

                >

                    <div

                        class="flex h-5 items-center justify-end px-1.5"

                        :style="{ background: theme.preview.topbar }"

                    >

                        <span

                            class="text-[10px] font-extrabold leading-none"

                            :style="{ color: theme.preview.accent }"

                        >

                            !

                        </span>

                    </div>

                    <div class="flex min-h-0 flex-1">

                        <div

                            class="flex w-[30%] flex-col gap-1 p-1.5"

                            :style="{ background: theme.preview.panel }"

                        >

                            <div

                                class="h-2 rounded-sm"

                                :style="{ background: theme.preview.header }"

                            />

                            <div

                                class="h-6 flex-1 rounded-sm border"

                                :style="{

                                    background: theme.preview.surface,

                                    borderColor: theme.preview.border,

                                }"

                            />

                        </div>

                        <div class="flex flex-1 flex-col justify-between p-1.5">

                            <div

                                class="h-5 rounded-sm border"

                                :style="{

                                    background: theme.preview.surface,

                                    borderColor: theme.preview.border,

                                }"

                            >

                                <div

                                    class="mx-1 mt-1 h-1.5 rounded-sm"

                                    :style="{ background: theme.preview.header }"

                                />

                            </div>

                            <div

                                class="h-2.5 w-2/3 rounded-full"

                                :style="{

                                    background: `linear-gradient(180deg, ${theme.preview.brand} 0%, ${theme.preview.brand_hover} 100%)`,

                                }"

                            />

                        </div>

                    </div>

                </div>

                <div class="p-3">

                    <div class="flex items-center justify-between gap-2">

                        <p class="text-sm font-semibold">{{ theme.name }}</p>

                        <span

                            v-if="theme.requires_creator_plus"

                            class="rounded bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-800"

                        >

                            Plus

                        </span>

                    </div>

                    <p v-if="theme.description" class="mt-1 text-xs text-fb-muted">{{ theme.description }}</p>

                    <p v-if="theme.locked" class="mt-1 text-xs text-amber-600">Requiere Creator Plus</p>

                    <p v-else-if="theme.id === activeThemeId" class="mt-1 text-xs text-green-600">Activo</p>

                    <p v-else-if="loadingId === theme.id" class="mt-1 text-xs text-fb-muted">Aplicando...</p>

                </div>

            </button>

        </div>

    </div>

</template>


