<script setup>
/**
 * Panel admin de verificaciones: búsqueda, revisión en lote y acciones rápidas
 * para gestionar grandes volúmenes de solicitudes y usuarios verificados.
 */

import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import { verificationLabels, label } from '@/utils/adminLabels';

const props = defineProps({
    pending: { type: Object, required: true },
    verified: { type: Object, required: true },
    recent: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ q: '', tab: 'pending' }) },
    counts: { type: Object, default: () => ({ pending: 0, verified: 0 }) },
});

/** Pestaña activa: pendientes o verificados. */
const activeTab = ref(props.filters.tab ?? 'pending');

/** Término de búsqueda sincronizado con la URL. */
const search = ref(props.filters.q ?? '');

/** IDs de solicitudes pendientes seleccionadas para acción en lote. */
const selectedIds = ref([]);

/** Solicitud abierta en el modal de revisión individual. */
const reviewTarget = ref(null);

/** Usuario abierto en el modal de revocación. */
const revokeTarget = ref(null);

/** Notas del admin para revisión individual o en lote. */
const adminNotes = ref('');

/** Motivo de revocación en el modal de quitar verificado. */
const revokeReason = ref('');

/** Indica si hay una acción en curso para deshabilitar botones. */
const processing = ref(false);

const documentTypeLabel = {
    id_card: 'DNI / Cédula',
    passport: 'Pasaporte',
    driver_license: 'Licencia',
};

const statusLabel = {
    pending: 'Pendiente',
    approved: 'Aprobada',
    rejected: 'Rechazada',
    revoked: 'Revocada',
};

/** Solicitudes visibles en la página actual del listado pendiente. */
const pendingRows = computed(() => props.pending.data ?? []);

/** Usuarios verificados visibles en la página actual. */
const verifiedRows = computed(() => props.verified.data ?? []);

/** Comprueba si todas las filas pendientes visibles están seleccionadas. */
const allPageSelected = computed(() =>
    pendingRows.value.length > 0
    && pendingRows.value.every((row) => selectedIds.value.includes(row.id)),
);

/** Sincroniza pestaña y búsqueda cuando Inertia actualiza props tras navegación. */
watch(
    () => props.filters,
    (filters) => {
        activeTab.value = filters.tab ?? 'pending';
        search.value = filters.q ?? '';
    },
    { deep: true },
);

/** Limpia selección al cambiar de página o resultados. */
watch(() => props.pending, () => {
    selectedIds.value = selectedIds.value.filter((id) =>
        pendingRows.value.some((row) => row.id === id),
    );
});

/** Formatea una fecha ISO en formato local español. */
const formatDate = (iso) => {
    if (! iso) return '—';
    return new Date(iso).toLocaleString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

/** Navega conservando pestaña y búsqueda actuales. */
const reloadList = (extra = {}) => {
    router.get('/admin/verificaciones', {
        tab: activeTab.value,
        q: search.value.trim() || undefined,
        ...extra,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

/** Cambia de pestaña y reinicia a la primera página del listado correspondiente. */
const switchTab = (tab) => {
    activeTab.value = tab;
    reloadList(tab === 'verified' ? { verified_page: 1, page: undefined } : { page: 1, verified_page: undefined });
};

/** Ejecuta búsqueda manual desde el formulario. */
const doSearch = () => reloadList({ page: 1, verified_page: 1 });

/** Búsqueda con debounce mientras el admin escribe. */
let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(doSearch, 350);
});

/** Alterna selección de una solicitud pendiente. */
const toggleRow = (id) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((item) => item !== id);
    } else {
        selectedIds.value = [...selectedIds.value, id];
    }
};

/** Selecciona o deselecciona todas las solicitudes visibles en la página. */
const toggleSelectAll = () => {
    if (allPageSelected.value) {
        const visibleIds = pendingRows.value.map((row) => row.id);
        selectedIds.value = selectedIds.value.filter((id) => ! visibleIds.includes(id));
        return;
    }

    const merged = new Set([...selectedIds.value, ...pendingRows.value.map((row) => row.id)]);
    selectedIds.value = [...merged];
};

/** Abre el modal de revisión para una solicitud concreta. */
const openReview = (req) => {
    reviewTarget.value = req;
    adminNotes.value = '';
};

/** Cierra el modal de revisión individual. */
const closeReview = () => {
    reviewTarget.value = null;
    adminNotes.value = '';
};

/** Abre el modal para revocar la verificación de un usuario. */
const openRevoke = (user) => {
    revokeTarget.value = user;
    revokeReason.value = '';
};

/** Cierra el modal de revocación. */
const closeRevoke = () => {
    revokeTarget.value = null;
    revokeReason.value = '';
};

/** Aprueba una solicitud con notas opcionales. */
const approveOne = (id, notes = null) => {
    processing.value = true;
    router.post(`/admin/verificaciones/${id}/aprobar`, {
        admin_notes: notes,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            closeReview();
            selectedIds.value = selectedIds.value.filter((item) => item !== id);
        },
    });
};

/** Rechaza una solicitud con notas opcionales. */
const rejectOne = (id, notes = null) => {
    processing.value = true;
    router.post(`/admin/verificaciones/${id}/rechazar`, {
        admin_notes: notes,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            closeReview();
            selectedIds.value = selectedIds.value.filter((item) => item !== id);
        },
    });
};

/** Aprueba o rechaza en lote las solicitudes seleccionadas. */
const bulkAction = (action) => {
    if (! selectedIds.value.length) return;

    const verb = action === 'approve' ? 'aprobar' : 'rechazar';
    if (! confirm(`¿${verb} ${selectedIds.value.length} solicitud(es) seleccionada(s)?`)) return;

    processing.value = true;
    router.post('/admin/verificaciones/lote', {
        action,
        ids: selectedIds.value,
        admin_notes: adminNotes.value || null,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            selectedIds.value = [];
            adminNotes.value = '';
        },
    });
};

/** Revoca la verificación del usuario seleccionado en el modal. */
const confirmRevoke = () => {
    if (! revokeTarget.value) return;

    processing.value = true;
    router.post(`/admin/verificaciones/usuarios/${revokeTarget.value.id}/revocar`, {
        reason: revokeReason.value || null,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            closeRevoke();
        },
    });
};

/** URL de descarga del documento adjunto a una solicitud. */
const documentUrl = (id) => `/admin/verificaciones/${id}/documento`;
</script>

<template>
    <!-- Panel de verificaciones con búsqueda, lote y modales -->

    <AdminLayout>
        <div class="mx-auto max-w-6xl space-y-4">

            <!-- Encabezado y pestañas -->
            <div class="gofio-box overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-fb-border px-4 py-3">
                    <div>
                        <h1 class="text-lg font-bold">Verificaciones</h1>
                        <p class="text-xs text-fb-muted">
                            Revisa solicitudes, actúa en lote y revoca verificados con búsqueda rápida.
                        </p>
                    </div>
                    <div class="flex gap-2 text-xs">
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 font-semibold text-amber-800">
                            {{ counts.pending }} pendientes
                        </span>
                        <span class="rounded-full bg-green-100 px-2.5 py-1 font-semibold text-green-800">
                            {{ counts.verified }} verificados
                        </span>
                    </div>
                </div>

                <!-- Pestañas -->
                <div class="flex border-b border-fb-border px-4">
                    <button
                        type="button"
                        class="border-b-2 px-4 py-2.5 text-sm font-medium transition-colors"
                        :class="activeTab === 'pending'
                            ? 'border-fb-accent text-fb-accent'
                            : 'border-transparent text-fb-muted hover:text-fb-text'"
                        @click="switchTab('pending')"
                    >
                        Solicitudes pendientes
                        <span v-if="counts.pending" class="ml-1 rounded-full bg-amber-100 px-1.5 text-xs text-amber-800">
                            {{ counts.pending }}
                        </span>
                    </button>
                    <button
                        type="button"
                        class="border-b-2 px-4 py-2.5 text-sm font-medium transition-colors"
                        :class="activeTab === 'verified'
                            ? 'border-fb-accent text-fb-accent'
                            : 'border-transparent text-fb-muted hover:text-fb-text'"
                        @click="switchTab('verified')"
                    >
                        Usuarios verificados
                        <span v-if="counts.verified" class="ml-1 rounded-full bg-green-100 px-1.5 text-xs text-green-800">
                            {{ counts.verified }}
                        </span>
                    </button>
                </div>

                <!-- Búsqueda -->
                <form class="border-b border-fb-border p-3" @submit.prevent="doSearch">
                    <div class="flex gap-2">
                        <input
                            v-model="search"
                            type="search"
                            class="gofio-input"
                            :placeholder="activeTab === 'pending'
                                ? 'Buscar por @usuario, correo o nombre legal…'
                                : 'Buscar verificado por @usuario o correo…'"
                        />
                        <button type="submit" class="gofio-btn-primary shrink-0">Buscar</button>
                        <button
                            v-if="search"
                            type="button"
                            class="gofio-btn-secondary shrink-0"
                            @click="search = ''; doSearch()"
                        >
                            Limpiar
                        </button>
                    </div>
                </form>

                <!-- Barra de acciones en lote (solo pendientes) -->
                <div
                    v-if="activeTab === 'pending' && selectedIds.length"
                    class="flex flex-wrap items-center gap-3 border-b border-amber-200 bg-amber-50 px-4 py-2.5"
                >
                    <span class="text-sm font-medium text-amber-900">
                        {{ selectedIds.length }} seleccionada(s)
                    </span>
                    <input
                        v-model="adminNotes"
                        type="text"
                        class="gofio-input max-w-xs text-xs"
                        placeholder="Notas para el lote (opcional)"
                    />
                    <button
                        type="button"
                        class="gofio-btn-primary text-xs"
                        :disabled="processing"
                        @click="bulkAction('approve')"
                    >
                        Aprobar seleccionadas
                    </button>
                    <button
                        type="button"
                        class="gofio-btn-secondary text-xs text-red-600"
                        :disabled="processing"
                        @click="bulkAction('reject')"
                    >
                        Rechazar seleccionadas
                    </button>
                    <button
                        type="button"
                        class="text-xs text-fb-muted hover:underline"
                        @click="selectedIds = []"
                    >
                        Cancelar selección
                    </button>
                </div>

                <!-- Tabla: solicitudes pendientes -->
                <div v-if="activeTab === 'pending'" class="overflow-x-auto">
                    <table v-if="pendingRows.length" class="w-full min-w-[720px] text-sm">
                        <thead class="border-b border-fb-border bg-[#F5F6F7] text-left text-xs uppercase text-fb-muted">
                            <tr>
                                <th class="w-10 px-3 py-2.5">
                                    <input
                                        type="checkbox"
                                        :checked="allPageSelected"
                                        :indeterminate="selectedIds.length > 0 && !allPageSelected"
                                        @change="toggleSelectAll"
                                    />
                                </th>
                                <th class="px-3 py-2.5">Usuario</th>
                                <th class="px-3 py-2.5">Nombre legal</th>
                                <th class="px-3 py-2.5">Documento</th>
                                <th class="px-3 py-2.5">Enviada</th>
                                <th class="px-3 py-2.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-fb-border">
                            <tr
                                v-for="req in pendingRows"
                                :key="req.id"
                                class="hover:bg-fb-hover/40 transition-colors"
                                :class="{ 'bg-blue-50/60': selectedIds.includes(req.id) }"
                            >
                                <td class="px-3 py-3">
                                    <input
                                        type="checkbox"
                                        :checked="selectedIds.includes(req.id)"
                                        @change="toggleRow(req.id)"
                                    />
                                </td>
                                <td class="px-3 py-3">
                                    <p class="font-semibold">@{{ req.user.username }}</p>
                                    <p class="text-xs text-fb-muted">{{ req.user.email }}</p>
                                </td>
                                <td class="px-3 py-3">{{ req.full_name }}</td>
                                <td class="px-3 py-3 text-xs">
                                    {{ documentTypeLabel[req.document_type] ?? req.document_type }}
                                </td>
                                <td class="px-3 py-3 text-xs text-fb-muted whitespace-nowrap">
                                    {{ formatDate(req.created_at) }}
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <button
                                            type="button"
                                            class="rounded px-2 py-1 text-xs font-medium text-fb-link hover:bg-blue-50"
                                            @click="openReview(req)"
                                        >
                                            Revisar
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-50"
                                            :disabled="processing"
                                            @click="approveOne(req.id)"
                                        >
                                            Aprobar
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                            :disabled="processing"
                                            @click="rejectOne(req.id)"
                                        >
                                            Rechazar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="p-8 text-center text-sm text-fb-muted">
                        {{ search ? 'No hay solicitudes que coincidan con la búsqueda.' : 'No hay solicitudes pendientes.' }}
                    </p>
                </div>

                <!-- Tabla: usuarios verificados -->
                <div v-else class="overflow-x-auto">
                    <p class="border-b border-fb-border px-4 py-2 text-xs text-fb-muted">
                        Retira el check verde obtenido por verificación de identidad. Creator Plus se gestiona en Usuarios.
                    </p>
                    <table v-if="verifiedRows.length" class="w-full min-w-[640px] text-sm">
                        <thead class="border-b border-fb-border bg-[#F5F6F7] text-left text-xs uppercase text-fb-muted">
                            <tr>
                                <th class="px-4 py-2.5">Usuario</th>
                                <th class="px-4 py-2.5">Correo</th>
                                <th class="px-4 py-2.5">Insignia</th>
                                <th class="px-4 py-2.5 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-fb-border">
                            <tr
                                v-for="user in verifiedRows"
                                :key="user.id"
                                class="hover:bg-fb-hover/40 transition-colors"
                            >
                                <td class="px-4 py-3 font-semibold">@{{ user.username }}</td>
                                <td class="px-4 py-3 text-xs text-fb-muted">{{ user.email }}</td>
                                <td class="px-4 py-3 text-xs">
                                    {{ label(verificationLabels, user.display_verification) }}
                                    <span
                                        v-if="user.is_creator_plus"
                                        class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-amber-800"
                                    >
                                        Creator Plus
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        class="rounded px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                        :disabled="!user.has_identity_verification && user.display_verification === 'none'"
                                        @click="openRevoke(user)"
                                    >
                                        Quitar verificado
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="p-8 text-center text-sm text-fb-muted">
                        {{ search ? 'No hay verificados que coincidan con la búsqueda.' : 'No hay usuarios verificados por solicitud.' }}
                    </p>
                </div>
            </div>

            <!-- Paginación según pestaña activa -->
            <AdminPagination
                v-if="activeTab === 'pending'"
                :paginator="pending"
                item-label="solicitudes"
            />
            <AdminPagination
                v-else
                :paginator="verified"
                query-key="verified_page"
                item-label="verificados"
            />

            <!-- Historial reciente compacto -->
            <div v-if="recent.length" class="gofio-box overflow-hidden">
                <div class="gofio-box-header">Revisadas recientemente</div>
                <ul class="divide-y divide-fb-border">
                    <li
                        v-for="item in recent"
                        :key="item.id"
                        class="flex items-center justify-between px-4 py-2 text-sm"
                    >
                        <span>@{{ item.username }} — {{ item.full_name }}</span>
                        <span class="text-xs text-fb-muted">{{ formatDate(item.reviewed_at) }}</span>
                        <span
                            class="text-xs font-semibold"
                            :class="{
                                'text-green-600': item.status === 'approved',
                                'text-red-600': item.status === 'rejected',
                                'text-orange-600': item.status === 'revoked',
                            }"
                        >
                            {{ statusLabel[item.status] ?? item.status }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Modal: revisión individual de solicitud -->
        <Teleport to="body">
            <div
                v-if="reviewTarget"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @click.self="closeReview"
            >
                <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-fb-border px-4 py-3">
                        <h2 class="font-semibold">Revisar solicitud</h2>
                        <button type="button" class="text-fb-muted hover:text-fb-text" @click="closeReview">✕</button>
                    </div>
                    <div class="space-y-3 p-4 text-sm">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div>
                                <p class="text-xs text-fb-muted">Usuario</p>
                                <p class="font-semibold">@{{ reviewTarget.user.username }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-fb-muted">Correo</p>
                                <p>{{ reviewTarget.user.email }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-fb-muted">Nombre legal</p>
                                <p>{{ reviewTarget.full_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-fb-muted">Documento</p>
                                <p>{{ documentTypeLabel[reviewTarget.document_type] ?? reviewTarget.document_type }}</p>
                            </div>
                        </div>
                        <p v-if="reviewTarget.user_notes" class="rounded bg-[#F5F6F7] px-3 py-2 text-xs">
                            <span class="font-medium">Notas del usuario:</span> {{ reviewTarget.user_notes }}
                        </p>
                        <a
                            :href="documentUrl(reviewTarget.id)"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-fb-link hover:underline"
                        >
                            Ver documento adjunto →
                        </a>
                        <textarea
                            v-model="adminNotes"
                            rows="3"
                            class="gofio-input text-xs"
                            placeholder="Notas internas (opcional)"
                        />
                    </div>
                    <div class="flex justify-end gap-2 border-t border-fb-border px-4 py-3">
                        <button type="button" class="gofio-btn-secondary text-sm" @click="closeReview">Cancelar</button>
                        <button
                            type="button"
                            class="gofio-btn-secondary text-sm text-red-600"
                            :disabled="processing"
                            @click="rejectOne(reviewTarget.id, adminNotes || null)"
                        >
                            Rechazar
                        </button>
                        <button
                            type="button"
                            class="gofio-btn-primary text-sm"
                            :disabled="processing"
                            @click="approveOne(reviewTarget.id, adminNotes || null)"
                        >
                            Aprobar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal: confirmar revocación de verificado -->
        <Teleport to="body">
            <div
                v-if="revokeTarget"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @click.self="closeRevoke"
            >
                <div class="w-full max-w-md rounded-lg bg-white shadow-xl">
                    <div class="border-b border-fb-border px-4 py-3">
                        <h2 class="font-semibold">Quitar verificado</h2>
                    </div>
                    <div class="space-y-3 p-4 text-sm">
                        <p>
                            Vas a revocar la verificación de
                            <strong>@{{ revokeTarget.username }}</strong>.
                        </p>
                        <textarea
                            v-model="revokeReason"
                            rows="3"
                            class="gofio-input text-xs"
                            placeholder="Motivo de revocación (opcional)"
                        />
                    </div>
                    <div class="flex justify-end gap-2 border-t border-fb-border px-4 py-3">
                        <button type="button" class="gofio-btn-secondary text-sm" @click="closeRevoke">Cancelar</button>
                        <button
                            type="button"
                            class="gofio-btn-secondary text-sm text-red-600"
                            :disabled="processing"
                            @click="confirmRevoke"
                        >
                            Confirmar revocación
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
