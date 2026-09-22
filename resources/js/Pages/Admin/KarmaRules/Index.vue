<script setup>
/**
 * Panel CRUD de reglas de karma.
 * Permite crear reglas personalizadas, editar parámetros de cualquier regla
 * y eliminar las reglas no pertenecientes al sistema.
 */

import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

const props = defineProps({
    rules: { type: Object, required: true },
    triggerTypes: { type: Object, required: true },
    dedupModes: { type: Object, required: true },
});

// ─── Estado local ────────────────────────────────────────────────────────────

/** ID de la regla que está siendo editada en línea. null = ninguna. */
const editingId = ref(null);

/** Formulario de creación de nueva regla. */
const createForm = useForm({
    key: '',
    trigger_type: '',
    label: '',
    description: '',
    karma_points: 5,
    enabled: true,
    dedup_mode: 'per_reference',
    conditions: { min_reactions: null, min_likes: null, every: null, max_per_day: null },
    sort_order: 0,
});

/** Formulario de edición — se rellena al abrir el modal de edición. */
const editForm = useForm({
    trigger_type: '',
    label: '',
    description: '',
    karma_points: 0,
    enabled: true,
    dedup_mode: 'per_reference',
    conditions: { min_reactions: null, min_likes: null, every: null, max_per_day: null },
    sort_order: 0,
});

/** Datos de la regla actualmente en edición. */
const editingRule = ref(null);

// ─── Computed ────────────────────────────────────────────────────────────────

/** Lista de opciones de trigger para el select. */
const triggerOptions = computed(() =>
    Object.entries(props.triggerTypes).map(([value, label]) => ({ value, label }))
);

/** Lista de opciones de dedup mode para el select. */
const dedupOptions = computed(() =>
    Object.entries(props.dedupModes).map(([value, label]) => ({ value, label }))
);

/** Determina si el trigger_type activo en el formulario de creación tiene condiciones opcionales. */
const createNeedsMinReactions = computed(() => createForm.trigger_type === 'popular_post');
const createNeedsMinLikes = computed(() => createForm.trigger_type === 'vidu_popular');
const createNeedsEvery = computed(() => createForm.trigger_type === 'followers_milestone');

/** Igual pero para el formulario de edición. */
const editNeedsMinReactions = computed(() => editForm.trigger_type === 'popular_post');
const editNeedsMinLikes = computed(() => editForm.trigger_type === 'vidu_popular');
const editNeedsEvery = computed(() => editForm.trigger_type === 'followers_milestone');

// ─── Helpers de color para badges ────────────────────────────────────────────

/** Devuelve clases de color según el trigger_type. */
function triggerBadgeClass(type) {
    const map = {
        post_created:        'bg-blue-100 text-blue-700',
        comment_created:     'bg-indigo-100 text-indigo-700',
        profile_completed:   'bg-green-100 text-green-700',
        video_in_post:       'bg-purple-100 text-purple-700',
        popular_post:        'bg-yellow-100 text-yellow-700',
        followers_milestone: 'bg-pink-100 text-pink-700',
        tip_sent:            'bg-orange-100 text-orange-700',
        tip_received:        'bg-teal-100 text-teal-700',
    };
    return map[type] ?? 'bg-gray-100 text-gray-600';
}

/** Devuelve clases de color según el dedup_mode. */
function dedupBadgeClass(mode) {
    const map = {
        once:          'bg-red-100 text-red-700',
        per_reference: 'bg-blue-100 text-blue-700',
        unlimited:     'bg-green-100 text-green-700',
    };
    return map[mode] ?? 'bg-gray-100 text-gray-600';
}

// ─── Acciones ────────────────────────────────────────────────────────────────

/** Envía el formulario de creación. */
function submitCreate() {
    createForm.post('/admin/karma', {
        onSuccess: () => {
            createForm.reset();
            createForm.conditions = { min_reactions: null, every: null, max_per_day: null };
        },
    });
}

/** Abre el formulario de edición para la regla indicada. */
function startEdit(rule) {
    editingId.value = rule.id;
    editingRule.value = rule;
    editForm.trigger_type = rule.trigger_type;
    editForm.label = rule.label;
    editForm.description = rule.description ?? '';
    editForm.karma_points = rule.karma_points;
    editForm.enabled = rule.enabled;
    editForm.dedup_mode = rule.dedup_mode;
    editForm.sort_order = rule.sort_order;
    editForm.conditions = {
        min_reactions: rule.conditions?.min_reactions ?? null,
        min_likes:     rule.conditions?.min_likes ?? null,
        every:         rule.conditions?.every ?? null,
        max_per_day:   rule.conditions?.max_per_day ?? null,
    };
}

/** Cancela la edición en curso. */
function cancelEdit() {
    editingId.value = null;
    editingRule.value = null;
    editForm.reset();
}

/** Guarda los cambios del formulario de edición. */
function submitEdit(rule) {
    editForm.put(`/admin/karma/${rule.id}`, {
        onSuccess: () => cancelEdit(),
    });
}

/** Elimina una regla personalizada tras confirmación. */
function destroyRule(rule) {
    if (rule.is_system) return;
    if (! confirm(`¿Eliminar la regla "${rule.label}"? Esta acción no se puede deshacer.`)) return;
    router.delete(`/admin/karma/${rule.id}`);
}

/** Formatea el resumen de condiciones como texto legible. */
function conditionsSummary(rule) {
    const c = rule.conditions;
    if (! c) return null;
    const parts = [];
    if (c.min_reactions) parts.push(`Mín. ${c.min_reactions} reacciones`);
    if (c.min_likes)     parts.push(`Mín. ${c.min_likes} likes`);
    if (c.every)         parts.push(`Cada ${c.every} seguidores`);
    if (c.max_per_day)   parts.push(`Máx. ${c.max_per_day}/día`);
    return parts.join(' · ') || null;
}
</script>

<template>
    <!-- Página principal de reglas de karma -->
    <AdminLayout>
        <div class="mx-auto max-w-5xl space-y-6 p-4">

            <!-- Encabezado -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold">Reglas de karma</h1>
                    <p class="mt-1 text-sm text-fb-muted">
                        Define cuántos puntos obtiene el usuario por cada acción.
                        Las reglas del sistema no se pueden eliminar.
                    </p>
                </div>
                <span class="rounded-full bg-fb-accent/10 px-3 py-1 text-sm font-semibold text-fb-accent">
                    {{ rules.total }} regla{{ rules.total !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- Formulario: crear nueva regla -->
            <div class="rounded-lg border border-fb-border bg-white p-5 shadow-sm">
                <h2 class="mb-4 font-semibold">Nueva regla</h2>
                <form @submit.prevent="submitCreate" class="space-y-4">

                    <!-- Primera fila: key + trigger + label -->
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">
                                Identificador <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="createForm.key"
                                class="gofio-input font-mono text-sm"
                                placeholder="mi_regla_custom"
                                pattern="[a-z0-9_]+"
                                title="Solo letras minúsculas, números y guiones bajos"
                                required
                            />
                            <p v-if="createForm.errors.key" class="mt-1 text-xs text-red-500">{{ createForm.errors.key }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">
                                Trigger (cuándo se dispara) <span class="text-red-500">*</span>
                            </label>
                            <select v-model="createForm.trigger_type" class="gofio-input" required>
                                <option value="">— Seleccionar —</option>
                                <option v-for="opt in triggerOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <p v-if="createForm.errors.trigger_type" class="mt-1 text-xs text-red-500">{{ createForm.errors.trigger_type }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">
                                Etiqueta <span class="text-red-500">*</span>
                            </label>
                            <input v-model="createForm.label" class="gofio-input" placeholder="Ej: Bonus post viral" required maxlength="120" />
                            <p v-if="createForm.errors.label" class="mt-1 text-xs text-red-500">{{ createForm.errors.label }}</p>
                        </div>
                    </div>

                    <!-- Segunda fila: descripción -->
                    <div>
                        <label class="mb-1 block text-xs font-medium text-fb-muted">Descripción (opcional)</label>
                        <input v-model="createForm.description" class="gofio-input" placeholder="Explicación visible para otros admins" maxlength="255" />
                    </div>

                    <!-- Tercera fila: karma + dedup + sort + enabled -->
                    <div class="grid gap-3 sm:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">
                                Puntos de karma <span class="text-red-500">*</span>
                            </label>
                            <input v-model.number="createForm.karma_points" type="number" min="0" max="10000" class="gofio-input" required />
                            <p v-if="createForm.errors.karma_points" class="mt-1 text-xs text-red-500">{{ createForm.errors.karma_points }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">Deduplicación</label>
                            <select v-model="createForm.dedup_mode" class="gofio-input">
                                <option v-for="opt in dedupOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">Orden</label>
                            <input v-model.number="createForm.sort_order" type="number" min="0" class="gofio-input" />
                        </div>

                        <div class="flex items-end pb-1">
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input v-model="createForm.enabled" type="checkbox" class="h-4 w-4" />
                                Activa
                            </label>
                        </div>
                    </div>

                    <!-- Condiciones específicas por trigger -->
                    <div v-if="createNeedsMinReactions || createNeedsMinLikes || createNeedsEvery" class="grid gap-3 sm:grid-cols-3">
                        <div v-if="createNeedsMinReactions">
                            <label class="mb-1 block text-xs font-medium text-fb-muted">Mín. reacciones requeridas</label>
                            <input v-model.number="createForm.conditions.min_reactions" type="number" min="1" class="gofio-input" placeholder="10" />
                        </div>
                        <div v-if="createNeedsMinLikes">
                            <label class="mb-1 block text-xs font-medium text-fb-muted">Mín. likes requeridos (Vidu)</label>
                            <input v-model.number="createForm.conditions.min_likes" type="number" min="1" class="gofio-input" placeholder="10" />
                        </div>
                        <div v-if="createNeedsEvery">
                            <label class="mb-1 block text-xs font-medium text-fb-muted">Cada N seguidores</label>
                            <input v-model.number="createForm.conditions.every" type="number" min="1" class="gofio-input" placeholder="10" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">Máx. por día (opcional)</label>
                            <input v-model.number="createForm.conditions.max_per_day" type="number" min="1" class="gofio-input" placeholder="Sin límite" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="gofio-btn-primary"
                            :disabled="createForm.processing"
                        >
                            <FaIcon icon="fa-solid fa-plus" class="mr-1" /> Crear regla
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de reglas existentes -->
            <div class="overflow-hidden rounded-lg border border-fb-border bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="border-b border-fb-border bg-[#F5F6F7] text-xs uppercase text-fb-muted">
                        <tr>
                            <th class="px-4 py-3 text-left">Regla</th>
                            <th class="px-4 py-3 text-left">Trigger</th>
                            <th class="px-4 py-3 text-center">Karma</th>
                            <th class="px-4 py-3 text-left">Dedup</th>
                            <th class="px-4 py-3 text-left">Condiciones</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-fb-border">

                        <!-- Fila normal (sin edición) -->
                        <template v-for="rule in rules.data" :key="rule.id">
                            <tr v-if="editingId !== rule.id" class="hover:bg-fb-hover/50 transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-medium">{{ rule.label }}</p>
                                    <p class="font-mono text-xs text-fb-muted">{{ rule.key }}</p>
                                    <p v-if="rule.description" class="mt-0.5 text-xs text-fb-muted">{{ rule.description }}</p>
                                    <span v-if="rule.is_system" class="mt-1 inline-block rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-500">
                                        Sistema
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-block rounded px-2 py-0.5 text-xs font-medium', triggerBadgeClass(rule.trigger_type)]">
                                        {{ triggerTypes[rule.trigger_type] ?? rule.trigger_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-sm font-bold text-amber-700">
                                        +{{ rule.karma_points }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-block rounded px-2 py-0.5 text-xs', dedupBadgeClass(rule.dedup_mode)]">
                                        {{ dedupModes[rule.dedup_mode] ?? rule.dedup_mode }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-fb-muted">
                                    {{ conditionsSummary(rule) ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="rule.enabled ? 'text-green-600' : 'text-gray-400'">
                                        <FaIcon :icon="rule.enabled ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'" />
                                        {{ rule.enabled ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="rounded px-2 py-1 text-xs font-medium text-fb-link hover:underline"
                                            @click="startEdit(rule)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            v-if="!rule.is_system"
                                            type="button"
                                            class="rounded px-2 py-1 text-xs font-medium text-red-500 hover:underline"
                                            @click="destroyRule(rule)"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Fila en modo edición inline -->
                            <tr v-else class="bg-blue-50">
                                <td colspan="7" class="px-4 py-4">
                                    <form @submit.prevent="submitEdit(rule)" class="space-y-3">
                                        <p class="text-xs font-semibold text-fb-muted">
                                            Editando: <span class="font-mono text-blue-700">{{ rule.key }}</span>
                                        </p>

                                        <div class="grid gap-3 sm:grid-cols-3">
                                            <!-- Trigger type (solo editable en reglas custom) -->
                                            <div v-if="!rule.is_system">
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Trigger</label>
                                                <select v-model="editForm.trigger_type" class="gofio-input">
                                                    <option v-for="opt in triggerOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                                </select>
                                            </div>
                                            <div v-else>
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Trigger (fijo en reglas del sistema)</label>
                                                <p class="rounded border border-fb-border bg-white px-3 py-2 text-sm">
                                                    <span :class="['inline-block rounded px-2 py-0.5 text-xs font-medium', triggerBadgeClass(rule.trigger_type)]">
                                                        {{ triggerTypes[rule.trigger_type] ?? rule.trigger_type }}
                                                    </span>
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Etiqueta</label>
                                                <input v-model="editForm.label" class="gofio-input" required maxlength="120" />
                                                <p v-if="editForm.errors.label" class="mt-1 text-xs text-red-500">{{ editForm.errors.label }}</p>
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Descripción</label>
                                                <input v-model="editForm.description" class="gofio-input" maxlength="255" />
                                            </div>
                                        </div>

                                        <div class="grid gap-3 sm:grid-cols-4">
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Puntos de karma</label>
                                                <input v-model.number="editForm.karma_points" type="number" min="0" max="10000" class="gofio-input" />
                                                <p v-if="editForm.errors.karma_points" class="mt-1 text-xs text-red-500">{{ editForm.errors.karma_points }}</p>
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Deduplicación</label>
                                                <select v-model="editForm.dedup_mode" class="gofio-input">
                                                    <option v-for="opt in dedupOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Orden</label>
                                                <input v-model.number="editForm.sort_order" type="number" min="0" class="gofio-input" />
                                            </div>

                                            <div class="flex items-end pb-1">
                                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                                    <input v-model="editForm.enabled" type="checkbox" class="h-4 w-4" />
                                                    Activa
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Condiciones específicas al editar -->
                                        <div v-if="editNeedsMinReactions || editNeedsMinLikes || editNeedsEvery" class="grid gap-3 sm:grid-cols-3">
                                            <div v-if="editNeedsMinReactions">
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Mín. reacciones</label>
                                                <input v-model.number="editForm.conditions.min_reactions" type="number" min="1" class="gofio-input" />
                                            </div>
                                            <div v-if="editNeedsMinLikes">
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Mín. likes (Vidu)</label>
                                                <input v-model.number="editForm.conditions.min_likes" type="number" min="1" class="gofio-input" />
                                            </div>
                                            <div v-if="editNeedsEvery">
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Cada N seguidores</label>
                                                <input v-model.number="editForm.conditions.every" type="number" min="1" class="gofio-input" />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-fb-muted">Máx. por día</label>
                                                <input v-model.number="editForm.conditions.max_per_day" type="number" min="1" class="gofio-input" placeholder="Sin límite" />
                                            </div>
                                        </div>

                                        <div class="flex gap-2">
                                            <button type="submit" class="gofio-btn-primary text-sm" :disabled="editForm.processing">
                                                Guardar cambios
                                            </button>
                                            <button type="button" class="rounded border border-fb-border px-3 py-1.5 text-sm hover:bg-white" @click="cancelEdit">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="!rules.data?.length">
                            <td colspan="7" class="px-4 py-8 text-center text-fb-muted">
                                No hay reglas de karma configuradas.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <AdminPagination :paginator="rules" item-label="reglas" />

        </div>
    </AdminLayout>
</template>
