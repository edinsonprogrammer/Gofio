<script setup>
/**
 * Gestión administrativa de usuarios: búsqueda, edición, suspensión y asignación de rangos.
 */

import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import { verificationLabels } from '@/utils/adminLabels';

const props = defineProps({
    users: Object,
    filters: Object,
    rangos: Array,
});

const search = ref(props.filters.q ?? '');
const banForms = ref({});
const editForms = ref({});
const revokeForms = ref({});

const verificationLabel = verificationLabels;


/** Sincroniza los formularios de edición con el usuario seleccionado. */
const syncForms = (usersPage) => {
    usersPage.data.forEach((u) => {
        if (!banForms.value[u.id]) {
            banForms.value[u.id] = { reason: '', days: 7 };
        }
        if (!editForms.value[u.id]) {
            editForms.value[u.id] = {
                karma: u.karma,
                balance_monedas: u.balance_monedas,
                rango_id: u.rango_id,
                tipo_verificacion: u.tipo_verificacion,
                is_admin: u.is_admin,
            };
        }
        if (!revokeForms.value[u.id]) {
            revokeForms.value[u.id] = { reason: '' };
        }
    });
};

syncForms(props.users);
watch(() => props.users, syncForms);


/** Ejecuta la búsqueda de usuarios con el término del filtro. */
const doSearch = () => router.get('/admin/usuarios', { q: search.value }, { preserveState: true, replace: true });

let debounceTimer = null;
watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(doSearch, 300);
});


/** Suspende la cuenta del usuario seleccionado. */
const ban = (user) => router.post(`/admin/usuarios/${user.id}/banear`, banForms.value[user.id], { preserveScroll: true });

/** Reactiva una cuenta previamente suspendida. */
const unban = (user) => router.post(`/admin/usuarios/${user.id}/desbanear`, {}, { preserveScroll: true });

/** Actualiza el registro existente con los valores editados. */
const update = (user) => router.put(`/admin/usuarios/${user.id}`, editForms.value[user.id], { preserveScroll: true });


/** Retira la verificación o Creator Plus del usuario. */
const revokeVerification = (user) => router.post(
    `/admin/usuarios/${user.id}/quitar-verificado`,
    revokeForms.value[user.id],
    { preserveScroll: true },
);


/** Retira la suscripción Creator Plus del usuario. */
const revokeCreatorPlus = (user) => router.post(
    `/admin/usuarios/${user.id}/quitar-creator-plus`,
    revokeForms.value[user.id],
    { preserveScroll: true },
);


/** Comprueba si el admin puede revocar la verificación del usuario. */
const canRevokeVerification = (user) =>
    user.has_identity_verification || user.display_verification === 'user_verified';


/** Comprueba si el admin puede retirar Creator Plus. */
const canRevokeCreatorPlus = (user) => user.is_creator_plus || user.tipo_verificacion === 'creator_plus';
</script>

<template>
    <!-- Búsqueda y administración de usuarios -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header">Usuarios</div>
    <!-- Formulario principal -->
            <form class="border-b border-fb-border p-3" @submit.prevent="doSearch">
                <div class="flex gap-2">
                    <input v-model="search" type="search" placeholder="Buscar por @usuario, @nick o correo..." class="gofio-input" />
                    <button type="submit" class="gofio-btn-primary">Buscar</button>
                </div>
            </form>

            <div v-for="user in users.data" :key="user.id" class="border-b border-fb-border p-4 last:border-0">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold">
                            @{{ user.username }}
                            <span v-if="user.nick" class="ml-1 font-normal text-fb-muted">@{{ user.nick }}</span>
                            <span v-if="user.is_admin" class="ml-1 text-xs text-red-600">administrador</span>
                            <span v-if="user.is_banned" class="ml-1 text-xs text-red-600">baneado</span>
                        </p>
                        <p class="text-xs text-fb-muted">
                            {{ user.email }} · {{ user.rango?.nombre }} · {{ user.karma }} karma
                        </p>
                        <p class="mt-1 text-xs">
                            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-medium">
                                {{ verificationLabel[user.display_verification] ?? user.display_verification }}
                            </span>
                            <span v-if="user.is_creator_plus && user.creator_plus_expires_at" class="ml-1 text-fb-muted">
                                CP hasta {{ new Date(user.creator_plus_expires_at).toLocaleDateString('es-ES') }}
                            </span>
                        </p>
                    </div>
                </div>

                <details class="mt-3">
                    <summary class="cursor-pointer text-sm font-semibold text-fb-link">Editar / sancionar / verificación</summary>
                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase text-fb-muted">Editar datos</p>
                            <div>
                                <label class="gofio-field-label">Karma</label>
                                <input v-model.number="editForms[user.id].karma" type="number" class="gofio-input text-xs" />
                            </div>
                            <div>
                                <label class="gofio-field-label">Balance de monedas</label>
                                <input v-model.number="editForms[user.id].balance_monedas" type="number" step="0.01" class="gofio-input text-xs" />
                            </div>
                            <div>
                                <label class="gofio-field-label">Rango</label>
                                <select v-model="editForms[user.id].rango_id" class="gofio-input text-xs">
                                    <option v-for="r in rangos" :key="r.id" :value="r.id">{{ r.nombre }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="gofio-field-label">Verificación (manual)</label>
                                <select v-model="editForms[user.id].tipo_verificacion" class="gofio-input text-xs">
                                    <option value="none">Sin verificar</option>
                                    <option value="user_verified">Verificado</option>
                                    <option value="creator_plus">Creator Plus</option>
                                </select>
                                <p class="mt-1 text-[10px] text-fb-muted">Usa los botones de abajo para revocar con historial consistente.</p>
                            </div>
                            <label class="flex items-center gap-2 text-xs"><input v-model="editForms[user.id].is_admin" type="checkbox" /> Administrador</label>
                            <button type="button" class="gofio-btn-primary text-xs" @click="update(user)">Guardar</button>
                        </div>
                        <div class="space-y-3">
                            <div class="space-y-2">
                                <p class="text-xs font-semibold uppercase text-fb-muted">Control de verificación</p>
                                <textarea v-model="revokeForms[user.id].reason" rows="2" class="gofio-input text-xs" placeholder="Motivo (opcional)" />
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="gofio-btn-secondary text-xs text-orange-700"
                                        :disabled="!canRevokeVerification(user)"
                                        @click="revokeVerification(user)"
                                    >
                                        Quitar verificado
                                    </button>
                                    <button
                                        type="button"
                                        class="gofio-btn-secondary text-xs text-red-600"
                                        :disabled="!canRevokeCreatorPlus(user)"
                                        @click="revokeCreatorPlus(user)"
                                    >
                                        Quitar Creator Plus
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-2 border-t border-fb-border pt-3">
                                <p class="text-xs font-semibold uppercase text-fb-muted">Suspensión</p>
                                <textarea v-model="banForms[user.id].reason" rows="2" class="gofio-input text-xs" placeholder="Motivo" />
                                <input v-model.number="banForms[user.id].days" type="number" class="gofio-input text-xs" placeholder="Días (vacío = permanente)" />
                                <div class="flex gap-2">
                                    <button type="button" class="gofio-btn-primary text-xs" :disabled="user.is_banned" @click="ban(user)">Banear</button>
                                    <button type="button" class="gofio-btn-secondary text-xs" :disabled="!user.is_banned" @click="unban(user)">Desbanear</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>
        <!-- Paginación de usuarios -->
        <AdminPagination :paginator="users" item-label="usuarios" />
    </AdminLayout>
</template>
