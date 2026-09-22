<script setup>
/**
 * Listado y gestión de tickets de soporte con cambio de estado y respuestas.
 */

import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import { label, ticketStatusLabels } from '@/utils/adminLabels';
defineProps({ tickets: Object });

const replies = ref({});


/** Actualiza el registro existente con los valores editados. */
const update = (ticket) => router.put(`/admin/tickets/${ticket.id}`, {
    status: ticket._status ?? ticket.status,
    priority: ticket.priority,
    admin_reply: replies.value[ticket.id] ?? ticket.admin_reply,
}, { preserveScroll: true });
</script>

<template>
    <!-- Listado de tickets de soporte -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header">Tickets de soporte</div>
            <div v-if="!tickets.data.length" class="p-4 text-sm text-fb-muted">Sin tickets.</div>
            <div
                v-for="ticket in tickets.data"
                :key="ticket.id"
                class="border-b border-fb-border p-4"
                :class="{ 'bg-gradient-to-r from-amber-50/80 to-transparent': ticket.is_creator_plus_priority }"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <p class="font-semibold">{{ ticket.subject }}</p>
                    <span
                        v-if="ticket.is_creator_plus_priority"
                        class="rounded bg-gradient-to-r from-amber-200 to-yellow-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-900"
                    >
                        Creator Plus prioritario
                    </span>
                </div>
                <p class="text-xs text-fb-muted">
                    @{{ ticket.user?.username }} · {{ ticket.category }} · {{ label(ticketStatusLabels, ticket.status) }}
                    <span v-if="ticket.priority === 'high'" class="ml-1 font-semibold text-red-600">· Alta prioridad</span>
                </p>
                <p class="mt-2 text-sm">{{ ticket.body }}</p>
                <textarea v-model="replies[ticket.id]" rows="2" class="gofio-input mt-2 text-xs" :placeholder="ticket.admin_reply || 'Respuesta del equipo...'" />
                <div class="mt-2 flex flex-wrap gap-2">
                    <select v-model="ticket._status" class="gofio-input w-auto text-xs">
                        <option :value="undefined" disabled>Cambiar estado</option>
                        <option value="open">Abierto</option>
                        <option value="in_progress">En progreso</option>
                        <option value="closed">Cerrado</option>
                    </select>
                    <button type="button" class="gofio-btn-primary text-xs" @click="update(ticket)">Actualizar</button>
                </div>
            </div>
        </div>
        <!-- Paginación de tickets -->
        <AdminPagination :paginator="tickets" item-label="tickets" />
    </AdminLayout>
</template>
