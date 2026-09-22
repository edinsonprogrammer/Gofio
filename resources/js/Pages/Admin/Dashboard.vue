<script setup>

/**

 * Dashboard administrativo: métricas generales del sitio, alerta de mantenimiento

 * y registro paginado de acciones de moderación.

 */



import AdminLayout from '@/Layouts/AdminLayout.vue';

import AdminPagination from '@/Components/Admin/AdminPagination.vue';

import FaIcon from '@/Components/UI/FaIcon.vue';

import {

    formatAdminLogTarget,

    label,

    statLabels,

    translateAdminAction,

} from '@/utils/adminLabels';



defineProps({

    stats: Object,

    recent_logs: Object,

    settings: Object,

});



/** Formatea la fecha del log en locale español. */

const formatLogDate = (iso) => new Date(iso).toLocaleString('es-ES', {

    day: 'numeric',

    month: 'short',

    year: 'numeric',

    hour: '2-digit',

    minute: '2-digit',

});

</script>



<template>

    <AdminLayout>

        <!-- Tarjetas con contadores de usuarios, posts, denuncias, etc. -->

        <div class="gofio-box overflow-hidden">

            <div class="gofio-box-header">Estadísticas generales</div>

            <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">

                <div v-for="(val, key) in stats" :key="key" class="rounded border border-fb-border bg-[var(--color-panel)] p-3">

                    <p class="text-xs uppercase text-fb-muted">{{ label(statLabels, key) }}</p>

                    <p class="text-2xl font-bold">{{ val }}</p>

                </div>

            </div>

        </div>



        <!-- Aviso visible cuando el modo mantenimiento está activo -->

        <div v-if="settings.offline_mode" class="gofio-box border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">

            <FaIcon icon="fa-solid fa-triangle-exclamation" class="mr-1" />

            Modo mantenimiento activo.

        </div>



        <!-- Historial paginado de acciones del equipo administrativo -->

        <div class="gofio-box overflow-hidden">

            <div class="gofio-box-header flex flex-wrap items-center justify-between gap-2">

                <span>Historial reciente</span>

                <span v-if="recent_logs.total" class="text-[10px] font-normal normal-case tracking-normal text-fb-muted">

                    {{ recent_logs.total }} acciones registradas

                </span>

            </div>



            <ul class="divide-y divide-fb-border">

                <li

                    v-for="log in recent_logs.data"

                    :key="log.id"

                    class="admin-log-item px-4 py-3 text-sm"

                >

                    <div class="flex flex-wrap items-start justify-between gap-2">

                        <div class="min-w-0 flex-1">

                            <p class="leading-snug">

                                <span class="font-semibold text-textPrincipal">{{ log.admin || 'Sistema' }}</span>

                                <span
                                    v-if="log.staff_rank"
                                    class="ml-1 rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-semibold text-orange-800"
                                >
                                    {{ log.staff_rank }}
                                </span>

                                <span class="text-fb-muted"> · {{ translateAdminAction(log.action) }}</span>

                                <span

                                    v-if="formatAdminLogTarget(log.target_type, log.target_id)"

                                    class="text-fb-muted"

                                >

                                    → {{ formatAdminLogTarget(log.target_type, log.target_id) }}

                                </span>

                            </p>

                            <p v-if="log.reason" class="mt-1 text-xs leading-snug text-fb-muted">

                                {{ log.reason }}

                            </p>

                        </div>

                        <time class="admin-log-item__date shrink-0 text-xs text-fb-muted">

                            {{ formatLogDate(log.created_at) }}

                        </time>

                    </div>

                </li>



                <li v-if="!recent_logs.data?.length" class="p-6 text-center text-sm text-fb-muted">

                    Sin acciones registradas.

                </li>

            </ul>



            <div v-if="recent_logs.last_page > 1" class="border-t border-fb-border px-4 py-3">

                <AdminPagination :paginator="recent_logs" item-label="acciones" />

            </div>

        </div>

    </AdminLayout>

</template>


