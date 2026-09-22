<script setup>
/**
 * Página de solicitud y estado de verificación de cuenta con requisitos y formulario de envío.
 */

import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import GofioLayout from '@/Layouts/GofioLayout.vue';

const props = defineProps({
    status: {
        type: Object,
        required: true,
    },
    documentTypes: {
        type: Array,
        default: () => [],
    },
});

const form = ref({
    full_name: '',
    document_type: 'id_card',
    user_notes: '',
});
const documentFile = ref(null);
const loading = ref(false);
const error = ref('');
const success = ref('');


/** Asocia el documento de identidad seleccionado a la solicitud. */
const onFileChange = (event) => {
    documentFile.value = event.target.files[0] ?? null;
};


/** Envía el formulario al servidor y gestiona errores de validación. */
const submit = async () => {
    if (!documentFile.value) {
        error.value = 'Debes adjuntar un documento.';
        return;
    }

    loading.value = true;
    error.value = '';
    success.value = '';

    const data = new FormData();
    data.append('full_name', form.value.full_name);
    data.append('document_type', form.value.document_type);
    data.append('document', documentFile.value);
    if (form.value.user_notes) {
        data.append('user_notes', form.value.user_notes);
    }

    try {
        const response = await window.axios.post('/api/verification/submit', data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        success.value = response.data.message;
        form.value = { full_name: '', document_type: 'id_card', user_notes: '' };
        documentFile.value = null;
        router.reload({ only: ['status'] });
    } catch (e) {
        error.value = e.response?.data?.message
            || e.response?.data?.errors?.document?.[0]
            || 'No se pudo enviar la solicitud.';
    } finally {
        loading.value = false;
    }
};

const statusLabel = {
    pending: 'Pendiente de revisión',
    approved: 'Aprobada',
    rejected: 'Rechazada',
    revoked: 'Revocada',
};
</script>

<template>
    <!-- Solicitud y estado de verificación de cuenta -->

    <GofioLayout>
        <template #feed>
            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header">Verificación de identidad</div>
                <div class="space-y-4 p-4">
                    <p class="text-sm text-fb-muted">
                        Obtén la insignia verificada confirmando tu identidad con documento. Es independiente de Creator Plus:
                        puedes tener ambas verificaciones si lo deseas.
                    </p>

                    <div v-if="status.is_staff_verified" class="rounded border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        <i class="fa-solid fa-shield-halved mr-1"></i>
                        Verificado automáticamente por tu rango de staff (moderación). No necesitas enviar documentos.
                    </div>

                    <div v-else-if="status.has_identity_verification" class="rounded border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900">
                        <i class="fa-solid fa-circle-check mr-1 text-[#00EAFF]"></i>
                        Tu identidad está verificada.
                    </div>

                    <div v-if="status.is_creator_plus" class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        <i class="fa-solid fa-crown mr-1"></i>
                        Creator Plus activo — badge premium visible en tu perfil y publicaciones.
                    </div>

                    <div v-if="status.latest_request?.status === 'pending'" class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Tienes una solicitud pendiente enviada el
                        {{ new Date(status.latest_request.created_at).toLocaleDateString('es-ES') }}.
                    </div>

                    <div v-else-if="status.latest_request?.status === 'rejected'" class="rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <p>Tu última solicitud fue rechazada.</p>
                        <p v-if="status.latest_request.admin_notes" class="mt-1">
                            Motivo: {{ status.latest_request.admin_notes }}
                        </p>
                    </div>

    <!-- Formulario principal -->
                    <form v-if="status.can_submit" class="space-y-3" @submit.prevent="submit">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Nombre completo (como en el documento)</label>
                            <input v-model="form.full_name" type="text" class="gofio-input" required />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Tipo de documento</label>
                            <select v-model="form.document_type" class="gofio-input">
                                <option v-for="type in documentTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Documento (JPG, PNG o PDF, máx. 5 MB)</label>
                            <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="gofio-input" @change="onFileChange" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Notas (opcional)</label>
                            <textarea v-model="form.user_notes" rows="3" class="gofio-input"></textarea>
                        </div>

                        <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
                        <p v-if="success" class="text-xs text-green-600">{{ success }}</p>

                        <button type="submit" class="gofio-btn-primary" :disabled="loading">
                            {{ loading ? 'Enviando...' : 'Enviar solicitud' }}
                        </button>
                    </form>

                    <div v-if="status.latest_request" class="border-t border-fb-border pt-4 text-xs text-fb-muted">
                        <p>Estado: {{ statusLabel[status.latest_request.status] }}</p>
                    </div>

                    <Link href="/configuracion/creator-plus" class="inline-block text-sm font-semibold text-fb-link hover:underline">
                        ¿Quieres más beneficios? Conoce Creator Plus →
                    </Link>
                </div>
            </div>
        </template>
    </GofioLayout>
</template>
