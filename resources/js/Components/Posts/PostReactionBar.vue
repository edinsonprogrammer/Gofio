<script setup>
/**
 * Barra de reacciones con selector de emoji y resumen de conteos por tipo.
 */

import { computed, ref } from 'vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import { REACTIONS, reactionByKey, topReactions } from '@/utils/reactions';

const props = defineProps({
    postId: {
        type: Number,
        required: true,
    },
    reactions: {
        type: Object,
        default: () => ({
            summary: {},
            total: 0,
            user_reaction: null,
        }),
    },
    pointsCount: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['reacted']);

const localReactions = ref({ ...props.reactions });
const localPoints = ref(props.pointsCount);
const loading = ref(false);
const showTray = ref(false);
const puffIcon = ref('');
const puffVisible = ref(false);
const message = ref('');
let hideTimer = null;
let puffTimer = null;

const userReaction = computed(() => reactionByKey(localReactions.value.user_reaction));
const summaryTop = computed(() => topReactions(localReactions.value.summary));


/** Despliega la bandeja de reacciones disponibles. */
const openTray = () => {
    clearTimeout(hideTimer);
    showTray.value = true;
};


/** Programa el cierre automático de la bandeja tras inactividad. */
const scheduleClose = () => {
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
        showTray.value = false;
    }, 220);
};


/** Dispara la animación de partículas al reaccionar. */
const triggerPuff = (icon) => {
    puffIcon.value = icon;
    puffVisible.value = true;
    clearTimeout(puffTimer);
    puffTimer = setTimeout(() => {
        puffVisible.value = false;
    }, 480);
};


/** Registra o elimina la reacción del usuario vía API. */
const react = async (reactionKey) => {
    if (loading.value) {
        return;
    }

    loading.value = true;
    message.value = '';

    const clicked = REACTIONS.find((r) => r.key === reactionKey);
    triggerPuff(clicked?.icon ?? 'fa-solid fa-thumbs-up');

    try {
        const { data } = await window.axios.post(`/api/posts/${props.postId}/react`, {
            reaction: reactionKey,
        });

        localReactions.value = data.reactions;
        if (data.points_given) {
            localPoints.value += data.points_given;
        }
        message.value = data.message ?? '';
        emit('reacted', data);
    } catch (e) {
        message.value = e.response?.data?.message
            || e.response?.data?.errors?.reaction?.[0]
            || 'No se pudo reaccionar.';
    } finally {
        loading.value = false;
        showTray.value = false;
    }
};
</script>

<template>
    <!-- Barra de reacciones y contadores del post -->

    <div class="reaction-bar">
        <div
            class="reaction-trigger-wrap"
            @mouseenter="openTray"
            @mouseleave="scheduleClose"
        >
            <button
                type="button"
                class="reaction-trigger"
                :class="{ 'reaction-trigger--active': userReaction }"
                :disabled="loading"
                @click="react(userReaction?.key ?? 'like')"
            >
                <FaIcon v-if="userReaction" :icon="userReaction.icon" class="reaction-icon reaction-trigger__icon" />
                <FaIcon v-else icon="fa-solid fa-thumbs-up" class="reaction-icon" />
                <span>{{ userReaction?.label ?? 'Reaccionar' }}</span>
            </button>

            <Transition name="reaction-tray-fade">
                <div
                    v-if="showTray"
                    class="reaction-tray"
                    @mouseenter="openTray"
                    @mouseleave="scheduleClose"
                >
                    <button
                        v-for="(reaction, index) in REACTIONS"
                        :key="reaction.key"
                        type="button"
                        class="reaction-option"
                        :class="{ 'reaction-option--selected': localReactions.user_reaction === reaction.key }"
                        :style="{ '--delay': `${index * 25}ms` }"
                        :title="reaction.label"
                        :disabled="loading"
                        @click.stop="react(reaction.key)"
                    >
                        <span class="reaction-option__emoji">
                            <FaIcon :icon="reaction.icon" class="reaction-icon" />
                        </span>
                        <span class="reaction-option__label">{{ reaction.label }}</span>
                    </button>
                </div>
            </Transition>

            <Transition name="reaction-puff-fade">
                <span v-if="puffVisible" class="reaction-puff" aria-hidden="true">
                    <FaIcon :icon="puffIcon" class="reaction-icon" />
                </span>
            </Transition>
        </div>

        <div v-if="localReactions.total > 0" class="reaction-summary">
            <span
                v-for="item in summaryTop"
                :key="item.key"
                class="reaction-summary__chip"
                :title="`${item.label}: ${localReactions.summary[item.key]}`"
            >
                <FaIcon :icon="item.icon" class="reaction-icon reaction-summary__icon" />
                <span class="reaction-summary__count">{{ localReactions.summary[item.key] }}</span>
            </span>
            <span v-if="localReactions.total > 0" class="text-xs text-fb-muted">
                {{ localReactions.total }} reacción{{ localReactions.total === 1 ? '' : 'es' }}
            </span>
        </div>

        <span v-if="localPoints > 0" class="text-xs font-semibold text-brandColor">
            +{{ localPoints }} pts
        </span>

        <p v-if="message" class="reaction-message">{{ message }}</p>
    </div>
</template>

<style scoped>
.reaction-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
}

.reaction-trigger-wrap {
    position: relative;
}

.reaction-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    border-radius: 9999px;
    padding: 0.35rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-brand);
    transition: background-color 0.15s;
}

.reaction-trigger:hover:not(:disabled),
.reaction-trigger--active {
    background-color: #ebedf0;
}

.reaction-tray {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 0;
    z-index: 40;
    display: flex;
    align-items: flex-end;
    gap: 0.35rem;
    padding: 0.5rem 0.65rem;
    border-radius: 9999px;
    background: white;
    border: 1px solid var(--color-border);
    box-shadow: 0 12px 32px rgba(15, 23, 42, 0.16);
}

.reaction-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    padding: 0.15rem;
    transform: scale(1);
    transition: transform 0.18s cubic-bezier(0.34, 1.4, 0.64, 1);
    transition-delay: var(--delay, 0ms);
}

.reaction-option:hover {
    transform: scale(1.55);
    z-index: 2;
}

.reaction-tray:hover .reaction-option:not(:hover) {
    transform: scale(0.88);
    opacity: 0.75;
}

.reaction-icon {
    color: var(--color-accent, var(--color-brand));
}

.reaction-option--selected .reaction-option__emoji {
    filter: drop-shadow(0 0 6px color-mix(in srgb, var(--color-accent, var(--color-brand)) 45%, transparent));
}

.reaction-trigger__icon {
    font-size: 1rem;
    line-height: 1;
}

.reaction-option__emoji {
    display: inline-flex;
    font-size: 1.65rem;
    line-height: 1;
}

.reaction-summary__icon {
    font-size: 0.85rem;
    line-height: 1;
}

.reaction-puff {
    position: absolute;
    left: 50%;
    top: -8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transform: translateX(-50%);
    font-size: 2rem;
    pointer-events: none;
    animation: reaction-puff 0.48s ease-out forwards;
}

.reaction-option__label {
    font-size: 0.55rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: var(--color-muted);
    max-width: 4.5rem;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@keyframes reaction-puff {
    0% {
        transform: translateX(-50%) scale(0.6);
        opacity: 0.2;
    }
    35% {
        transform: translateX(-50%) scale(1.35);
        opacity: 1;
    }
    100% {
        transform: translateX(-50%) scale(2);
        opacity: 0;
    }
}

.reaction-summary {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.reaction-summary__chip {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    padding: 0.1rem 0.4rem;
    border-radius: 9999px;
    background: var(--color-panel);
    border: 1px solid var(--color-border);
    font-size: 0.85rem;
}

.reaction-summary__count {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--color-muted);
}

.reaction-message {
    width: 100%;
    font-size: 0.7rem;
    color: var(--color-muted);
}

.reaction-tray-fade-enter-active,
.reaction-tray-fade-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.reaction-tray-fade-enter-from,
.reaction-tray-fade-leave-to {
    opacity: 0;
    transform: translateY(6px) scale(0.96);
}
</style>
