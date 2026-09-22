<script setup>
/**
 * Gestión de medallas: condiciones de desbloqueo, iconos y asignación manual.
 */

import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import IconPickerField from '@/Components/Admin/IconPickerField.vue';
import UserPicker from '@/Components/Admin/UserPicker.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import { label, medalConditionLabels } from '@/utils/adminLabels';

defineProps({
    medals: { type: Object, required: true },
});

const editingId = ref(null);
const assignMedalId = ref(null);

const createForm = useForm({
    title: '',
    description: '',
    icon: 'fa-solid fa-medal',
    color: '#F39C12',
    condition_type: 'manual',
    condition_value: 0,
    sort_order: 0,
});

const editForm = useForm({
    title: '',
    description: '',
    icon: '',
    color: '',
    condition_type: 'manual',
    condition_value: 0,
    is_active: true,
    sort_order: 0,
});

const assignForm = useForm({ user_id: '', note: '' });


/** Carga los datos de la medalla en el formulario de edición. */
const startEdit = (medal) => {
    editingId.value = medal.id;
    editForm.title = medal.title;
    editForm.description = medal.description;
    editForm.icon = medal.icon;
    editForm.color = medal.color;
    editForm.condition_type = medal.condition_type;
    editForm.condition_value = medal.condition_value;
    editForm.is_active = medal.is_active;
    editForm.sort_order = medal.sort_order;
};


/** Crea una medalla nueva con los datos del formulario. */
const submitCreate = () => createForm.post('/admin/medallas');

/** Guarda los cambios de una medalla existente. */
const submitEdit = (medal) => editForm.put(`/admin/medallas/${medal.id}`, { onSuccess: () => { editingId.value = null; } });

/** Otorga manualmente una medalla a un usuario. */
const submitAssign = (medal) => {
    if (!assignForm.user_id) return;
    assignForm.post(`/admin/medallas/${medal.id}/asignar`, { onSuccess: () => { assignMedalId.value = null; assignForm.reset(); } });
};
</script>

<template>
    <!-- Gestión de medallas y asignación manual -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header">Medallas</div>
    <!-- Formulario principal -->
            <form class="grid gap-3 border-b border-fb-border bg-[#F5F6F7] p-3 sm:grid-cols-3" @submit.prevent="submitCreate">
                <div>
                    <label class="gofio-field-label">Título</label>
                    <input v-model="createForm.title" placeholder="Ej. Colaborador" class="gofio-input text-xs" required />
                </div>
                <div class="sm:col-span-2">
                    <label class="gofio-field-label">Descripción</label>
                    <input v-model="createForm.description" placeholder="Qué representa esta medalla" class="gofio-input text-xs" />
                </div>
                <IconPickerField
                    v-model="createForm.icon"
                    label="Icono"
                    default-icon="fa-solid fa-medal"
                    :preview-color="createForm.color"
                    required
                />
                <div>
                    <label class="gofio-field-label">Color</label>
                    <input v-model="createForm.color" placeholder="#F39C12" class="gofio-input text-xs" required />
                </div>
                <div>
                    <label class="gofio-field-label">Condición para otorgar</label>
                    <select v-model="createForm.condition_type" class="gofio-input text-xs">
                        <option value="manual">{{ medalConditionLabels.manual }}</option>
                        <option value="karma">{{ medalConditionLabels.karma }}</option>
                        <option value="posts">{{ medalConditionLabels.posts }}</option>
                        <option value="comments">{{ medalConditionLabels.comments }}</option>
                        <option value="verified">{{ medalConditionLabels.verified }}</option>
                    </select>
                </div>
                <div v-if="createForm.condition_type !== 'manual'">
                    <label class="gofio-field-label">Valor requerido</label>
                    <input v-model.number="createForm.condition_value" type="number" class="gofio-input text-xs" placeholder="Ej. 500" />
                </div>
                <div class="flex sm:col-span-3">
                    <button type="submit" class="gofio-btn-primary text-xs" :disabled="createForm.processing">Crear medalla</button>
                </div>
            </form>

            <div class="divide-y divide-fb-border">
                <div v-for="medal in medals.data" :key="medal.id" class="p-4">
                    <div v-if="editingId !== medal.id" class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <FaIcon :icon="medal.icon" class="text-xl" :style="{ color: medal.color }" />
                            <div>
                                <p class="font-semibold">{{ medal.title }} <span class="ml-1 font-mono text-[10px] font-normal text-fb-muted">{{ medal.slug }}</span></p>
                                <p class="text-xs text-fb-muted">{{ medal.description }} · {{ label(medalConditionLabels, medal.condition_type) }} ({{ medal.condition_value }}) · {{ medal.users_count }} otorgada(s)</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button class="gofio-btn-secondary text-xs" @click="assignMedalId = assignMedalId === medal.id ? null : medal.id">Asignar</button>
                            <button class="gofio-btn-secondary text-xs" @click="startEdit(medal)">Editar</button>
                        </div>
                    </div>

                    <form v-if="assignMedalId === medal.id" class="mt-3 flex flex-wrap items-start gap-2 rounded bg-[#F5F6F7] p-3" @submit.prevent="submitAssign(medal)">
                        <div class="w-56"><UserPicker v-model="assignForm.user_id" /></div>
                        <input v-model="assignForm.note" placeholder="Nota opcional" class="gofio-input w-40 text-xs" />
                        <button type="submit" class="gofio-btn-primary text-xs" :disabled="!assignForm.user_id">Otorgar</button>
                    </form>

                    <form v-if="editingId === medal.id" class="mt-3 grid gap-3 sm:grid-cols-2" @submit.prevent="submitEdit(medal)">
                        <div>
                            <label class="gofio-field-label">Título</label>
                            <input v-model="editForm.title" class="gofio-input text-xs" />
                        </div>
                        <div>
                            <label class="gofio-field-label">Descripción</label>
                            <input v-model="editForm.description" class="gofio-input text-xs" />
                        </div>
                        <IconPickerField
                            v-model="editForm.icon"
                            label="Icono"
                            default-icon="fa-solid fa-medal"
                            :preview-color="editForm.color"
                            required
                        />
                        <div>
                            <label class="gofio-field-label">Color</label>
                            <input v-model="editForm.color" class="gofio-input text-xs" />
                        </div>
                        <div>
                            <label class="gofio-field-label">Condición para otorgar</label>
                            <select v-model="editForm.condition_type" class="gofio-input text-xs">
                                <option value="manual">{{ medalConditionLabels.manual }}</option>
                                <option value="karma">{{ medalConditionLabels.karma }}</option>
                                <option value="posts">{{ medalConditionLabels.posts }}</option>
                                <option value="comments">{{ medalConditionLabels.comments }}</option>
                                <option value="verified">{{ medalConditionLabels.verified }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="gofio-field-label">Valor requerido</label>
                            <input v-model.number="editForm.condition_value" type="number" class="gofio-input text-xs" />
                        </div>
                        <label class="flex items-center gap-2 text-xs"><input v-model="editForm.is_active" type="checkbox" /> Activa</label>
                        <div class="flex gap-2 sm:col-span-2">
                            <button type="submit" class="gofio-btn-primary text-xs">Guardar</button>
                            <button type="button" class="gofio-btn-secondary text-xs" @click="editingId = null">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Paginación de medallas -->
            <AdminPagination :paginator="medals" item-label="medallas" />
        </div>
    </AdminLayout>
</template>
