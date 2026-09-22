<script setup>
/**
 * CRUD de categorías de publicaciones con icono, orden y visibilidad.
 */

import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import IconPickerField from '@/Components/Admin/IconPickerField.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';

const props = defineProps({
    categories: Object,
});

const createForm = useForm({ name: '', icon: 'fa-solid fa-folder' });
const editId = ref(null);
const editForm = useForm({ name: '', icon: '' });


/** Crea un nuevo registro con los datos del formulario. */
const create = () => createForm.post('/admin/categorias', { preserveScroll: true, onSuccess: () => createForm.reset() });

/** Carga una categoría en el formulario de edición inline. */
const startEdit = (cat) => { editId.value = cat.id; editForm.name = cat.name; editForm.icon = cat.icon; };

/** Guarda los cambios de la categoría en edición. */
const saveEdit = () => editForm.put(`/admin/categorias/${editId.value}`, { preserveScroll: true, onSuccess: () => { editId.value = null; } });

/** Elimina una categoría vacía del catálogo. */
const destroy = (cat) => { if (confirm(`¿Eliminar ${cat.name}?`)) router.delete(`/admin/categorias/${cat.id}`); };
</script>

<template>
    <!-- CRUD de categorías de publicaciones -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header">Categorías (p_categorias)</div>
    <!-- Formulario principal -->
            <form class="flex flex-wrap items-end gap-2 border-b border-fb-border bg-[#F5F6F7] p-3" @submit.prevent="create">
                <div>
                    <label class="gofio-field-label">Nombre de la categoría</label>
                    <input v-model="createForm.name" placeholder="Ej. Tecnología" class="gofio-input text-xs" required />
                </div>
                <IconPickerField
                    v-model="createForm.icon"
                    label="Icono"
                    default-icon="fa-solid fa-folder"
                    required
                />
                <button type="submit" class="gofio-btn-primary text-xs">Crear categoría</button>
            </form>
            <ul class="divide-y divide-fb-border">
                <li v-for="cat in categories.data" :key="cat.id" class="flex items-center justify-between px-4 py-3 text-sm">
                    <span class="flex items-center"><FaIcon :icon="cat.icon" class="mr-2" />{{ cat.name }} <span class="text-fb-muted">({{ cat.slug }})</span></span>
                    <div class="flex gap-2">
                        <button type="button" class="text-xs text-fb-link hover:underline" @click="startEdit(cat)">Editar</button>
                        <button type="button" class="text-xs text-red-600 hover:underline" @click="destroy(cat)">Eliminar</button>
                    </div>
                </li>
            </ul>
            <!-- Paginación de categorías -->
            <AdminPagination :paginator="categories" item-label="categorías" />
        </div>
        <div v-if="editId" class="gofio-box mt-4 p-4">
            <p class="mb-2 text-sm font-semibold">Editar categoría</p>
            <div class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="gofio-field-label">Nombre de la categoría</label>
                    <input v-model="editForm.name" class="gofio-input text-xs" />
                </div>
                <IconPickerField
                    v-model="editForm.icon"
                    label="Icono"
                    default-icon="fa-solid fa-folder"
                    required
                />
                <button type="button" class="gofio-btn-primary text-xs" @click="saveEdit">Guardar</button>
            </div>
        </div>
    </AdminLayout>
</template>
