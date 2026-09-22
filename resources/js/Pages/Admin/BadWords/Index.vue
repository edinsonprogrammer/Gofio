<script setup>
/**
 * Lista de palabras prohibidas con acción de filtrado o bloqueo de contenido.
 */

import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import { badWordActionLabels, label } from '@/utils/adminLabels';

defineProps({ words: Object });

const form = useForm({ word: '', action: 'filter' });

/** Añade una palabra nueva a la lista de filtrado. */
const add = () => form.post('/admin/palabras', { preserveScroll: true, onSuccess: () => form.reset('word') });

/** Elimina una palabra prohibida del listado. */
const remove = (w) => router.delete(`/admin/palabras/${w.id}`);
</script>

<template>
    <!-- Listado y alta de palabras prohibidas -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header">Palabras prohibidas</div>
    <!-- Formulario principal -->
            <form class="flex flex-wrap items-end gap-2 border-b border-fb-border bg-[#F5F6F7] p-3" @submit.prevent="add">
                <div>
                    <label class="gofio-field-label">Palabra a filtrar/bloquear</label>
                    <input v-model="form.word" placeholder="Palabra..." class="gofio-input text-xs" required />
                </div>
                <div>
                    <label class="gofio-field-label">Acción</label>
                    <select v-model="form.action" class="gofio-input w-auto text-xs">
                        <option value="filter">Filtrar (se censura)</option>
                        <option value="block">Bloquear (no se permite publicar)</option>
                    </select>
                </div>
                <button type="submit" class="gofio-btn-primary text-xs">Añadir</button>
            </form>
            <ul class="divide-y divide-fb-border">
                <li v-for="w in words.data" :key="w.id" class="flex items-center justify-between px-4 py-2 text-sm">
                    <span>{{ w.word }} <span class="text-xs text-fb-muted">({{ label(badWordActionLabels, w.action) }})</span></span>
                    <button type="button" class="text-xs text-red-600 hover:underline" @click="remove(w)">Eliminar</button>
                </li>
            </ul>
            <!-- Paginación de palabras prohibidas -->
            <AdminPagination :paginator="words" item-label="palabras" />
        </div>
    </AdminLayout>
</template>
