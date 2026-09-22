<script setup>
/**
 * Administración de propinas y economía de monedas: historial y configuración de montos.
 */

import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UserPicker from '@/Components/Admin/UserPicker.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';

const props = defineProps({
    analytics: {
        type: Object,
        required: true,
    },
    transactions: {
        type: Object,
        required: true,
    },
});

const feeForm = useForm({
    platform_fee_percent: props.analytics.platform_fee_percent ?? 10,
});

const depositForm = useForm({
    user_id: '',
    amount: 50,
});


/** Guarda el porcentaje de comisión por propinas. */
const submitFee = () => feeForm.put('/admin/propinas/comision', { preserveScroll: true });


/** Registra un depósito manual de monedas a un usuario. */
const submitDeposit = () => {
    if (!depositForm.user_id) return;
    depositForm.post('/admin/propinas/depositos', {
        preserveScroll: true,
        onSuccess: () => depositForm.reset('amount'),
    });
};


/** Formatea montos de monedas con dos decimales. */
const fmt = (n) => Number(n ?? 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });


/** Formatea una fecha ISO en formato local español. */
const formatDate = (iso) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};


/** Devuelve la etiqueta legible del tipo de transacción. */
const typeLabel = (type) => ({
    tip_post: 'Propina',
    deposit: 'Compra/depósito',
    withdraw: 'Retiro',
    subscription_premium: 'Suscripción',
    tip_comment: 'Propina comentario',
}[type] ?? type);
</script>

<template>
    <!-- Economía de monedas y historial de propinas -->

    <AdminLayout>
        <div class="space-y-4">
            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header">Resumen de propinas y monedas</div>
                <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded border border-fb-border bg-[#F5F6F7] p-3">
                        <p class="text-xs text-fb-muted">Propinas enviadas</p>
                        <p class="text-xl font-bold">{{ analytics.summary.tips_count }}</p>
                        <p class="text-xs text-fb-muted">{{ fmt(analytics.summary.tips_gross) }} monedas brutas</p>
                    </div>
                    <div class="rounded border border-fb-border bg-[#F5F6F7] p-3">
                        <p class="text-xs text-fb-muted">Comisión plataforma</p>
                        <p class="text-xl font-bold text-green-700">{{ fmt(analytics.summary.platform_fees_collected) }}</p>
                        <p class="text-xs text-fb-muted">Hoy: {{ fmt(analytics.summary.today_platform_fees) }}</p>
                    </div>
                    <div class="rounded border border-fb-border bg-[#F5F6F7] p-3">
                        <p class="text-xs text-fb-muted">Creadores (neto recibido)</p>
                        <p class="text-xl font-bold">{{ fmt(analytics.summary.creators_net) }}</p>
                        <p class="text-xs text-fb-muted">{{ analytics.summary.posts_tipped }} posts con propinas</p>
                    </div>
                    <div class="rounded border border-fb-border bg-[#F5F6F7] p-3">
                        <p class="text-xs text-fb-muted">Compras de monedas</p>
                        <p class="text-xl font-bold">{{ fmt(analytics.summary.deposits_total) }}</p>
                        <p class="text-xs text-fb-muted">{{ analytics.summary.deposits_count }} depósitos</p>
                    </div>
                </div>
                <div class="border-t border-fb-border px-4 py-3 text-sm text-fb-muted">
                    Saldo cuenta plataforma
                    <strong class="text-fb-text">{{ fmt(analytics.platform_balance) }}</strong>
                    <span v-if="analytics.platform_username"> (@{{ analytics.platform_username }})</span>
                    · Hoy: {{ analytics.summary.today_tips_count }} propinas ({{ fmt(analytics.summary.today_tips_gross) }})
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Comisión editable</div>
    <!-- Formulario principal -->
                    <form class="space-y-3 p-4" @submit.prevent="submitFee">
                        <p class="text-xs text-fb-muted">
                            Porcentaje que retiene la plataforma de cada propina. El creador recibe el resto.
                        </p>
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="gofio-field-label">Comisión (%)</label>
                                <input v-model.number="feeForm.platform_fee_percent" type="number" min="0" max="50" step="0.5" class="gofio-input" />
                            </div>
                            <button type="submit" class="gofio-btn-primary text-sm" :disabled="feeForm.processing">Guardar</button>
                        </div>
                        <p class="text-xs text-fb-muted">
                            Ejemplo: propina 100 → plataforma {{ fmt(100 * feeForm.platform_fee_percent / 100) }} · creador {{ fmt(100 - 100 * feeForm.platform_fee_percent / 100) }}
                        </p>
                    </form>
                </div>

                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Registrar compra / depósito de monedas</div>
                    <form class="space-y-3 p-4" @submit.prevent="submitDeposit">
                        <div>
                            <label class="gofio-field-label">Usuario</label>
                            <UserPicker v-model="depositForm.user_id" />
                        </div>
                        <div>
                            <label class="gofio-field-label">Monto (monedas)</label>
                            <input v-model.number="depositForm.amount" type="number" min="0.01" step="0.01" class="gofio-input" />
                        </div>
                        <button type="submit" class="gofio-btn-primary text-sm" :disabled="depositForm.processing || !depositForm.user_id">
                            Acreditar monedas
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Mayores receptores</div>
                    <div class="divide-y divide-fb-border">
                        <div v-if="!analytics.top_receivers.length" class="p-4 text-sm text-fb-muted">Sin datos aún.</div>
                        <div v-for="row in analytics.top_receivers" :key="row.user_id" class="flex justify-between px-4 py-2 text-sm">
                            <span>@{{ row.username }}</span>
                            <span class="font-semibold">{{ fmt(row.net_received) }} <span class="text-xs text-fb-muted">({{ row.tips_count }})</span></span>
                        </div>
                    </div>
                </div>
                <div class="gofio-box overflow-hidden">
                    <div class="gofio-box-header">Mayores emisores</div>
                    <div class="divide-y divide-fb-border">
                        <div v-if="!analytics.top_senders.length" class="p-4 text-sm text-fb-muted">Sin datos aún.</div>
                        <div v-for="row in analytics.top_senders" :key="row.user_id" class="flex justify-between px-4 py-2 text-sm">
                            <span>@{{ row.username }}</span>
                            <span class="font-semibold">{{ fmt(row.gross_sent) }} <span class="text-xs text-fb-muted">({{ row.tips_count }})</span></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header">Propinas por día (últimos 30 días)</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#F5F6F7] text-left text-xs text-fb-muted">
                            <tr>
                                <th class="px-4 py-2">Día</th>
                                <th class="px-4 py-2">Cantidad</th>
                                <th class="px-4 py-2">Bruto</th>
                                <th class="px-4 py-2">Comisión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!analytics.daily_tips.length">
                                <td colspan="4" class="px-4 py-3 text-fb-muted">Sin propinas registradas.</td>
                            </tr>
                            <tr v-for="row in analytics.daily_tips" :key="row.day" class="border-t border-fb-border">
                                <td class="px-4 py-2">{{ row.day }}</td>
                                <td class="px-4 py-2">{{ row.count }}</td>
                                <td class="px-4 py-2">{{ fmt(row.gross) }}</td>
                                <td class="px-4 py-2">{{ fmt(row.fees) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header">Movimientos recientes</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#F5F6F7] text-left text-xs text-fb-muted">
                            <tr>
                                <th class="px-4 py-2">Fecha</th>
                                <th class="px-4 py-2">Tipo</th>
                                <th class="px-4 py-2">De → A</th>
                                <th class="px-4 py-2">Publicación</th>
                                <th class="px-4 py-2">Monto</th>
                                <th class="px-4 py-2">Comisión</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tx in transactions.data" :key="tx.id" class="border-t border-fb-border">
                                <td class="px-4 py-2 whitespace-nowrap">{{ formatDate(tx.created_at) }}</td>
                                <td class="px-4 py-2">{{ typeLabel(tx.type) }}</td>
                                <td class="px-4 py-2">{{ tx.sender ?? '—' }} → {{ tx.receiver ?? '—' }}</td>
                                <td class="px-4 py-2">{{ tx.post_title ?? '—' }}</td>
                                <td class="px-4 py-2 font-semibold">{{ fmt(tx.amount) }}</td>
                                <td class="px-4 py-2">{{ fmt(tx.platform_fee) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Paginación de movimientos recientes de billetera -->
                <AdminPagination :paginator="transactions" item-label="movimientos" />
            </div>
        </div>
    </AdminLayout>
</template>
