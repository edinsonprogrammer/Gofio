<script setup>
/**
 * Modal Creator Plus con estadísticas de visitas al perfil.
 */

import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import FaIcon from '@/Components/UI/FaIcon.vue';
import ListRowSkeleton from '@/Components/UI/ListRowSkeleton.vue';
import VerificationBadge from '@/Components/User/VerificationBadge.vue';

const emit = defineEmits(['close']);

const loading = ref(true);
const visits = ref([]);
const error = ref('');


/** Formatea una fecha ISO en formato local español. */
const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString('es-ES', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

onMounted(async () => {
    try {
        const { data } = await window.axios.get('/api/creator-plus/profile-visits');
        const payload = data.data;
        visits.value = Array.isArray(payload) ? payload : (payload?.data ?? []);
    } catch (e) {
        error.value = e.response?.data?.message || 'No se pudieron cargar las visitas.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <!-- Modal con estadísticas de visitas al perfil -->

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="emit('close')">
        <div class="flex max-h-[min(80vh,560px)] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="creator-plus-modal-header flex shrink-0 items-center justify-between px-4 py-3 text-white">
                <div class="flex items-center gap-2">
                    <FaIcon icon="fa-solid fa-eye" />
                    <h2 class="text-sm font-bold">Visitas a tu perfil</h2>
                </div>
                <button type="button" class="rounded p-1 hover:bg-white/20" @click="emit('close')">
                    <FaIcon icon="fa-solid fa-xmark" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <p class="mb-3 text-sm text-fb-muted">
                    Solo Creator Plus recibe notificaciones cuando alguien visita su perfil.
                </p>

                <ListRowSkeleton v-if="loading" :count="5" />
                <p v-else-if="error" class="text-sm text-red-600">{{ error }}</p>
                <p v-else-if="!visits.length" class="text-sm text-fb-muted">Aún no hay visitas registradas.</p>

                <ul v-else class="divide-y divide-fb-border">
                    <li v-for="visit in visits" :key="visit.id" class="flex items-center gap-3 py-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#DADDE1] text-sm font-bold text-fb-muted">
                            <img v-if="visit.visitor?.avatar_url" :src="visit.visitor.avatar_url" alt="" class="h-full w-full object-cover" />
                            <span v-else>{{ visit.visitor?.username?.charAt(0)?.toUpperCase() }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <Link
                                :href="`/perfil/${visit.visitor.username}`"
                                class="inline-flex items-center gap-1 text-sm font-semibold text-fb-link hover:underline"
                            >
                                {{ visit.visitor.username }}
                                <VerificationBadge :tipo="visit.visitor.tipo_verificacion" size="xs" />
                            </Link>
                            <p class="text-xs text-fb-muted">{{ formatDate(visit.visited_at) }}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped>
.creator-plus-modal-header {
    background: linear-gradient(135deg, #b45309, #fbbf24, #d97706);
}
</style>
