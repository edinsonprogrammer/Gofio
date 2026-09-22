<script setup>
/**
 * Modal para denunciar contenido con motivo y descripción.
 */

import { onUnmounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        required: true,
        validator: (value) => ['post', 'comment', 'user'].includes(value),
    },
    targetId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['close', 'submitted']);

const page = usePage();
const reasons = page.props.reportReasons ?? [];

const selectedReason = ref('');
const details = ref('');
const loading = ref(false);
const error = ref('');
const success = ref('');


/** Restablece el formulario de denuncia a su estado inicial. */
const reset = () => {
    selectedReason.value = '';
    details.value = '';
    error.value = '';
    success.value = '';
    loading.value = false;
};


/** Bloquea el scroll del documento mientras el modal está abierto. */
const lockBodyScroll = (locked) => {
    document.body.style.overflow = locked ? 'hidden' : '';
};

watch(() => props.show, (visible) => {
    if (visible) {
        reset();
        lockBodyScroll(true);
        document.addEventListener('keydown', onEscape);
        return;
    }

    lockBodyScroll(false);
    document.removeEventListener('keydown', onEscape);
});


/** Cierra el modal al pulsar la tecla Escape. */
const onEscape = (event) => {
    if (event.key === 'Escape') {
        emit('close');
    }
};

onUnmounted(() => {
    lockBodyScroll(false);
    document.removeEventListener('keydown', onEscape);
});


/** Envía el formulario al servidor y gestiona errores de validación. */
const submit = async () => {
    if (!selectedReason.value) {
        error.value = 'Selecciona un motivo de denuncia.';
        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const { data } = await window.axios.post('/api/reports', {
            type: props.type,
            id: props.targetId,
            reason: selectedReason.value,
            details: details.value.trim() || null,
        });

        success.value = data.message;
        emit('submitted');
        setTimeout(() => emit('close'), 1400);
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.reason?.[0]
            || 'No se pudo enviar la denuncia.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <!-- Formulario modal para enviar una denuncia -->

    <Teleport to="body">
        <div
            v-if="show"
            class="report-modal-overlay"
            @click.self="emit('close')"
        >
            <div
                class="report-modal gofio-box"
                role="dialog"
                aria-modal="true"
                aria-labelledby="report-modal-title"
                @click.stop
            >
    <!-- Cabecera de la sección -->
                <header class="report-modal__header">
                    <div class="report-modal__heading">
                        <h3 id="report-modal-title" class="report-modal__title">Denunciar contenido</h3>
                        <p class="report-modal__subtitle">
                            El equipo de moderación revisará tu reporte.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="report-modal__close"
                        aria-label="Cerrar"
                        @click="emit('close')"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </header>

                <div class="report-modal__body">
                    <label class="report-modal__label" for="report-reason">Motivo</label>
                    <select
                        id="report-reason"
                        v-model="selectedReason"
                        class="gofio-input report-modal__select"
                    >
                        <option value="" disabled>Selecciona un motivo...</option>
                        <option
                            v-for="reason in reasons"
                            :key="reason.key"
                            :value="reason.key"
                        >
                            {{ reason.label }}
                        </option>
                    </select>

                    <template v-if="selectedReason">
                        <label class="report-modal__label report-modal__label--spaced" for="report-details">
                            Detalle (opcional)
                        </label>
                        <textarea
                            id="report-details"
                            v-model="details"
                            rows="2"
                            maxlength="500"
                            class="gofio-input report-modal__textarea"
                            placeholder="Añade contexto si lo necesitas..."
                        />
                    </template>

                    <p v-if="error" class="report-modal__feedback report-modal__feedback--error">{{ error }}</p>
                    <p v-if="success" class="report-modal__feedback report-modal__feedback--success">{{ success }}</p>
                </div>

                <footer class="report-modal__footer">
                    <button type="button" class="gofio-btn-secondary report-modal__btn" @click="emit('close')">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="gofio-btn-primary report-modal__btn"
                        :disabled="loading"
                        @click="submit"
                    >
                        {{ loading ? 'Enviando...' : 'Enviar' }}
                    </button>
                </footer>
            </div>
        </div>
    </Teleport>
</template>
