<script setup>
/**
 * Centro de moderación: denuncias pendientes, alertas y acciones sobre contenido.
 */

import { Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import { label, reportableTypeLabels } from '@/utils/adminLabels';

defineProps({
    reports: Object,
    alerts: Object,
});

const notes = ref({});
const editForms = reactive({});
const suspendDays = ref({});
const expandedEdit = ref({});


/** Formatea una fecha ISO en formato local español. */
const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString('es-ES', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};


/** Inicializa el formulario de edición de una denuncia si no existe. */
const ensureEditForm = (report) => {
    if (!editForms[report.id]) {
        editForms[report.id] = {
            title: report.editable?.title ?? '',
            content: report.editable?.content ?? '',
        };
    }
};


/** Alterna el modo edición de una denuncia o alerta. */
const toggleEdit = (report) => {
    ensureEditForm(report);
    expandedEdit.value[report.id] = !expandedEdit.value[report.id];
};


/** Aplica la acción de moderación seleccionada (ban, ignorar, etc.). */
const applyAction = (id, action) => router.post(`/admin/moderacion/denuncias/${id}/accion`, {
    action,
    admin_notes: notes.value[`r-${id}`] || null,
    suspend_days: action === 'suspend_author' ? (suspendDays.value[id] || 7) : null,
}, { preserveScroll: true });


/** Guarda las notas editadas de una denuncia. */
const submitEdit = (report) => {
    ensureEditForm(report);
    router.post(`/admin/moderacion/denuncias/${report.id}/editar`, {
        title: report.reportable_type === 'Post' ? editForms[report.id].title : null,
        content: editForms[report.id].content,
        admin_notes: notes.value[`r-${report.id}`] || null,
    }, { preserveScroll: true });
};


/** Marca una alerta de moderación como resuelta. */
const resolveAlert = (id, status) => router.post(`/admin/moderacion/alertas/${id}`, { status }, { preserveScroll: true });
</script>

<template>
    <!-- Cola de denuncias y alertas de moderación -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header">Denuncias pendientes</div>
            <div v-if="!reports.data.length" class="p-4 text-sm text-fb-muted">
                Sin denuncias pendientes.
            </div>

            <div v-for="report in reports.data" :key="report.id" class="border-b border-fb-border p-4 last:border-0">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm">
                            <strong>@{{ report.reporter?.username }}</strong>
                            denunció
                            {{ label(reportableTypeLabels, report.reportable_type) }}
                            <span class="font-semibold text-red-600">{{ report.reason_label }}</span>
                        </p>
                        <p class="mt-1 text-xs text-fb-muted">
                            {{ formatDate(report.created_at) }}
                            <span v-if="report.author"> · Autor: @{{ report.author.username }}</span>
                        </p>
                        <p class="mt-2 rounded bg-[#F5F6F7] px-3 py-2 text-sm">
                            «{{ report.summary }}»
                        </p>
                        <p v-if="report.details" class="mt-2 text-xs text-fb-muted">
                            Detalle del reportante: {{ report.details }}
                        </p>
                        <Link
                            v-if="report.content_url"
                            :href="report.content_url"
                            class="mt-2 inline-block text-xs font-semibold text-fb-link hover:underline"
                        >
                            Ver contenido →
                        </Link>
                    </div>
                </div>

                <textarea
                    v-model="notes[`r-${report.id}`]"
                    rows="2"
                    class="gofio-input mt-3 text-xs"
                    placeholder="Notas internas de moderación"
                />

                <div v-if="report.reportable_type !== 'User'" class="mt-2 flex flex-wrap items-center gap-2">
                    <label class="text-xs text-fb-muted">Días de suspensión:</label>
                    <input
                        v-model.number="suspendDays[report.id]"
                        type="number"
                        min="1"
                        max="3650"
                        placeholder="7"
                        class="gofio-input w-20 text-xs"
                    />
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" class="gofio-btn-primary text-xs" @click="applyAction(report.id, 'delete_content')">
                        Eliminar contenido
                    </button>
                    <button
                        v-if="report.editable"
                        type="button"
                        class="gofio-btn-secondary text-xs"
                        @click="toggleEdit(report)"
                    >
                        {{ expandedEdit[report.id] ? 'Ocultar editor' : 'Editar contenido' }}
                    </button>
                    <button type="button" class="gofio-btn-secondary text-xs" @click="applyAction(report.id, 'suspend_author')">
                        Suspender autor
                    </button>
                    <button type="button" class="gofio-btn-secondary text-xs" @click="applyAction(report.id, 'reviewed')">
                        Marcar revisada
                    </button>
                    <button type="button" class="gofio-btn-secondary text-xs" @click="applyAction(report.id, 'dismiss')">
                        Descartar
                    </button>
                </div>

                <div v-if="expandedEdit[report.id] && report.editable" class="mt-3 space-y-2 rounded border border-fb-border bg-[#F5F6F7] p-3">
                    <input
                        v-if="report.reportable_type === 'Post'"
                        v-model="editForms[report.id].title"
                        type="text"
                        class="gofio-input text-xs"
                        placeholder="Título"
                    />
                    <textarea
                        v-model="editForms[report.id].content"
                        rows="4"
                        class="gofio-input text-xs"
                        placeholder="Contenido"
                    />
                    <button type="button" class="gofio-btn-primary text-xs" @click="submitEdit(report)">
                        Guardar edición y cerrar denuncia
                    </button>
                </div>
            </div>
        </div>
        <!-- Paginación de denuncias pendientes -->
        <AdminPagination :paginator="reports" item-label="denuncias" />

        <div class="gofio-box mt-4 overflow-hidden">
            <div class="gofio-box-header">Alertas anti-fraude (farming de karma)</div>
            <div v-if="!alerts.data.length" class="p-4 text-sm text-fb-muted">Sin alertas abiertas.</div>
            <div v-for="alert in alerts.data" :key="alert.id" class="border-b border-fb-border p-4 last:border-0">
                <p class="text-sm font-semibold">@{{ alert.user?.username }} ↔ @{{ alert.related_user?.username }}</p>
                <p class="text-xs text-fb-muted">{{ alert.reason }}</p>
                <div class="mt-2 flex gap-2">
                    <button type="button" class="gofio-btn-primary text-xs" @click="resolveAlert(alert.id, 'resolved')">Resolver</button>
                    <button type="button" class="gofio-btn-secondary text-xs" @click="resolveAlert(alert.id, 'dismissed')">Descartar</button>
                </div>
            </div>
        </div>
        <!-- Paginación de alertas anti-fraude con query key separado para no colisionar con denuncias -->
        <AdminPagination :paginator="alerts" query-key="alerts_page" item-label="alertas" />
    </AdminLayout>
</template>
