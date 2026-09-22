<script setup>
/**
 * Cuadrícula compacta de medallas del perfil: medallones pequeños con tooltip al pasar el cursor.
 * Prioriza iconos del paquete activo sobre los definidos en cada medalla.
 */

import { usePage } from '@inertiajs/vue3';
import FaIcon from '@/Components/UI/FaIcon.vue';
import ProfileAchievementTooltip from '@/Components/Profile/ProfileAchievementTooltip.vue';

defineProps({
    medals: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

/** Resuelve el icono de la medalla priorizando override del paquete de iconos. */
const iconFor = (medal) => {
    const override = medal?.slug && page.props.iconPack?.medals?.[medal.slug];
    return override || medal?.icon;
};

/** Formatea la fecha de obtención de la medalla. */
const formatGrantedDate = (iso) => {
    if (! iso) {
        return '';
    }

    return new Date(iso).toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <!-- Medallas en cuadrícula densa para escalar con muchos ítems -->
    <section class="profile-medals-showcase">
        <header class="profile-medals-showcase__header">
            <FaIcon icon="fa-solid fa-medal" />
            <span>Medallas</span>
            <span v-if="medals.length" class="profile-medals-showcase__count">{{ medals.length }}</span>
        </header>

        <div v-if="medals.length" class="profile-medals-showcase__body">
            <ul class="profile-medals-grid">
                <li
                    v-for="medal in medals"
                    :key="medal.id"
                    class="profile-medal-item"
                    tabindex="0"
                    :aria-label="medal.title"
                    :style="{ '--medal-color': medal.color || '#0D9488' }"
                >
                    <div class="profile-medal-item__medallion" aria-hidden="true">
                        <span class="profile-medal-item__ring" />
                        <span class="profile-medal-item__icon-wrap">
                            <FaIcon :icon="iconFor(medal)" class="profile-medal-item__icon" />
                        </span>
                    </div>

                    <p class="profile-medal-item__title">{{ medal.title }}</p>

                    <ProfileAchievementTooltip
                        :title="medal.title"
                        :description="medal.description"
                        :date-label="medal.granted_at ? `Obtenida el ${formatGrantedDate(medal.granted_at)}` : ''"
                        :accent-color="medal.color || '#0D9488'"
                    />
                </li>
            </ul>
        </div>

        <div v-else class="profile-medals-showcase__empty">
            <FaIcon icon="fa-solid fa-medal" />
            <p>Sin medallas todavía.</p>
        </div>
    </section>
</template>

<style scoped>
.profile-medals-showcase {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--color-border);
    border-radius: 0.25rem;
    background: var(--color-surfaceColor, #fff);
    box-shadow: var(--shadow-card);
}

.profile-medals-showcase__header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.25rem 0.25rem 0 0;
    background: linear-gradient(135deg, #b45309, #d97706);
    padding: 0.65rem 0.85rem;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #fff;
}

.profile-medals-showcase__count {
    margin-left: auto;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.22);
    padding: 0.1rem 0.45rem;
    font-size: 0.65rem;
    font-weight: 700;
}

.profile-medals-showcase__body {
    padding: 0.75rem;
    overflow: visible;
}

.profile-medals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(4.25rem, 1fr));
    gap: 0.5rem 0.35rem;
    margin: 0;
    padding: 0;
    list-style: none;
    overflow: visible;
}

.profile-medal-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.2rem;
    min-width: 0;
    text-align: center;
    outline: none;
}

.profile-medal-item:hover,
.profile-medal-item:focus-visible {
    z-index: 50;
}

.profile-medal-item:hover :deep(.profile-achievement-tooltip),
.profile-medal-item:focus-visible :deep(.profile-achievement-tooltip) {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(2px);
}

.profile-medal-item__medallion {
    position: relative;
    display: flex;
    height: 2.35rem;
    width: 2.35rem;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.profile-medal-item__ring {
    position: absolute;
    inset: 0;
    border-radius: 999px;
    background: conic-gradient(from 210deg, color-mix(in srgb, var(--medal-color) 80%, #fff), var(--medal-color), color-mix(in srgb, var(--medal-color) 50%, #fff), var(--medal-color));
    box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.45);
}

.profile-medal-item__icon-wrap {
    position: relative;
    z-index: 1;
    display: flex;
    height: 1.75rem;
    width: 1.75rem;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #fff;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.1);
}

.profile-medal-item__icon {
    font-size: 0.8rem;
    color: var(--medal-color);
}

.profile-medal-item__title {
    width: 100%;
    font-size: 0.5625rem;
    font-weight: 600;
    line-height: 1.2;
    color: var(--color-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-medals-showcase__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--color-muted);
}

.profile-medals-showcase__empty svg,
.profile-medals-showcase__empty i {
    font-size: 1.35rem;
    opacity: 0.45;
}

.profile-medals-showcase__empty p {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-principal);
}
</style>
