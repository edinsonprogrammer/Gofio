<script setup>
/**
 * Cuadro flotante con leyenda y descripción breve para medallas y premios del perfil.
 */

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    dateLabel: {
        type: String,
        default: '',
    },
    accentColor: {
        type: String,
        default: '#0D9488',
    },
});
</script>

<template>
    <!-- Leyenda emergente al pasar el cursor sobre la medalla o premio -->
    <div
        class="profile-achievement-tooltip"
        role="tooltip"
        :style="{ '--accent-color': accentColor }"
    >
        <span class="profile-achievement-tooltip__arrow" aria-hidden="true" />
        <p class="profile-achievement-tooltip__title">{{ title }}</p>
        <p v-if="description" class="profile-achievement-tooltip__description">{{ description }}</p>
        <p v-else class="profile-achievement-tooltip__description profile-achievement-tooltip__description--muted">
            Sin descripción disponible.
        </p>
        <p v-if="dateLabel" class="profile-achievement-tooltip__date">{{ dateLabel }}</p>
    </div>
</template>

<style scoped>
.profile-achievement-tooltip {
    pointer-events: none;
    position: absolute;
    left: 50%;
    top: calc(100% + 0.35rem);
    z-index: 60;
    width: max-content;
    max-width: 11.5rem;
    transform: translateX(-50%);
    border: 1px solid var(--color-border);
    border-radius: 0.5rem;
    background: #fff;
    padding: 0.5rem 0.6rem;
    text-align: left;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.14);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s ease, visibility 0.15s ease, transform 0.15s ease;
}

.profile-achievement-tooltip__arrow {
    position: absolute;
    left: 50%;
    top: -0.35rem;
    height: 0.5rem;
    width: 0.5rem;
    transform: translateX(-50%) rotate(45deg);
    border-left: 1px solid var(--color-border);
    border-top: 1px solid var(--color-border);
    background: #fff;
}

.profile-achievement-tooltip__title {
    font-size: 0.6875rem;
    font-weight: 700;
    line-height: 1.3;
    color: var(--text-principal);
}

.profile-achievement-tooltip__description {
    margin-top: 0.25rem;
    font-size: 0.625rem;
    line-height: 1.4;
    color: var(--color-muted);
    word-wrap: break-word;
}

.profile-achievement-tooltip__description--muted {
    font-style: italic;
    opacity: 0.85;
}

.profile-achievement-tooltip__date {
    margin-top: 0.35rem;
    padding-top: 0.3rem;
    border-top: 1px solid var(--color-border);
    font-size: 0.5625rem;
    font-weight: 600;
    color: color-mix(in srgb, var(--accent-color) 72%, var(--color-muted));
}
</style>
