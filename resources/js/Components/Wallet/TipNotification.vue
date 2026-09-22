<script setup>
/**
 * Notificaciones toast en tiempo real de propinas recibidas y menciones,
 * escuchando el canal privado de Laravel Echo del usuario.
 */

import { onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const notifications = ref([]);
let tipChannel = null;


/** Encola un aviso flotante y lo retira automáticamente tras seis segundos. */
const addNotification = (payload) => {
    const id = Date.now();
    notifications.value.push({ id, ...payload });

    setTimeout(() => {
        notifications.value = notifications.value.filter((n) => n.id !== id);
    }, 6000);
};

onMounted(() => {
    const userId = page.props.auth.user?.id;
    if (!userId || !window.Echo) return;

    tipChannel = window.Echo.private(`user.${userId}`);

    tipChannel.listen('.tip.received', (data) => {
        addNotification({
            type: 'tip',
            title: '¡Recibiste una propina!',
            message: `@${data.sender_username} te envió ${data.net_amount} monedas por «${data.post_title}»`,
            amount: data.net_amount,
        });
    });

    tipChannel.listen('.user.mentioned', (data) => {
        addNotification({
            type: 'mention',
            title: 'Te mencionaron',
            message: `@${data.mentioned_by} te mencionó: ${data.preview}`,
        });
    });
});

onUnmounted(() => {
    const userId = page.props.auth.user?.id;
    if (userId && window.Echo) {
        window.Echo.leave(`user.${userId}`);
    }
});
</script>

<template>
    <!-- Toasts apilados de propinas y menciones en la esquina superior derecha -->
    <div class="fixed right-4 top-16 z-[90] flex flex-col gap-2">
        <TransitionGroup name="tip-slide">
            <div
                v-for="n in notifications"
                :key="n.id"
                class="tip-notification flex max-w-xs items-start gap-3 rounded-lg border border-yellow-300 bg-gradient-to-r from-yellow-50 to-amber-100 p-4 shadow-lg"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-yellow-400 text-lg">
                    <i v-if="n.type === 'tip'" class="fa-solid fa-coins text-yellow-900"></i>
                    <i v-else class="fa-solid fa-at text-yellow-900"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-yellow-900">{{ n.title }}</p>
                    <p class="text-xs text-yellow-800">{{ n.message }}</p>
                    <p v-if="n.amount" class="mt-1 text-lg font-bold text-amber-600">+{{ n.amount }} 🪙</p>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.tip-slide-enter-active {
    animation: tip-in 0.4s ease-out;
}
.tip-slide-leave-active {
    animation: tip-out 0.3s ease-in forwards;
}
@keyframes tip-in {
    from { opacity: 0; transform: translateX(100px) scale(0.8); }
    to { opacity: 1; transform: translateX(0) scale(1); }
}
@keyframes tip-out {
    from { opacity: 1; transform: translateX(0); }
    to { opacity: 0; transform: translateX(100px); }
}
</style>
