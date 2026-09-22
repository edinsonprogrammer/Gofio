<script setup>
/**
 * Gestión de premios otorgables a usuarios con categoría e icono.
 */

import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import IconPickerField from '@/Components/Admin/IconPickerField.vue';
import UserPicker from '@/Components/Admin/UserPicker.vue';

defineProps({
    categories: { type: Array, required: true },
});

const grantAwardId = ref(null);

const categoryForm = useForm({ name: '', sort_order: 0 });
const awardForm = useForm({
    award_category_id: '',
    name: '',
    description: '',
    icon: 'fa-solid fa-award',
    color: '#F39C12',
    sort_order: 0,
});
const grantForm = useForm({ user_id: '', note: '' });


/** Crea una categoría de premios. */
const submitCategory = () => categoryForm.post('/admin/premios/categorias');

/** Registra un premio nuevo en el catálogo. */
const submitAward = () => awardForm.post('/admin/premios');

/** Otorga un premio manualmente a un usuario. */
const submitGrant = (award) => {
    if (!grantForm.user_id) return;
    grantForm.post(`/admin/premios/${award.id}/otorgar`, {
        onSuccess: () => { grantAwardId.value = null; grantForm.reset(); },
    });
};
</script>

<template>
    <!-- Gestión de premios y categorías -->

    <AdminLayout>
        <div class="space-y-4">
            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header">Categorías de premios</div>
    <!-- Formulario principal -->
                <form class="flex flex-wrap items-end gap-2 p-3" @submit.prevent="submitCategory">
                    <div>
                        <label class="gofio-field-label">Nombre de la categoría</label>
                        <input v-model="categoryForm.name" placeholder="Ej. Comunidad" class="gofio-input text-xs" required />
                    </div>
                    <button type="submit" class="gofio-btn-primary text-xs">Crear categoría</button>
                </form>
            </div>

            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header">Premios (w_premios)</div>
                <form class="grid gap-3 border-b border-fb-border bg-[#F5F6F7] p-3 sm:grid-cols-3" @submit.prevent="submitAward">
                    <div>
                        <label class="gofio-field-label">Categoría</label>
                        <select v-model="awardForm.award_category_id" class="gofio-input text-xs" required>
                            <option value="">Selecciona…</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="gofio-field-label">Nombre del premio</label>
                        <input v-model="awardForm.name" placeholder="Nombre premio" class="gofio-input text-xs" required />
                    </div>
                    <div>
                        <label class="gofio-field-label">Descripción</label>
                        <input v-model="awardForm.description" placeholder="Opcional" class="gofio-input text-xs" />
                    </div>
                    <IconPickerField
                        v-model="awardForm.icon"
                        label="Icono"
                        default-icon="fa-solid fa-award"
                        :preview-color="awardForm.color"
                        required
                    />
                    <div>
                        <label class="gofio-field-label">Color</label>
                        <input v-model="awardForm.color" placeholder="#F39C12" class="gofio-input text-xs" required />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="gofio-btn-primary text-xs">Crear premio</button>
                    </div>
                </form>

                <div v-for="cat in categories" :key="cat.id" class="border-b border-fb-border p-4 last:border-0">
                    <h3 class="mb-2 text-sm font-semibold">{{ cat.name }}</h3>
                    <div v-if="!cat.awards?.length" class="text-xs text-fb-muted">Sin premios en esta categoría.</div>
                    <div v-for="award in cat.awards" :key="award.id" class="mb-3 rounded border border-fb-border p-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <FaIcon :icon="award.icon" class="text-xl" :style="{ color: award.color }" />
                                <div>
                                    <p class="font-semibold">{{ award.name }}</p>
                                    <p class="text-xs text-fb-muted">{{ award.description }}</p>
                                </div>
                            </div>
                            <button class="gofio-btn-secondary text-xs" @click="grantAwardId = grantAwardId === award.id ? null : award.id">
                                Otorgar
                            </button>
                        </div>
                        <form v-if="grantAwardId === award.id" class="mt-3 flex flex-wrap items-start gap-2" @submit.prevent="submitGrant(award)">
                            <div class="w-56"><UserPicker v-model="grantForm.user_id" /></div>
                            <input v-model="grantForm.note" placeholder="Nota" class="gofio-input w-32 text-xs" />
                            <button type="submit" class="gofio-btn-primary text-xs" :disabled="!grantForm.user_id">Otorgar premio</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
