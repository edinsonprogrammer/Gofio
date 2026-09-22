<script setup>

/**

 * Panel desplegable de notificaciones con marcado de leídas y tiempo real.

 */



import { computed, onMounted, onUnmounted, ref } from 'vue';

import { router } from '@inertiajs/vue3';

import FaIcon from '@/Components/UI/FaIcon.vue';

import ListRowSkeleton from '@/Components/UI/ListRowSkeleton.vue';



const isOpen = ref(false);

const loading = ref(false);

const syncing = ref(false);

const badgeCount = ref(0);

const notifications = ref([]);

const retentionDays = ref(15);

const rootRef = ref(null);

const panelRef = ref(null);

const pollInterval = ref(null);

/** Evita que el mismo clic de apertura cierre el panel en móvil. */

let suppressOutsideClose = false;

let loadRequestId = 0;



const hasBadge = computed(() => badgeCount.value > 0);

const unreadInList = computed(() => notifications.value.filter((item) => item.is_unread).length);



/** Devuelve el icono Font Awesome según el tipo de notificación. */

const typeIcon = (type) => {

    const map = {

        post_from_following: 'fa-solid fa-newspaper',

        post_removed: 'fa-solid fa-ban',

        rank_up: 'fa-solid fa-ranking-star',

        medal_received: 'fa-solid fa-medal',

        award_received: 'fa-solid fa-award',

        verified: 'fa-solid fa-circle-check',

        message_received: 'fa-solid fa-comment-dots',

        tip_received: 'fa-solid fa-coins',

        comment_received: 'fa-solid fa-comments',

        reaction_received: 'fa-solid fa-heart',

        following_updated: 'fa-solid fa-user-pen',

        new_follower: 'fa-solid fa-user-plus',

        user_reported: 'fa-solid fa-shield-halved',

        post_reported: 'fa-solid fa-shield-halved',

        content_reported: 'fa-solid fa-shield-halved',

        spam_detected: 'fa-solid fa-triangle-exclamation',

        profile_visit: 'fa-solid fa-eye',

        moderator_action: 'fa-solid fa-shield-halved',

    };



    return map[type] ?? 'fa-solid fa-bolt';

};



/** Indica si la notificación es de tipo administrativo o moderación. */

const isStaffType = (type) => [

    'user_reported',

    'post_reported',

    'content_reported',

    'spam_detected',

    'moderator_action',

].includes(type);



/** Obtiene las notificaciones no leídas del usuario. */

const loadNotifications = async ({ silent = false } = {}) => {

    const requestId = ++loadRequestId;



    if (!silent) {

        loading.value = true;

    }



    try {

        const { data } = await window.axios.get('/api/notifications');



        if (requestId !== loadRequestId) {

            return;

        }



        badgeCount.value = data.data.badge_count;

        notifications.value = data.data.notifications;

        retentionDays.value = data.data.retention_days ?? 15;

    } catch {

        // Silencioso: el panel puede reintentar al abrirse de nuevo.

    } finally {

        if (requestId === loadRequestId && !silent) {

            loading.value = false;

        }

    }

};



/** Marca como vistas las notificaciones al cerrar el panel. */

const markPanelSeen = async () => {

    if (syncing.value) {

        return;

    }



    syncing.value = true;



    try {

        await window.axios.post('/api/notifications/mark-seen');

        badgeCount.value = 0;

        notifications.value = notifications.value.map((item) => ({

            ...item,

            seen_at: item.seen_at ?? new Date().toISOString(),

        }));

    } catch {

        // El contador se sincronizará en el próximo poll.

    } finally {

        syncing.value = false;

    }

};



/** Despliega el panel de notificaciones. */

const openPanel = async () => {

    isOpen.value = true;

    document.documentElement.classList.add('gofio-notification-open');

    await loadNotifications();

};



/** Cierra el panel y restaura el foco. */

const closePanel = async () => {

    if (!isOpen.value) {

        return;

    }



    isOpen.value = false;

    document.documentElement.classList.remove('gofio-notification-open');

    await markPanelSeen();

};



/** Alterna la visibilidad del panel de actividad. */

const toggle = async () => {

    if (isOpen.value) {

        await closePanel();

        return;

    }



    suppressOutsideClose = true;

    await openPanel();

    window.setTimeout(() => {

        suppressOutsideClose = false;

    }, 0);

};



/** Navega al destino de la notificación seleccionada. */

const openNotification = async (item) => {

    if (item.is_unread) {

        try {

            const { data } = await window.axios.post(`/api/notifications/${item.id}/read`);

            badgeCount.value = data.badge_count;

            item.read_at = new Date().toISOString();

            item.is_unread = false;

            item.seen_at = item.seen_at ?? new Date().toISOString();

        } catch {

            // Si falla, igual se intenta abrir el destino.

        }

    }



    await closePanel();



    if (item.action_url) {

        router.visit(item.action_url);

    }

};



/** Formatea la marca temporal en texto relativo legible. */

const formatTime = (iso) => {

    if (!iso) {

        return '';

    }



    const date = new Date(iso);

    const now = new Date();

    const diffMin = Math.floor((now - date) / 60000);



    if (diffMin < 1) {

        return 'Ahora';

    }



    if (diffMin < 60) {

        return `Hace ${diffMin} min`;

    }



    if (diffMin < 1440) {

        return `Hace ${Math.floor(diffMin / 60)} h`;

    }



    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });

};



/** Comprueba si el clic ocurrió fuera del botón y del panel teleportado. */

const isOutsidePanel = (target) => {

    if (!(target instanceof Node)) {

        return true;

    }



    if (rootRef.value?.contains(target)) {

        return false;

    }



    if (panelRef.value?.contains(target)) {

        return false;

    }



    return true;

};



/** Cierra el panel al hacer clic fuera del componente. */

const onDocumentClick = (event) => {

    if (suppressOutsideClose || !isOpen.value) {

        return;

    }



    if (isOutsidePanel(event.target)) {

        closePanel();

    }

};



/** Cierra el panel con Escape. */

const onKeydown = (event) => {

    if (event.key === 'Escape' && isOpen.value) {

        closePanel();

    }

};



onMounted(() => {

    loadNotifications({ silent: true });

    pollInterval.value = window.setInterval(() => {

        if (!isOpen.value && !syncing.value) {

            loadNotifications({ silent: true });

        }

    }, 30000);

    document.addEventListener('click', onDocumentClick, true);

    document.addEventListener('keydown', onKeydown);

});



onUnmounted(() => {

    if (pollInterval.value) {

        window.clearInterval(pollInterval.value);

    }



    document.documentElement.classList.remove('gofio-notification-open');

    document.removeEventListener('click', onDocumentClick, true);

    document.removeEventListener('keydown', onKeydown);

});

</script>



<template>

    <!-- Botón de actividad en la barra superior -->

    <div ref="rootRef" class="gofio-notification-root">

        <button

            type="button"

            class="gofio-nav-icon-btn gofio-notification-trigger"

            title="Actividad y notificaciones"

            aria-label="Actividad y notificaciones"

            :aria-expanded="isOpen"

            @click.stop="toggle"

        >

            <FaIcon icon="fa-solid fa-bolt" />

            <span v-if="hasBadge" class="gofio-notification-badge">{{ badgeCount }}</span>

        </button>

    </div>



    <!-- Panel teleportado para evitar recortes en móvil y respetar capas -->

    <Teleport to="body">

        <Transition name="gofio-notification-fade">

            <div v-if="isOpen" class="gofio-notification-layer" role="presentation">

                <button

                    type="button"

                    class="gofio-notification-layer__backdrop"

                    aria-label="Cerrar actividad"

                    @click="closePanel"

                />



                <div

                    ref="panelRef"

                    class="gofio-notification-panel"

                    role="dialog"

                    aria-label="Actividad y notificaciones"

                    aria-modal="true"

                    @click.stop

                >

                    <div class="gofio-notification-panel-header">

                        <div class="gofio-notification-panel-heading">

                            <span class="gofio-notification-panel-icon-wrap">

                                <FaIcon icon="fa-solid fa-bolt" />

                            </span>

                            <div>

                                <p class="gofio-notification-panel-title">Actividad</p>

                                <p class="gofio-notification-panel-subtitle">

                                    Últimos {{ retentionDays }} días

                                </p>

                            </div>

                        </div>

                        <span v-if="unreadInList" class="gofio-notification-unread-pill">

                            {{ unreadInList }} nuevas

                        </span>

                    </div>



                    <div class="gofio-notification-panel-body">

                        <ListRowSkeleton v-if="loading" :count="4" />



                        <div v-else-if="!notifications.length" class="gofio-notification-empty">

                            <FaIcon icon="fa-solid fa-bolt" class="gofio-notification-empty-icon" />

                            <p class="font-semibold text-textPrincipal">Sin novedades</p>

                            <p class="mt-1 text-xs text-fb-muted">Cuando pase algo relevante, aparecerá aquí.</p>

                        </div>



                        <ul v-else class="gofio-notification-list">

                            <li v-for="item in notifications" :key="item.id">

                                <button

                                    type="button"

                                    class="gofio-notification-item"

                                    :class="{

                                        'gofio-notification-item--unread': item.is_unread,

                                        'gofio-notification-item--staff': isStaffType(item.type),

                                    }"

                                    @click="openNotification(item)"

                                >

                                    <span

                                        class="gofio-notification-item-icon"

                                        :class="{ 'gofio-notification-item-icon--staff': isStaffType(item.type) }"

                                    >

                                        <FaIcon :icon="typeIcon(item.type)" />

                                    </span>

                                    <span class="min-w-0 flex-1 text-left">

                                        <span class="gofio-notification-item-title">{{ item.title }}</span>

                                        <span class="gofio-notification-item-body">{{ item.body }}</span>

                                        <span class="gofio-notification-item-meta">

                                            <span>{{ formatTime(item.created_at) }}</span>

                                            <span v-if="item.is_unread" class="gofio-notification-item-badge">Nueva</span>

                                        </span>

                                    </span>

                                </button>

                            </li>

                        </ul>

                    </div>



                    <div class="gofio-notification-panel-footer">

                        <FaIcon icon="fa-solid fa-clock-rotate-left" class="gofio-notification-panel-footer-icon" />

                        <span>Actividad reciente</span>

                    </div>

                </div>

            </div>

        </Transition>

    </Teleport>

</template>


