<script setup>
/**
 * Botón de bandera para denunciar una publicación; abre el modal de reporte
 * y se deshabilita si el viewer es el autor del post.
 */

import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ReportModal from '@/Components/Moderation/ReportModal.vue';

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
    variant: {
        type: String,
        default: 'corner',
        validator: (value) => ['corner', 'inline'].includes(value),
    },
});

const page = usePage();
const showReport = ref(false);

const isLoggedIn = computed(() => Boolean(page.props.auth?.user?.id));

const authorId = computed(() => props.post.user?.id ?? props.post.user_id ?? null);

/** Impide denunciar publicaciones propias comparando IDs de usuario. */
const isOwner = computed(() => {
    const viewerId = page.props.auth?.user?.id;
    if (!viewerId || authorId.value === null) {
        return false;
    }

    return Number(viewerId) === Number(authorId.value);
});

/** Abre el modal de denuncia si el usuario no es el autor. */
const openReport = () => {
    if (isOwner.value) {
        return;
    }

    showReport.value = true;
};
</script>

<template>
    <div
        v-if="isLoggedIn"
        class="post-report-flag"
        :class="variant === 'corner' ? 'post-report-flag--corner' : 'post-report-flag--inline'"
    >
        <!-- Icono de bandera que lanza el flujo de denuncia -->
        <button
            type="button"
            class="post-report-flag__btn"
            :class="{ 'post-report-flag__btn--disabled': isOwner }"
            :title="isOwner ? 'No puedes denunciar tu propia publicación' : 'Denunciar publicación'"
            :aria-label="isOwner ? 'No puedes denunciar tu propia publicación' : 'Denunciar publicación'"
            :disabled="isOwner"
            @click.stop="openReport"
        >
            <i class="fa-regular fa-flag"></i>
        </button>

        <ReportModal
            v-if="!isOwner"
            :show="showReport"
            type="post"
            :target-id="post.id"
            @close="showReport = false"
        />
    </div>
</template>
