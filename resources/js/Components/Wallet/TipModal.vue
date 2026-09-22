<script setup>
/**
 * Modal para enviar propinas en monedas al autor de una publicación
 * con validación de monto y retroalimentación de éxito o error.
 */

import { ref, watch } from 'vue';

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'sent']);

const amount = ref(10);
const loading = ref(false);
const error = ref('');
const success = ref('');


/** Envía la propina a la API y cierra el modal tras confirmación. */
const submit = async () => {
    loading.value = true;
    error.value = '';
    success.value = '';

    try {
        const { data } = await window.axios.post(`/api/posts/${props.post.id}/tip`, {
            amount: amount.value,
        });
        success.value = data.message;
        emit('sent', data);
        setTimeout(() => emit('close'), 1500);
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.amount?.[0]
            || 'Error al enviar propina.';
    } finally {
        loading.value = false;
    }
};

watch(() => props.show, (val) => {
    if (val) {
        error.value = '';
        success.value = '';
    }
});
</script>

<template>
    <!-- Diálogo modal para confirmar monto y enviar propina -->
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4"
            @click.self="emit('close')"
        >
            <div class="gofio-box w-full max-w-sm p-5">
                <h3 class="text-lg font-bold">Enviar propina</h3>
                <p class="mt-1 text-sm text-fb-muted">
                    Apoya a <strong>{{ post.user.username }}</strong> por su post
                    «{{ post.title }}».
                </p>

                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium">Monto (monedas)</label>
                    <input
                        v-model.number="amount"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="gofio-input"
                    />
                    <p class="mt-1 text-xs text-fb-muted">Comisión plataforma: 10%</p>
                </div>

                <p v-if="error" class="mt-2 text-xs text-red-600">{{ error }}</p>
                <p v-if="success" class="mt-2 text-xs text-green-600">{{ success }}</p>

                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="gofio-btn-secondary" @click="emit('close')">
                        Cancelar
                    </button>
                    <button type="button" class="gofio-btn-primary" :disabled="loading" @click="submit">
                        {{ loading ? 'Enviando...' : 'Enviar propina' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
