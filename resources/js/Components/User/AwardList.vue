<script setup>
/**
 * Galería compacta de premios del perfil: icono mediano con nombre, fecha y tooltip descriptivo.
 */

import { computed } from 'vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import ProfileAchievementTooltip from '@/Components/Profile/ProfileAchievementTooltip.vue';

const props = defineProps({
    awards: {
        type: Array,
        default: () => [],
    },
});

/** Agrupa premios por categoría para una presentación ordenada. */
const groupedAwards = computed(() => {
    const groups = new Map();

    props.awards.forEach((award) => {
        const category = award.category?.trim() || 'Reconocimientos';
        if (! groups.has(category)) {
            groups.set(category, []);
        }
        groups.get(category).push(award);
    });

    return [...groups.entries()].map(([category, items]) => ({ category, items }));
});

/** Formatea la fecha de otorgamiento del premio. */
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
    <!-- Premios en cuadrícula vertical compacta con leyenda emergente al hover -->
    <section class="profile-awards-showcase">
        <header class="profile-awards-showcase__header">
            <FaIcon icon="fa-solid fa-award" />
            <span>Premios</span>
            <span v-if="awards.length" class="profile-awards-showcase__count">{{ awards.length }}</span>
        </header>

        <div v-if="awards.length" class="profile-awards-showcase__body">
            <div
                v-for="group in groupedAwards"
                :key="group.category"
                class="profile-awards-group"
            >
                <h4 class="profile-awards-group__title">
                    <FaIcon icon="fa-solid fa-tag" />
                    {{ group.category }}
                </h4>

                <ul class="profile-awards-grid">
                    <li
                        v-for="award in group.items"
                        :key="award.id"
                        class="profile-award-item"
                        tabindex="0"
                        :aria-label="award.name"
                        :style="{ '--award-color': award.color || '#D97706' }"
                    >
                        <span class="profile-award-item__icon-wrap" aria-hidden="true">
                            <FaIcon :icon="award.icon || 'fa-solid fa-award'" class="profile-award-item__icon" />
                        </span>

                        <p class="profile-award-item__name">{{ award.name }}</p>

                        <p v-if="award.granted_at" class="profile-award-item__date">
                            {{ formatGrantedDate(award.granted_at) }}
                        </p>

                        <ProfileAchievementTooltip
                            :title="award.name"
                            :description="award.description"
                            :date-label="award.granted_at ? `Otorgado el ${formatGrantedDate(award.granted_at)}` : ''"
                            :accent-color="award.color || '#D97706'"
                        />
                    </li>
                </ul>
            </div>
        </div>

        <div v-else class="profile-awards-showcase__empty">
            <FaIcon icon="fa-solid fa-award" />
            <p>Sin premios otorgados.</p>
        </div>
    </section>
</template>

<style scoped>
.profile-awards-showcase {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--color-border);
    border-radius: 0.25rem;
    background: var(--color-surfaceColor, #fff);
    box-shadow: var(--shadow-card);
}

.profile-awards-showcase__header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.25rem 0.25rem 0 0;
    background: linear-gradient(135deg, #7c3aed, #9333ea);
    padding: 0.65rem 0.85rem;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #fff;
}

.profile-awards-showcase__count {
    margin-left: auto;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.22);
    padding: 0.1rem 0.45rem;
    font-size: 0.65rem;
    font-weight: 700;
}

.profile-awards-showcase__body {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    padding: 0.75rem;
    overflow: visible;
}

.profile-awards-group__title {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.45rem;
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--color-muted);
}

.profile-awards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(4.75rem, 1fr));
    gap: 0.65rem 0.45rem;
    margin: 0;
    padding: 0;
    list-style: none;
    overflow: visible;
}

.profile-award-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    min-width: 0;
    text-align: center;
    outline: none;
}

.profile-award-item:hover,
.profile-award-item:focus-visible {
    z-index: 50;
}

.profile-award-item:hover :deep(.profile-achievement-tooltip),
.profile-award-item:focus-visible :deep(.profile-achievement-tooltip) {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(2px);
}

.profile-award-item__icon-wrap {
    display: flex;
    height: 2.5rem;
    width: 2.5rem;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.profile-award-item__icon {
    font-size: 1.35rem;
    color: var(--award-color);
    filter: drop-shadow(0 1px 2px color-mix(in srgb, var(--award-color) 25%, transparent));
}

.profile-award-item__name {
    width: 100%;
    font-size: 0.6875rem;
    font-weight: 700;
    line-height: 1.25;
    color: var(--text-principal);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-award-item__date {
    width: 100%;
    font-size: 0.5625rem;
    font-weight: 600;
    line-height: 1.2;
    color: var(--color-muted);
}

.profile-awards-showcase__empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    padding: 1.5rem 1rem;
    text-align: center;
    color: var(--color-muted);
}

.profile-awards-showcase__empty svg,
.profile-awards-showcase__empty i {
    font-size: 1.35rem;
    opacity: 0.45;
}

.profile-awards-showcase__empty p {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--text-principal);
}
</style>
