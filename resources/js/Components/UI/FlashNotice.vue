<script setup>
/**
 * Aviso flash de éxito o error con icono y animación de entrada,
 * usado tras acciones de formulario en toda la aplicación.
 */

import FaIcon from '@/Components/UI/FaIcon.vue';

defineProps({
    message: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: 'success',
    },
});
</script>

<template>
    <!-- Banner de retroalimentación con estilo según tipo (éxito o error) -->
    <div
        class="flash-notice"
        :class="type === 'error' ? 'flash-notice--error' : 'flash-notice--success'"
        role="status"
    >
        <FaIcon
            :icon="type === 'error' ? 'fa-solid fa-circle-xmark' : 'fa-solid fa-circle-check'"
            class="flash-notice__icon"
        />
        <p class="flash-notice__text">{{ message }}</p>
    </div>
</template>

<style scoped>
.flash-notice {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.85rem 1rem;
    border-radius: 0.65rem;
    border: 1px solid transparent;
    box-shadow: 0 8px 24px -14px rgba(15, 23, 42, 0.35);
    animation: flash-notice-in 0.35s ease-out;
}

.flash-notice--success {
    border-color: rgba(13, 148, 136, 0.25);
    background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 55%, #ffffff 100%);
    color: #0f766e;
}

.flash-notice--error {
    border-color: rgba(220, 38, 38, 0.25);
    background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 55%, #ffffff 100%);
    color: #b91c1c;
}

.flash-notice__icon {
    margin-top: 0.1rem;
    font-size: 1rem;
    flex-shrink: 0;
}

.flash-notice__text {
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.45;
}

@keyframes flash-notice-in {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
