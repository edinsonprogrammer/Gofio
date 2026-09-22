<script setup>
/**
 * Botón de seguir/dejar de seguir un usuario con actualización optimista
 * del contador, emisión de evento global para sincronizar la UI,
 * y efecto hover elegante que avisa al usuario sobre la acción de dejar de seguir.
 */

import { ref, watch } from 'vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

const props = defineProps({
    username: {
        type: String,
        required: true,
    },
    isFollowing: {
        type: Boolean,
        default: false,
    },
    followersCount: {
        type: Number,
        default: 0,
    },
    block: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update']);

const following = ref(props.isFollowing);
const followers = ref(props.followersCount);
const loading = ref(false);
/** Controla si el cursor está sobre el botón "Siguiendo" para mostrar la alerta de dejar de seguir. */
const hovered = ref(false);

/** Activa el estado hover solo cuando ya se sigue al usuario y no hay carga en curso. */
const onMouseEnter = () => { if (following.value && !loading.value) hovered.value = true; };
/** Desactiva el estado hover al salir del botón. */
const onMouseLeave = () => { hovered.value = false; };

watch(
    () => [props.isFollowing, props.followersCount],
    ([nextFollowing, nextCount]) => {
        following.value = nextFollowing;
        followers.value = nextCount;
    },
);

/** Alterna el estado de seguimiento mediante la API REST de usuarios. */
const toggleFollow = async () => {
    if (loading.value) return;

    // Al hacer clic se resetea el hover para evitar estado residual.
    hovered.value = false;
    loading.value = true;
    try {
        const method = following.value ? 'delete' : 'post';
        const { data } = await window.axios[method](`/api/users/${props.username}/follow`);

        following.value = data.data.is_following;
        followers.value = data.data.followers_count;

        window.dispatchEvent(new CustomEvent('gofio:following-changed', {
            detail: {
                username: props.username,
                is_following: data.data.is_following,
            },
        }));

        emit('update', {
            is_following: data.data.is_following,
            followers_count: data.data.followers_count,
            following_count: data.data.following_count,
        });
    } catch (e) {
        alert(e.response?.data?.message || e.response?.data?.errors?.follow?.[0] || 'No se pudo completar la acción.');
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <!-- Acción principal de seguimiento con estados visual, hover elegante y de carga -->
    <button
        type="button"
        class="gofio-follow-btn"
        :class="[
            following
                ? (hovered ? 'gofio-follow-btn--unfollow-hover' : 'gofio-follow-btn--following')
                : 'gofio-follow-btn--follow',
            { 'gofio-follow-btn--block': block },
        ]"
        :disabled="loading"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        @click="toggleFollow"
    >
        <!-- Icono cambia según estado y hover para reforzar la intención -->
        <FaIcon
            :icon="
                following
                    ? (hovered ? 'fa-solid fa-user-xmark' : 'fa-solid fa-user-check')
                    : 'fa-solid fa-user-plus'
            "
        />
        <span>{{ following ? (hovered ? 'Dejar de seguir' : 'Siguiendo') : 'Seguir' }}</span>
    </button>
</template>

<style scoped>
.gofio-follow-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    border-radius: 9999px;
    padding: 0.45rem 1rem;
    font-size: 0.8125rem;
    font-weight: 700;
    line-height: 1;
    /* Transición suave para todos los cambios visuales, incluyendo el ancho al cambiar el texto */
    transition:
        background-color 0.2s ease,
        color 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        transform 0.2s ease;
    white-space: nowrap;
    overflow: hidden;
}

.gofio-follow-btn:disabled {
    opacity: 0.65;
    cursor: wait;
}

/* Estado: no sigue — botón de acción principal */
.gofio-follow-btn--follow {
    background: linear-gradient(135deg, var(--color-brand), var(--color-brand-hover));
    color: white;
    box-shadow: 0 4px 14px rgba(20, 184, 166, 0.28);
}

.gofio-follow-btn--follow:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(20, 184, 166, 0.38);
}

/* Estado: ya sigue — bordes sutiles, sin hover aún */
.gofio-follow-btn--following {
    background: white;
    color: var(--color-brand);
    border: 1.5px solid rgba(20, 184, 166, 0.35);
}

/* Estado: hover sobre "Siguiendo" — aviso visual de que se dejará de seguir */
.gofio-follow-btn--unfollow-hover {
    background: #fff1f1;
    color: #e53e3e;
    border: 1.5px solid rgba(229, 62, 62, 0.4);
    box-shadow: 0 4px 14px rgba(229, 62, 62, 0.12);
    transform: translateY(-1px);
}

.gofio-follow-btn--block {
    width: 100%;
    justify-content: center;
    padding-top: 0.625rem;
    padding-bottom: 0.625rem;
}

@media (min-width: 640px) {
    .gofio-follow-btn--block {
        width: auto;
    }
}
</style>
