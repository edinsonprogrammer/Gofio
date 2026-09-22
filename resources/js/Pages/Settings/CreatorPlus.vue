<script setup>
/**
 * Página de suscripción Creator Plus con planes, beneficios y gestión de la membresía premium.
 */

import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import GofioLayout from '@/Layouts/GofioLayout.vue';
import ProfileVisitsModal from '@/Components/CreatorPlus/ProfileVisitsModal.vue';

const props = defineProps({
    subscription: {
        type: Object,
        required: true,
    },
});

const loading = ref(false);
const error = ref('');
const success = ref('');
const selectedMonths = ref(1);
const paymentMethod = ref('coins');
const showVisits = ref(false);

const selectedQuote = computed(() => {
    const options = props.subscription.months_options ?? [];
    return options.find((item) => item.months === selectedMonths.value)
        ?? options[0]
        ?? null;
});

const canPayWithCoins = computed(() => {
    if (!selectedQuote.value) return false;
    return Number(props.subscription.balance_monedas ?? 0) >= selectedQuote.value.total_price;
});


/** Inicia el proceso de suscripción al plan Creator Plus. */
const subscribe = async () => {
    if (paymentMethod.value === 'gateway') {
        error.value = props.subscription.payment_gateway_message;
        return;
    }

    loading.value = true;
    error.value = '';
    success.value = '';

    try {
        const { data } = await window.axios.post('/api/subscription/subscribe', {
            months: selectedMonths.value,
        });
        success.value = data.message;
        router.reload({ only: ['subscription'] });
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.subscription?.[0]
            || 'No se pudo procesar la suscripción.';
    } finally {
        loading.value = false;
    }
};


/** Formatea una fecha ISO en formato local español. */
const formatDate = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('es-ES', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
};
</script>

<template>
    <!-- Planes y beneficios de la suscripción Creator Plus -->

    <GofioLayout>
        <template #feed>
            <div class="gofio-box creator-plus-page overflow-hidden">
                <div class="creator-plus-page-header px-4 py-5 text-white">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="creator-plus-page-badge flex h-16 w-16 items-center justify-center rounded-full text-3xl">
                            <i class="fa-solid fa-crown"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-extrabold tracking-tight">Creator Plus</h1>
                            <p class="mt-1 max-w-xl text-sm text-amber-50/90">
                                Suscripción premium con check verificado, personalización de apariencia, karma x2,
                                posts con brillo, soporte prioritario a moderación y alertas de visitas a tu perfil.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 p-4">
                    <div v-if="subscription.is_creator_plus" class="rounded-lg border border-amber-200 bg-gradient-to-r from-amber-50 to-yellow-50 p-4 text-sm text-amber-950">
                        <p class="font-semibold">Tu suscripción está activa</p>
                        <p class="mt-1">Vence el {{ formatDate(subscription.expires_at) }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" class="gofio-btn-secondary text-xs" @click="showVisits = true">
                                <i class="fa-solid fa-eye mr-1"></i>
                                Ver visitas al perfil
                            </button>
                            <Link :href="`/perfil/${$page.props.auth.user?.username}`" class="gofio-btn-secondary text-xs">
                                Ir a mi perfil (soporte prioritario)
                            </Link>
                        </div>
                    </div>

                    <div>
                        <h2 class="mb-3 text-sm font-bold uppercase tracking-wide text-fb-muted">Beneficios incluidos</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="(benefit, i) in subscription.benefit_details"
                                :key="i"
                                class="creator-plus-benefit-card rounded-lg border border-amber-100 bg-white p-3"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="creator-plus-benefit-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-amber-700">
                                        <i :class="benefit.icon"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold">{{ benefit.title }}</p>
                                        <p class="mt-0.5 text-xs text-fb-muted">{{ benefit.description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-fb-border bg-[#F5F6F7] p-4">
                        <h2 class="text-sm font-bold">Elige tu plan</h2>
                        <p class="mt-1 text-xs text-fb-muted">
                            {{ subscription.monthly_price }} monedas por mes · {{ subscription.days_per_month }} días por mes
                        </p>

                        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button
                                v-for="option in subscription.months_options"
                                :key="option.months"
                                type="button"
                                class="rounded-lg border px-3 py-2 text-left text-sm transition"
                                :class="selectedMonths === option.months
                                    ? 'creator-plus-plan-selected border-amber-400 bg-amber-50 font-semibold text-amber-900'
                                    : 'border-fb-border bg-white hover:border-amber-200'"
                                @click="selectedMonths = option.months"
                            >
                                <span class="block">{{ option.label }}</span>
                                <span class="text-xs text-fb-muted">{{ option.total_price }} monedas</span>
                            </button>
                        </div>

                        <div v-if="selectedQuote" class="mt-4 rounded border border-amber-200 bg-white p-3 text-sm">
                            <p>
                                Total: <strong>{{ selectedQuote.total_price }} monedas</strong>
                                ({{ selectedQuote.total_days }} días)
                            </p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-fb-border p-4">
                        <h2 class="text-sm font-bold">Método de pago</h2>
                        <div class="mt-3 space-y-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-fb-border p-3 hover:bg-[#F5F6F7]">
                                <input v-model="paymentMethod" type="radio" value="coins" class="mt-1" />
                                <div>
                                    <p class="text-sm font-semibold">Pagar con monedas (disponible ahora)</p>
                                    <p class="text-xs text-fb-muted">Se debitará de tu billetera al confirmar.</p>
                                </div>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-dashed border-amber-300 bg-amber-50/50 p-3">
                                <input v-model="paymentMethod" type="radio" value="gateway" class="mt-1" />
                                <div>
                                    <p class="text-sm font-semibold text-amber-900">
                                        Pasarela de pago con tarjeta
                                        <span class="ml-1 rounded bg-amber-200 px-1.5 py-0.5 text-[10px] font-bold uppercase">Próximamente</span>
                                    </p>
                                    <p class="text-xs text-amber-800/80">{{ subscription.payment_gateway_message }}</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
                    <p v-if="success" class="text-xs text-green-600">{{ success }}</p>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="creator-plus-subscribe-btn gofio-btn-primary"
                            :disabled="loading || (paymentMethod === 'coins' && !canPayWithCoins && !subscription.is_creator_plus)"
                            @click="subscribe"
                        >
                            {{ loading ? 'Procesando...' : (subscription.is_creator_plus ? 'Renovar Creator Plus' : 'Activar Creator Plus') }}
                        </button>
                        <Link href="/configuracion/verificacion" class="gofio-btn-secondary">
                            Verificación de identidad
                        </Link>
                    </div>

                    <p v-if="paymentMethod === 'coins' && selectedQuote && !canPayWithCoins" class="text-xs text-fb-muted">
                        Necesitas al menos {{ selectedQuote.total_price }} monedas en tu billetera.
                    </p>
                </div>
            </div>

            <ProfileVisitsModal v-if="showVisits" @close="showVisits = false" />
        </template>
    </GofioLayout>
</template>

<style scoped>
.creator-plus-page-header {
    background: linear-gradient(135deg, #92400e 0%, #d97706 45%, #fbbf24 100%);
}

.creator-plus-page-badge {
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 0 24px rgba(251, 191, 36, 0.45);
}

.creator-plus-benefit-icon {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
}

.creator-plus-benefit-card {
    box-shadow: 0 1px 0 rgba(251, 191, 36, 0.15);
}

.creator-plus-plan-selected {
    box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.35);
}

.creator-plus-subscribe-btn:not(:disabled) {
    background: linear-gradient(135deg, #b45309, #d97706);
    border-color: #b45309;
}
</style>
