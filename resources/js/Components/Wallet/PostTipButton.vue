<script setup>
/**
 * Botón y flujo de propina asociado a una publicación específica.
 */

import { computed, nextTick, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['updated']);

const page = usePage();
const showPopover = ref(false);
const amount = ref(5);
const loading = ref(false);
const error = ref('');
const celebrating = ref(false);
const shineActive = ref(false);
const popoverRef = ref(null);
const buttonRef = ref(null);

const tipsTotal = ref(Number(props.post.tips_total ?? 0));
const tipsCount = ref(Number(props.post.tips_count ?? 0));
const displayTotal = ref(tipsTotal.value);

const tipSettings = computed(() => page.props.tipSettings ?? {});
const balance = computed(() => Number(page.props.auth?.user?.balance_monedas ?? 0));
const canTip = computed(() =>
    tipSettings.value.enabled !== false
    && props.post.can_tip !== false
    && tipSettings.value.can_send !== false,
);
const feePercent = computed(() => Number(tipSettings.value.platform_fee_percent ?? 10));

watch(
    () => props.post.tips_total,
    (val) => {
        tipsTotal.value = Number(val ?? 0);
        if (!celebrating.value) {
            displayTotal.value = tipsTotal.value;
        }
    },
);

watch(showPopover, (open) => {
    if (open) {
        error.value = '';
        amount.value = Math.min(Math.max(5, 1), balance.value || 5);
        nextTick(positionPopover);
    }
});


/** Calcula la posición del popover de propina respecto al botón. */
const positionPopover = () => {
    if (!buttonRef.value || !popoverRef.value) return;
    const rect = buttonRef.value.getBoundingClientRect();
    const pop = popoverRef.value;
    pop.style.top = `${rect.bottom + window.scrollY + 8}px`;
    pop.style.left = `${Math.max(12, rect.left + window.scrollX - 40)}px`;
};


/** Valida que el monto ingresado sea positivo y no exceda el saldo. */
const validateAmount = () => {
    const value = Number(amount.value);
    if (!value || value <= 0) {
        error.value = 'Ingresa un monto válido.';
        return false;
    }
    if (value > balance.value) {
        error.value = `Saldo insuficiente. Tienes ${balance.value.toFixed(2)} monedas.`;
        return false;
    }
    error.value = '';
    return true;
};


/** Anima el contador total de propinas recibidas en el post. */
const animateTotal = (added) => {
    celebrating.value = true;
    shineActive.value = true;
    const start = displayTotal.value;
    const end = start + added;
    const duration = 900;
    const started = performance.now();

    
/** Avanza un frame de la animación numérica del total. */
const tick = (now) => {
        const progress = Math.min(1, (now - started) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        displayTotal.value = start + (end - start) * eased;
        if (progress < 1) {
            requestAnimationFrame(tick);
        } else {
            displayTotal.value = end;
            celebrating.value = false;
        }
    };

    requestAnimationFrame(tick);
    setTimeout(() => {
        shineActive.value = false;
    }, 3200);
};


/** Envía el formulario al servidor y gestiona errores de validación. */
const submit = async () => {
    if (!validateAmount() || loading.value) return;

    loading.value = true;
    error.value = '';

    try {
        const { data } = await window.axios.post(`/api/posts/${props.post.id}/tip`, {
            amount: Number(amount.value),
        });

        tipsTotal.value = Number(data.tips_total ?? tipsTotal.value);
        tipsCount.value = Number(data.tips_count ?? tipsCount.value + 1);
        animateTotal(Number(data.amount ?? amount.value));

        emit('updated', {
            tips_total: tipsTotal.value,
            tips_count: tipsCount.value,
        });

        router.reload({ only: ['auth'], preserveScroll: true, preserveState: true });

        showPopover.value = false;
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.amount?.[0]
            || 'Error al enviar propina.';
    } finally {
        loading.value = false;
    }
};


/** Muestra u oculta el popover de envío de propina. */
const togglePopover = () => {
    if (!canTip.value) return;
    showPopover.value = !showPopover.value;
};


/** Cierra el popover al hacer clic fuera del componente. */
const onClickOutside = (event) => {
    if (!showPopover.value) return;
    if (buttonRef.value?.contains(event.target) || popoverRef.value?.contains(event.target)) {
        return;
    }
    showPopover.value = false;
};

if (typeof window !== 'undefined') {
    window.addEventListener('click', onClickOutside);
    window.addEventListener('resize', positionPopover);
    window.addEventListener('scroll', positionPopover, true);
}
</script>

<template>
    <!-- Botón y popover para enviar propina a un post -->

    <div class="post-tip-wrap">
        <button
            v-if="canTip"
            ref="buttonRef"
            type="button"
            class="post-tip-btn"
            @click.stop="togglePopover"
        >
            <i class="fa-solid fa-coins"></i>
            Propina
        </button>

        <div
            v-if="tipsTotal > 0 || tipsCount > 0 || celebrating || shineActive"
            class="post-tip-total"
            :class="{ 'post-tip-total--shine': shineActive, 'post-tip-total--celebrate': celebrating }"
        >
            <span class="post-tip-sparkles" aria-hidden="true">
                <i v-for="n in 6" :key="n" class="post-tip-sparkle" :style="{ '--i': n }"></i>
            </span>
            <i class="fa-solid fa-coins post-tip-coin"></i>
            <span class="post-tip-amount">{{ displayTotal.toFixed(2) }}</span>
            <span v-if="tipsCount > 0" class="post-tip-count">({{ tipsCount }})</span>
        </div>

    <!-- Modal superpuesto -->
        <Teleport to="body">
            <div
                v-if="showPopover"
                ref="popoverRef"
                class="tip-popover"
                role="dialog"
                aria-label="Enviar propina"
                @click.stop
            >
                <div class="tip-popover-arrow"></div>
                <p class="tip-popover-title">Enviar propina</p>
                <p class="tip-popover-sub">
                    A <strong>@{{ post.user.username }}</strong>
                </p>

                <label class="tip-popover-label">Monto (monedas)</label>
                <input
                    v-model.number="amount"
                    type="number"
                    min="0.01"
                    step="0.01"
                    class="tip-popover-input"
                    @keydown.enter.prevent="submit"
                />

                <p class="tip-popover-balance">
                    Disponible: <strong>{{ balance.toFixed(2) }}</strong>
                </p>
                <p class="tip-popover-fee">Comisión plataforma: {{ feePercent }}%</p>

                <p v-if="error" class="tip-popover-error">{{ error }}</p>

                <div class="tip-popover-actions">
                    <button type="button" class="tip-popover-cancel" @click="showPopover = false">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="tip-popover-ok"
                        :disabled="loading"
                        @click="submit"
                    >
                        {{ loading ? '...' : 'OK' }}
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.post-tip-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.post-tip-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 0.375rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #b45309;
    transition: background 0.15s;
}

.post-tip-btn:hover {
    background: #ebedf0;
}

.post-tip-total {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border-radius: 999px;
    padding: 0.15rem 0.55rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: #92400e;
    background: rgba(251, 191, 36, 0.15);
    overflow: hidden;
}

.post-tip-total--shine {
    animation: tipPulse 0.55s ease-in-out 3;
    box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.45);
}

.post-tip-total--celebrate .post-tip-coin {
    animation: coinSpin 0.9s ease;
}

.post-tip-coin {
    color: #d97706;
}

.post-tip-amount {
    font-variant-numeric: tabular-nums;
}

.post-tip-count {
    font-weight: 500;
    color: #a16207;
    font-size: 0.7rem;
}

.post-tip-sparkles {
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0;
}

.post-tip-total--shine .post-tip-sparkles {
    opacity: 1;
}

.post-tip-sparkle {
    position: absolute;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #fde68a;
    box-shadow: 0 0 6px #fbbf24;
    animation: sparkle 3s ease-out forwards;
    animation-delay: calc(var(--i) * 0.08s);
    top: 50%;
    left: calc(10% + var(--i) * 7%);
}

.tip-popover {
    position: absolute;
    z-index: 200;
    width: 240px;
    padding: 14px 14px 12px;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.55);
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(22px) saturate(180%);
    -webkit-backdrop-filter: blur(22px) saturate(180%);
    box-shadow:
        0 18px 40px rgba(15, 23, 42, 0.18),
        0 1px 0 rgba(255, 255, 255, 0.65) inset;
    color: #1f2937;
}

.tip-popover-arrow {
    position: absolute;
    top: -6px;
    left: 52px;
    width: 12px;
    height: 12px;
    transform: rotate(45deg);
    background: rgba(255, 255, 255, 0.78);
    border-top: 1px solid rgba(255, 255, 255, 0.65);
    border-left: 1px solid rgba(255, 255, 255, 0.65);
}

.tip-popover-title {
    margin: 0;
    font-size: 0.92rem;
    font-weight: 700;
}

.tip-popover-sub {
    margin: 0.15rem 0 0.65rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.tip-popover-label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.72rem;
    font-weight: 600;
    color: #4b5563;
}

.tip-popover-input {
    width: 100%;
    border-radius: 8px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    background: rgba(255, 255, 255, 0.85);
    padding: 0.45rem 0.55rem;
    font-size: 0.9rem;
    outline: none;
}

.tip-popover-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.tip-popover-balance,
.tip-popover-fee {
    margin: 0.35rem 0 0;
    font-size: 0.68rem;
    color: #6b7280;
}

.tip-popover-error {
    margin: 0.45rem 0 0;
    font-size: 0.72rem;
    color: #dc2626;
}

.tip-popover-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.4rem;
    margin-top: 0.65rem;
}

.tip-popover-cancel {
    border-radius: 7px;
    padding: 0.28rem 0.55rem;
    font-size: 0.72rem;
    color: #4b5563;
}

.tip-popover-cancel:hover {
    background: rgba(0, 0, 0, 0.05);
}

.tip-popover-ok {
    min-width: 44px;
    border-radius: 7px;
    padding: 0.28rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
    box-shadow: 0 1px 2px rgba(37, 99, 235, 0.35);
}

.tip-popover-ok:disabled {
    opacity: 0.6;
}

.tip-popover-frost {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    border-radius: inherit;
}

.tip-frost-bit {
    position: absolute;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 0 8px rgba(191, 219, 254, 0.9);
    animation: frostFloat 1.1s ease-out forwards;
    animation-delay: calc(var(--i) * 0.05s);
    top: 40%;
    left: calc(8% + var(--i) * 7%);
}

@keyframes tipPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.45); }
    50% { box-shadow: 0 0 0 8px rgba(251, 191, 36, 0); }
}

@keyframes coinSpin {
    0% { transform: rotateY(0deg) scale(1); }
    50% { transform: rotateY(180deg) scale(1.25); filter: brightness(1.4); }
    100% { transform: rotateY(360deg) scale(1); }
}

@keyframes sparkle {
    0% { opacity: 0; transform: translateY(0) scale(0.3); }
    20% { opacity: 1; }
    100% { opacity: 0; transform: translateY(-14px) scale(1.2); }
}

@keyframes frostFloat {
    0% { opacity: 0; transform: translateY(6px) scale(0.4); }
    30% { opacity: 1; }
    100% { opacity: 0; transform: translateY(-18px) scale(1); }
}
</style>
