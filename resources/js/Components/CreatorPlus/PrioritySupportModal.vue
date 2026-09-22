<script setup>
/**
 * Modal Creator Plus para acceso a soporte prioritario.
 */

import { ref } from 'vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

const emit = defineEmits(['close', 'submitted']);

const subject = ref('');
const body = ref('');
const loading = ref(false);
const error = ref('');
const success = ref('');


/** Envía el formulario al servidor y gestiona errores de validación. */
const submit = async () => {
    loading.value = true;
    error.value = '';
    success.value = '';

    try {
        const { data } = await window.axios.post('/api/creator-plus/priority-ticket', {
            subject: subject.value,
            body: body.value,
        });
        success.value = data.message;
        subject.value = '';
        body.value = '';
        emit('submitted', data);
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.subject?.[0]
            || e.response?.data?.errors?.body?.[0]
            || 'No se pudo enviar la solicitud.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <!-- Modal de soporte prioritario para suscriptores Creator Plus -->

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="emit('close')">
        <div class="w-full max-w-lg overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="creator-plus-modal-header flex items-center justify-between px-4 py-3 text-white">
                <div class="flex items-center gap-2">
                    <FaIcon icon="fa-solid fa-headset" />
                    <h2 class="text-sm font-bold">Soporte prioritario a moderación</h2>
                </div>
                <button type="button" class="rounded p-1 hover:bg-white/20" @click="emit('close')">
                    <FaIcon icon="fa-solid fa-xmark" />
                </button>
            </div>

            <div class="space-y-3 p-4">
                <p class="text-sm text-fb-muted">
                    Como Creator Plus, tu queja o problema llega con prioridad al equipo de moderación.
                </p>

                <div>
                    <label class="mb-1 block text-xs font-semibold">Asunto</label>
                    <input
                        v-model="subject"
                        type="text"
                        maxlength="120"
                        class="gofio-input w-full text-sm"
                        placeholder="Ej. Reporte mal gestionado, abuso, etc."
                    />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold">Descripción</label>
                    <textarea
                        v-model="body"
                        rows="4"
                        maxlength="2000"
                        class="gofio-input w-full text-sm"
                        placeholder="Explica el problema con el mayor detalle posible..."
                    />
                </div>

                <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
                <p v-if="success" class="text-xs text-green-600">{{ success }}</p>

                <div class="flex flex-wrap gap-2 pt-1">
                    <button
                        type="button"
                        class="gofio-btn-primary text-sm"
                        :disabled="loading || !subject.trim() || !body.trim()"
                        @click="submit"
                    >
                        {{ loading ? 'Enviando...' : 'Enviar solicitud prioritaria' }}
                    </button>
                    <button type="button" class="gofio-btn-secondary text-sm" @click="emit('close')">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.creator-plus-modal-header {
    background: linear-gradient(135deg, #b45309, #fbbf24, #d97706);
}
</style>
