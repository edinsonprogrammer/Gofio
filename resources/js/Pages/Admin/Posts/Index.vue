<script setup>
/**
 * Moderación de publicaciones: listado, filtros, destacar, fijar y banear posts.
 */

import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import { label, postStatusLabels } from '@/utils/adminLabels';

defineProps({
    posts: Object,
    filters: Object,
});

const status = ref('');
const q = ref('');


/** Recarga el listado de publicaciones con los filtros activos. */
const reload = () => router.get('/admin/posts', { status: status.value, q: q.value }, { preserveState: true });

/** Banea o desbanea una publicación desde el panel admin. */
const ban = (post) => router.post(`/admin/posts/${post.id}/banear`, { reason: 'Contenido inapropiado' }, { preserveScroll: true });

/** Valida el borrador y crea la publicación en el backend. */
const publish = (post) => router.post(`/admin/posts/${post.id}/publicar`, {}, { preserveScroll: true });

/** Marca o quita una publicación como destacada. */
const feature = (post) => router.post(`/admin/posts/${post.id}/destacar`, {}, { preserveScroll: true });

/** Fija o desfija una publicación en el feed. */
const sticky = (post) => router.post(`/admin/posts/${post.id}/sticky`, {}, { preserveScroll: true });
</script>

<template>
    <!-- Listado administrativo de publicaciones -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header">Publicaciones</div>
            <div class="flex flex-wrap gap-2 border-b border-fb-border p-3">
                <select v-model="status" class="gofio-input w-auto text-xs" @change="reload">
                    <option value="">Todos</option>
                    <option value="published">Publicados</option>
                    <option value="banned">Baneados</option>
                    <option value="draft">Borradores</option>
                </select>
                <input v-model="q" type="search" placeholder="Título..." class="gofio-input flex-1 text-xs" @keyup.enter="reload" />
                <button type="button" class="gofio-btn-primary text-xs" @click="reload">Filtrar</button>
            </div>

            <div v-for="post in posts.data" :key="post.id" class="border-b border-fb-border p-4 last:border-0">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <Link :href="`/post/${post.slug}`" class="font-semibold text-fb-link hover:underline">{{ post.title }}</Link>
                        <p class="text-xs text-fb-muted">
                            @{{ post.user?.username }} · {{ post.category?.name }} · {{ post.points_count }} puntos
                            · <span :class="post.status === 'banned' ? 'text-red-600' : ''">{{ label(postStatusLabels, post.status) }}</span>
                        </p>
                        <p class="mt-1 text-xs">
                            <span v-if="post.is_featured" class="mr-2 text-amber-600">★ Destacado</span>
                            <span v-if="post.is_sticky" class="text-blue-600">📌 Fijado</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-1">
                        <button type="button" class="gofio-btn-secondary px-2 py-1 text-xs" @click="feature(post)">Destacar</button>
                        <button type="button" class="gofio-btn-secondary px-2 py-1 text-xs" @click="sticky(post)">Fijar</button>
                        <button v-if="post.status !== 'banned'" type="button" class="gofio-btn-primary px-2 py-1 text-xs" @click="ban(post)">Banear</button>
                        <button v-else type="button" class="gofio-btn-primary px-2 py-1 text-xs" @click="publish(post)">Publicar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Paginación de publicaciones -->
        <AdminPagination :paginator="posts" item-label="publicaciones" />
    </AdminLayout>
</template>
