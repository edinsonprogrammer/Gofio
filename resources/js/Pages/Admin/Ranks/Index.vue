<script setup>
/**
 * Administración de rangos de karma: creación, iconos, colores, umbrales
 * y permisos de pestañas del panel para rangos staff.
 */

import { computed, ref, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import IconPickerField from '@/Components/Admin/IconPickerField.vue';
import UserPicker from '@/Components/Admin/UserPicker.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';

/** Pestañas por defecto del rango Moderador. */
const MODERATOR_TABS = [
    'dashboard', 'users', 'posts', 'moderation', 'categories',
    'badwords', 'tickets', 'verifications', 'medals', 'awards',
];

const page = usePage();
const adminTabOptions = computed(() => page.props.adminTabOptions ?? {});

defineProps({
    ranks: { type: Array, required: true },
    lockedUsers: { type: Object, default: () => ({}) },
});

const editingId = ref(null);
const assigningId = ref(null);
const showCreate = ref(false);
const showLocked = ref(false);

const createForm = useForm({
    nombre: '',
    slug: '',
    color: '#0198E7',
    icon: 'fa-solid fa-star',
    puntos_requeridos: 0,
    poder_voto: 1,
    limite_voto_diario: 5,
    max_content_length: '',
    max_posts_per_day: '',
    max_comments_per_day: '',
    can_send_tips: true,
    can_receive_tips: true,
    is_staff: false,
    auto_promote: true,
    admin_permissions: [...MODERATOR_TABS],
});

const editForm = useForm({
    nombre: '',
    color: '',
    icon: '',
    puntos_requeridos: 0,
    poder_voto: 1,
    limite_voto_diario: 5,
    max_content_length: '',
    max_posts_per_day: '',
    max_comments_per_day: '',
    can_send_tips: true,
    can_receive_tips: true,
    is_staff: false,
    auto_promote: true,
    admin_permissions: [...MODERATOR_TABS],
});

const assignForm = useForm({ user_id: '' });


/** Carga los datos del rango en el formulario de edición. */
const startEdit = (rank) => {
    editingId.value = rank.id;
    assigningId.value = null;
    editForm.nombre = rank.nombre;
    editForm.color = rank.color;
    editForm.icon = rank.icon;
    editForm.puntos_requeridos = rank.puntos_requeridos;
    editForm.poder_voto = rank.poder_voto;
    editForm.limite_voto_diario = rank.limite_voto_diario;
    editForm.max_content_length = rank.max_content_length ?? '';
    editForm.max_posts_per_day = rank.max_posts_per_day ?? '';
    editForm.max_comments_per_day = rank.max_comments_per_day ?? '';
    editForm.can_send_tips = rank.can_send_tips ?? true;
    editForm.can_receive_tips = rank.can_receive_tips ?? true;
    editForm.is_staff = rank.is_staff;
    editForm.auto_promote = rank.auto_promote;
    editForm.admin_permissions = rank.admin_permissions?.length
        ? [...rank.admin_permissions]
        : [...MODERATOR_TABS];
};

/** Activa o desactiva una pestaña del panel para el rango staff. */
const toggleAdminTab = (form, tabKey) => {
    const current = form.admin_permissions ?? [];
    if (current.includes(tabKey)) {
        form.admin_permissions = current.filter((key) => key !== tabKey);
    } else {
        form.admin_permissions = [...current, tabKey];
    }
};

/** Marca todas las pestañas de moderador como predeterminadas. */
const applyModeratorTabs = (form) => {
    form.admin_permissions = [...MODERATOR_TABS];
};

/** Cuenta las pestañas admin seleccionadas en un rango. */
const tabCountLabel = (rank) => {
    const count = rank.is_staff ? (rank.admin_permissions?.length ?? MODERATOR_TABS.length) : 0;
    return count ? `${count} pestaña(s) admin` : '';
};

watch(() => createForm.is_staff, (isStaff) => {
    if (isStaff && !createForm.admin_permissions.length) {
        createForm.admin_permissions = [...MODERATOR_TABS];
    }
});


/** Envía el formulario de creación de un rango nuevo. */
const submitCreate = () => {
    createForm.post('/admin/rangos', {
        onSuccess: () => {
            createForm.reset();
            createForm.color = '#0198E7';
            createForm.icon = 'fa-solid fa-star';
            createForm.poder_voto = 1;
            createForm.limite_voto_diario = 5;
            createForm.auto_promote = true;
            createForm.admin_permissions = [...MODERATOR_TABS];
            showCreate.value = false;
        },
    });
};


/** Guarda los cambios del rango en edición. */
const submitEdit = (rank) => {
    editForm.put(`/admin/rangos/${rank.id}`, {
        onSuccess: () => { editingId.value = null; },
    });
};


/** Elimina un rango del sistema tras confirmación. */
const destroyRank = (rank) => {
    if (!confirm(`¿Eliminar el rango «${rank.nombre}»? Sus usuarios se reasignarán al rango automático más bajo disponible.`)) {
        return;
    }
    router.delete(`/admin/rangos/${rank.id}`, { preserveScroll: true });
};


/** Asigna manualmente un rango a un usuario. */
const submitAssign = (rank) => {
    if (!assignForm.user_id) return;

    assignForm.post(`/admin/rangos/${rank.id}/asignar`, {
        preserveScroll: true,
        onSuccess: () => { assigningId.value = null; assignForm.reset(); },
    });
};


/** Desbloquea el progreso de rango de un usuario. */
const unlockUser = (user) => {
    router.post(`/admin/rangos/usuarios/${user.id}/desbloquear`, {}, { preserveScroll: true });
};
</script>

<template>
    <!-- Gestión de rangos de karma -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header flex items-center justify-between">
                <span>Rangos de usuario</span>
                <button type="button" class="text-xs font-semibold text-fb-link hover:underline" @click="showCreate = !showCreate">
                    {{ showCreate ? 'Cancelar' : '+ Nuevo rango' }}
                </button>
            </div>
            <p class="border-b border-fb-border px-4 py-2 text-xs text-fb-muted">
                Con <strong>«Auto-promoción»</strong> activa, el usuario asciende sola cuando su karma alcanza el
                umbral (condición automática). Sin ella, el rango solo se otorga <strong>asignándolo</strong> a mano
                desde «Asignar a usuario», lo que bloquea al usuario para que el karma no lo reemplace después.
                Al asignar un rango de staff (excepto administradores), el usuario recibe verificación normal automática.
                Los iconos se eligen visualmente desde los paquetes en <code>icon-packs/</code> (p. ej. gemas).
                También puedes vincular un paquete completo a un tema desde <code>/admin/iconos</code>.
            </p>

    <!-- Formulario principal -->
            <form v-if="showCreate" class="grid gap-3 border-b border-fb-border bg-[#F5F6F7] p-4 sm:grid-cols-2" @submit.prevent="submitCreate">
                <div>
                    <label class="gofio-field-label">Nombre del rango</label>
                    <input v-model="createForm.nombre" class="gofio-input text-xs" placeholder="Nombre" required />
                </div>
                <div>
                    <label class="gofio-field-label">Identificador (opcional)</label>
                    <input v-model="createForm.slug" class="gofio-input text-xs" placeholder="se autogenera si se deja vacío" />
                </div>
                <div>
                    <label class="gofio-field-label">Color</label>
                    <input v-model="createForm.color" class="gofio-input text-xs" placeholder="#0198E7" required />
                </div>
                <IconPickerField
                    v-model="createForm.icon"
                    label="Icono"
                    default-icon="fa-solid fa-star"
                    :preview-color="createForm.color"
                    required
                />
                <div>
                    <label class="gofio-field-label">Karma requerido (para auto-promoción)</label>
                    <input v-model.number="createForm.puntos_requeridos" type="number" class="gofio-input text-xs" placeholder="0" />
                </div>
                <div>
                    <label class="gofio-field-label">Poder de voto</label>
                    <input v-model.number="createForm.poder_voto" type="number" class="gofio-input text-xs" placeholder="1" />
                </div>
                <div>
                    <label class="gofio-field-label">Límite de votos por día</label>
                    <input v-model.number="createForm.limite_voto_diario" type="number" class="gofio-input text-xs" placeholder="5" />
                </div>
                <div>
                    <label class="gofio-field-label">Límite de posts por día</label>
                    <input v-model.number="createForm.max_posts_per_day" type="number" class="gofio-input text-xs" placeholder="por defecto según el rango" />
                </div>
                <div>
                    <label class="gofio-field-label">Límite de comentarios por día</label>
                    <input v-model.number="createForm.max_comments_per_day" type="number" class="gofio-input text-xs" placeholder="por defecto según el rango" />
                </div>
                <div>
                    <label class="gofio-field-label">Límite de caracteres por post</label>
                    <input v-model.number="createForm.max_content_length" type="number" class="gofio-input text-xs" placeholder="por defecto según el rango" />
                </div>
                <div class="flex items-center gap-4 text-xs sm:col-span-2">
                    <label class="flex items-center gap-2"><input v-model="createForm.is_staff" type="checkbox" /> Personal del staff</label>
                    <label class="flex items-center gap-2"><input v-model="createForm.auto_promote" type="checkbox" /> Auto-promoción</label>
                    <label class="flex items-center gap-2"><input v-model="createForm.can_send_tips" type="checkbox" /> Puede enviar propinas</label>
                    <label class="flex items-center gap-2"><input v-model="createForm.can_receive_tips" type="checkbox" /> Puede recibir propinas</label>
                </div>

                <!-- Permisos de pestañas del panel admin para rangos staff -->
                <div v-if="createForm.is_staff" class="rounded border border-orange-200 bg-orange-50 p-3 sm:col-span-2">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-orange-900">Pestañas del panel admin</p>
                        <button type="button" class="text-[11px] font-semibold text-fb-link hover:underline" @click="applyModeratorTabs(createForm)">
                            Usar preset Moderador
                        </button>
                    </div>
                    <div v-for="(options, groupName) in adminTabOptions" :key="`create-${groupName}`" class="mb-3 last:mb-0">
                        <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-orange-800">{{ groupName }}</p>
                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="option in options"
                                :key="option.key"
                                class="inline-flex cursor-pointer items-center gap-1 rounded border border-orange-200 bg-white px-2 py-1 text-[11px]"
                            >
                                <input
                                    type="checkbox"
                                    :checked="createForm.admin_permissions.includes(option.key)"
                                    @change="toggleAdminTab(createForm, option.key)"
                                />
                                {{ option.label }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 sm:col-span-2">
                    <button type="submit" class="gofio-btn-primary text-xs" :disabled="createForm.processing">Crear rango</button>
                </div>
                <p v-if="Object.keys(createForm.errors).length" class="text-xs text-red-600 sm:col-span-2">
                    {{ Object.values(createForm.errors)[0] }}
                </p>
            </form>

            <div class="divide-y divide-fb-border">
                <div v-for="rank in ranks" :key="rank.id" class="p-4">
                    <div v-if="editingId !== rank.id" class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <FaIcon :icon="rank.icon" class="text-xl" :style="{ color: rank.color }" />
                            <div>
                                <p class="font-semibold" :style="{ color: rank.color }">
                                    {{ rank.nombre }}
                                    <span class="ml-1 font-mono text-[10px] font-normal text-fb-muted">{{ rank.slug }}</span>
                                </p>
                                <p class="text-xs text-fb-muted">
                                    {{ rank.puntos_requeridos }} karma · voto x{{ rank.poder_voto }} · {{ rank.limite_voto_diario }} votos/día
                                    · {{ rank.max_posts_per_day ?? rank.default_max_posts_per_day }} posts/día
                                    · {{ rank.max_comments_per_day ?? rank.default_max_comments_per_day }} coment./día
                                    · {{ rank.max_content_length ?? rank.default_max_content_length }} carac./post
                                    · {{ rank.users_count }} usuario(s)
                                    <span v-if="rank.is_staff" class="ml-1 rounded bg-orange-100 px-1 text-orange-800">Equipo staff</span>
                                    <span v-if="rank.is_staff && tabCountLabel(rank)" class="ml-1 rounded bg-indigo-100 px-1 text-indigo-800">{{ tabCountLabel(rank) }}</span>
                                    <span v-if="rank.auto_promote" class="ml-1 rounded bg-green-100 px-1 text-green-700">Auto (karma)</span>
                                    <span v-else class="ml-1 rounded bg-slate-100 px-1 text-slate-600">Solo manual</span>
                                    <span v-if="!rank.can_send_tips" class="ml-1 rounded bg-red-100 px-1 text-red-700">Sin enviar propinas</span>
                                    <span v-if="!rank.can_receive_tips" class="ml-1 rounded bg-red-100 px-1 text-red-700">Sin recibir propinas</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button class="gofio-btn-secondary text-xs" @click="assigningId = assigningId === rank.id ? null : rank.id">Asignar</button>
                            <button class="gofio-btn-secondary text-xs" @click="startEdit(rank)">Editar</button>
                            <button class="gofio-btn-secondary text-xs text-red-600" @click="destroyRank(rank)">Eliminar</button>
                        </div>
                    </div>

                    <form v-if="assigningId === rank.id" class="mt-3 flex flex-wrap items-start gap-2 rounded bg-[#F5F6F7] p-3" @submit.prevent="submitAssign(rank)">
                        <div class="w-56"><UserPicker v-model="assignForm.user_id" /></div>
                        <button type="submit" class="gofio-btn-primary text-xs" :disabled="!assignForm.user_id">Asignar y bloquear</button>
                        <p class="w-full text-xs text-fb-muted">
                            Al asignar manualmente, este usuario deja de auto-promocionarse por karma hasta que lo desbloquees.
                        </p>
                    </form>

                    <form v-else-if="editingId === rank.id" class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitEdit(rank)">
                        <div>
                            <label class="gofio-field-label">Nombre del rango</label>
                            <input v-model="editForm.nombre" class="gofio-input text-xs" placeholder="Nombre" />
                        </div>
                        <div>
                            <label class="gofio-field-label">Color</label>
                            <input v-model="editForm.color" class="gofio-input text-xs" placeholder="#0198E7" />
                        </div>
                        <IconPickerField
                            v-model="editForm.icon"
                            label="Icono"
                            default-icon="fa-solid fa-star"
                            :preview-color="editForm.color"
                            required
                        />
                        <div>
                            <label class="gofio-field-label">Karma requerido</label>
                            <input v-model.number="editForm.puntos_requeridos" type="number" class="gofio-input text-xs" placeholder="Karma mín." />
                        </div>
                        <div>
                            <label class="gofio-field-label">Poder de voto</label>
                            <input v-model.number="editForm.poder_voto" type="number" class="gofio-input text-xs" />
                        </div>
                        <div>
                            <label class="gofio-field-label">Límite de votos por día</label>
                            <input v-model.number="editForm.limite_voto_diario" type="number" class="gofio-input text-xs" />
                        </div>
                        <div>
                            <label class="gofio-field-label">Límite de posts por día</label>
                            <input v-model.number="editForm.max_posts_per_day" type="number" class="gofio-input text-xs" :placeholder="`por defecto: ${rank.default_max_posts_per_day}`" />
                        </div>
                        <div>
                            <label class="gofio-field-label">Límite de comentarios por día</label>
                            <input v-model.number="editForm.max_comments_per_day" type="number" class="gofio-input text-xs" :placeholder="`por defecto: ${rank.default_max_comments_per_day}`" />
                        </div>
                        <div>
                            <label class="gofio-field-label">Límite de caracteres por post</label>
                            <input v-model.number="editForm.max_content_length" type="number" class="gofio-input text-xs" :placeholder="`por defecto: ${rank.default_max_content_length}`" />
                        </div>
                        <label class="flex items-center gap-2 text-xs"><input v-model="editForm.is_staff" type="checkbox" /> Personal del staff</label>
                        <label class="flex items-center gap-2 text-xs"><input v-model="editForm.auto_promote" type="checkbox" /> Auto-promoción</label>
                        <label class="flex items-center gap-2 text-xs"><input v-model="editForm.can_send_tips" type="checkbox" /> Puede enviar propinas</label>
                        <label class="flex items-center gap-2 text-xs"><input v-model="editForm.can_receive_tips" type="checkbox" /> Puede recibir propinas</label>

                        <div v-if="editForm.is_staff" class="rounded border border-orange-200 bg-orange-50 p-3 sm:col-span-2">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-semibold text-orange-900">Pestañas del panel admin</p>
                                <button type="button" class="text-[11px] font-semibold text-fb-link hover:underline" @click="applyModeratorTabs(editForm)">
                                    Usar preset Moderador
                                </button>
                            </div>
                            <div v-for="(options, groupName) in adminTabOptions" :key="`edit-${groupName}`" class="mb-3 last:mb-0">
                                <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-orange-800">{{ groupName }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="option in options"
                                        :key="option.key"
                                        class="inline-flex cursor-pointer items-center gap-1 rounded border border-orange-200 bg-white px-2 py-1 text-[11px]"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="editForm.admin_permissions.includes(option.key)"
                                            @change="toggleAdminTab(editForm, option.key)"
                                        />
                                        {{ option.label }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 sm:col-span-2">
                            <button type="submit" class="gofio-btn-primary text-xs" :disabled="editForm.processing">Guardar</button>
                            <button type="button" class="gofio-btn-secondary text-xs" @click="editingId = null">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="gofio-box mt-4 overflow-hidden">
            <button type="button" class="gofio-box-header flex w-full items-center justify-between text-left" @click="showLocked = !showLocked">
                <span>Usuarios con rango bloqueado (asignado manualmente)</span>
                <FaIcon :icon="showLocked ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'" class="text-xs" />
            </button>
            <div v-if="showLocked" class="divide-y divide-fb-border">
                <div v-if="!lockedUsers.data?.length" class="p-4 text-sm text-fb-muted">No hay usuarios bloqueados manualmente.</div>
                <div v-for="u in lockedUsers.data" :key="u.id" class="flex items-center justify-between gap-3 p-3 text-sm">
                    <span>{{ u.username }} — <span class="text-fb-muted">{{ u.rango_nombre }} ({{ u.karma }} karma)</span></span>
                    <button class="gofio-btn-secondary text-xs" @click="unlockUser(u)">Desbloquear (volver a automático)</button>
                </div>
                <!-- Paginación de usuarios con rango bloqueado manualmente -->
                <AdminPagination :paginator="lockedUsers" query-key="locked_page" item-label="usuarios bloqueados" />
            </div>
        </div>
    </AdminLayout>
</template>
