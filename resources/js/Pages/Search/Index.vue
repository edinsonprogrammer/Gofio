<script setup>
/**
 * Página de resultados de búsqueda global: listado de usuarios y publicaciones
 * que coinciden con el término ingresado en el buscador del header.
 */

import { Link } from '@inertiajs/vue3';
import GofioLayout from '@/Layouts/GofioLayout.vue';
import PostCard from '@/Components/Posts/PostCard.vue';
import RankBadge from '@/Components/User/RankBadge.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

defineProps({
    categories: { type: Array, default: () => [] },
    query: { type: String, default: '' },
    posts: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    minLength: { type: Number, default: 2 },
});
</script>

<template>
    <GofioLayout :categories="categories">
        <template #feed>
            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header flex items-center gap-2">
                    <FaIcon icon="fa-solid fa-magnifying-glass" />
                    Resultados de búsqueda
                </div>

                <div class="p-4">
                    <p v-if="!query || query.length < minLength" class="text-sm text-fb-muted">
                        Escribe al menos {{ minLength }} caracteres en el buscador del header para encontrar posts y usuarios.
                    </p>

                    <template v-else>
                        <p class="mb-4 text-sm text-fb-muted">
                            Resultados para <strong class="text-textPrincipal">"{{ query }}"</strong>
                        </p>

                        <!-- Listado de perfiles de usuario coincidentes -->
                        <section v-if="users.length" class="mb-6">
                            <h2 class="mb-2 text-sm font-semibold">Usuarios ({{ users.length }})</h2>
                            <ul class="divide-y divide-fb-border rounded border border-fb-border bg-white">
                                <li v-for="user in users" :key="user.id">
                                    <Link
                                        :href="`/perfil/${user.username}`"
                                        class="flex items-center gap-3 px-4 py-3 gofio-hover-panel"
                                    >
                                        <div class="flex h-10 w-10 items-center justify-center rounded bg-[#DADDE1] text-sm font-bold text-fb-muted">
                                            <img v-if="user.avatar_url" :src="user.avatar_url" alt="" class="h-full w-full rounded object-cover" />
                                            <span v-else>{{ user.username.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-fb-link">
                                                {{ user.username }}
                                                <span v-if="user.nick" class="ml-1 font-normal text-fb-muted">@{{ user.nick }}</span>
                                            </p>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-2">
                                                <RankBadge v-if="user.rango" :rango="user.rango" />
                                                <span class="text-xs text-fb-muted">{{ user.karma }} karma</span>
                                            </div>
                                        </div>
                                    </Link>
                                </li>
                            </ul>
                        </section>

                        <!-- Publicaciones que contienen el término buscado -->
                        <section>
                            <h2 class="mb-2 text-sm font-semibold">Posts ({{ posts.length }})</h2>
                            <div v-if="posts.length" class="space-y-3">
                                <PostCard v-for="post in posts" :key="post.id" :post="post" />
                            </div>
                            <p v-else class="rounded border border-fb-border bg-[#F5F6F7] p-4 text-center text-sm text-fb-muted">
                                No se encontraron posts con ese término.
                            </p>
                        </section>

                        <p v-if="!users.length && !posts.length" class="rounded border border-fb-border bg-[#F5F6F7] p-6 text-center text-sm text-fb-muted">
                            <FaIcon icon="fa-solid fa-magnifying-glass" class="mb-2 text-2xl" />
                            <br>
                            No hay resultados para "{{ query }}".
                        </p>
                    </template>
                </div>
            </div>
        </template>
    </GofioLayout>
</template>
