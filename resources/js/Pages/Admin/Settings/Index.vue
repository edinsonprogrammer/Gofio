<script setup>
/**
 * Configuración global del sitio: mantenimiento, límites y parámetros generales.
 */

import { useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

const props = defineProps({
    settings: Object,
    logs: Object,
});

const form = useForm({ ...props.settings });

const logoForm = useForm({
    logo: null,
});

const logoPreview = ref(props.settings?.site_logo_url || '');
const logoInputRef = ref(null);

watch(
    () => props.settings?.site_logo_url,
    (url) => {
        if (!logoForm.logo) {
            logoPreview.value = url || '';
        }
    },
);

/** Abre el selector de archivo del logo. */
const pickLogo = () => logoInputRef.value?.click();

/** Previsualiza el logo seleccionado antes de subirlo. */
const onLogoSelected = (event) => {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    logoForm.logo = file;
    logoPreview.value = URL.createObjectURL(file);
};

/** Sube el logo al servidor (redimensionado automáticamente). */
const uploadLogo = () => {
    if (!logoForm.logo) {
        return;
    }

    logoForm.post('/admin/configuracion/logo', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            logoForm.reset();
            if (logoInputRef.value) {
                logoInputRef.value.value = '';
            }
        },
        onFinish: () => {
            logoForm.logo = null;
        },
    });
};

/** Elimina el logo y restaura el wordmark del sitio. */
const removeLogo = () => {
    router.delete('/admin/configuracion/logo', {
        preserveScroll: true,
        onSuccess: () => {
            logoPreview.value = '';
            logoForm.reset();
            if (logoInputRef.value) {
                logoInputRef.value.value = '';
            }
        },
    });
};


/** Envía el formulario al servidor y gestiona errores de validación. */
const submit = () => form.put('/admin/configuracion');

</script>

<template>
    <!-- Formulario de configuración global del sitio -->

    <AdminLayout>
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header">Configuración general</div>
    <!-- Formulario principal -->
            <form class="space-y-4 p-4" @submit.prevent="submit">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Logo del sitio</label>
                        <div class="flex flex-col gap-3 rounded border border-fb-border bg-white p-3 sm:flex-row sm:items-center">
                            <div class="site-logo-admin-preview flex min-h-[3rem] min-w-[8rem] items-center justify-center rounded border border-dashed border-fb-border bg-[#FAFBFC] px-4 py-2">
                                <img
                                    v-if="logoPreview"
                                    :src="logoPreview"
                                    alt="Vista previa del logo"
                                    class="site-logo site-logo--header"
                                />
                                <span v-else class="text-xs text-fb-muted">Sin logo (se usa el nombre)</span>
                            </div>
                            <div class="flex flex-1 flex-col gap-2">
                                <p class="text-xs text-fb-muted">
                                    PNG, JPG, WebP, GIF, BMP, SVG o ICO. Se redimensiona automáticamente al tamaño del título (36px de alto).
                                </p>
                                <input
                                    ref="logoInputRef"
                                    type="file"
                                    accept="image/*,.svg,.ico"
                                    class="hidden"
                                    @change="onLogoSelected"
                                />
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="gofio-btn-secondary text-sm" @click="pickLogo">
                                        Elegir imagen
                                    </button>
                                    <button
                                        type="button"
                                        class="gofio-btn-primary text-sm"
                                        :disabled="!logoForm.logo || logoForm.processing"
                                        @click="uploadLogo"
                                    >
                                        {{ logoForm.processing ? 'Subiendo…' : 'Subir logo' }}
                                    </button>
                                    <button
                                        v-if="logoPreview"
                                        type="button"
                                        class="text-sm font-semibold text-red-600 hover:underline"
                                        :disabled="logoForm.processing"
                                        @click="removeLogo"
                                    >
                                        Quitar logo
                                    </button>
                                </div>
                                <p v-if="logoForm.errors.logo" class="text-xs text-red-600">{{ logoForm.errors.logo }}</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Título del sitio</label>
                        <input v-model="form.site_title" class="gofio-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Slogan</label>
                        <input v-model="form.site_slogan" class="gofio-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Correo electrónico</label>
                        <input v-model="form.site_email" type="email" class="gofio-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Copyright (pie de página)</label>
                        <input v-model="form.site_copyright" class="gofio-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Rango por defecto (ID)</label>
                        <input v-model.number="form.default_rango_id" type="number" class="gofio-input" />
                    </div>
                </div>

                <div class="rounded border border-fb-border bg-[#F5F6F7] p-3">
                    <p class="mb-3 text-sm font-semibold">SEO y metadatos (buscadores e IAs)</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Título SEO (meta title)</label>
                            <input v-model="form.seo_meta_title" class="gofio-input" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Descripción SEO (meta description)</label>
                            <textarea v-model="form.seo_meta_description" rows="3" class="gofio-input" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Palabras clave (keywords)</label>
                            <textarea v-model="form.seo_meta_keywords" rows="2" class="gofio-input" placeholder="Separadas por coma" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Imagen Open Graph (URL)</label>
                            <input v-model="form.seo_og_image" class="gofio-input" placeholder="https://..." />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Organización (Schema.org)</label>
                            <input v-model="form.seo_organization_name" class="gofio-input" />
                        </div>
                    </div>
                    <label class="mt-3 flex items-center gap-2 text-sm">
                        <input v-model="form.seo_indexnow_enabled" type="checkbox" />
                        Notificar buscadores al publicar (IndexNow)
                    </label>
                    <p class="mt-2 text-xs text-fb-muted">
                        Sitemap: <a href="/sitemap.xml" target="_blank" class="text-fb-link hover:underline">/sitemap.xml</a>
                        · Robots: <a href="/robots.txt" target="_blank" class="text-fb-link hover:underline">/robots.txt</a>
                        · IA: <a href="/llms.txt" target="_blank" class="text-fb-link hover:underline">/llms.txt</a>
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Mensaje de bienvenida</label>
                    <input v-model="form.welcome_message" class="gofio-input" placeholder="Usa {username}" />
                </div>

                <div class="rounded border border-fb-border bg-[#F5F6F7] p-3">
                    <p class="mb-3 text-sm font-semibold">Límites diarios para usuarios que inician</p>
                    <p class="mb-3 text-xs text-fb-muted">
                        Estos valores aplican al rango por defecto (Newbie). Cada rango puede tener sus propios límites en
                        <a href="/admin/rangos" class="text-fb-link hover:underline">Rangos</a>.
                    </p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Máx. posts/día</label>
                            <input v-model.number="form.max_posts_per_day" type="number" class="gofio-input" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Máx. comentarios/día</label>
                            <input v-model.number="form.max_comments_per_day" type="number" class="gofio-input" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Máx. votos/día</label>
                            <input v-model.number="form.max_votes_per_day" type="number" class="gofio-input" />
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Votos para destacado</label>
                        <input v-model.number="form.featured_votes_threshold" type="number" class="gofio-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Comisión propinas (%)</label>
                        <input v-model.number="form.platform_fee_percent" type="number" min="0" max="50" step="0.5" class="gofio-input" />
                        <p class="mt-1 text-xs text-fb-muted">Panel detallado en <a href="/admin/propinas" class="text-fb-link hover:underline">Propinas / Monedas</a>.</p>
                    </div>
                </div>

                <!-- Las reglas de karma se gestionan desde su propio panel -->
                <div class="rounded border border-fb-border bg-[#F5F6F7] p-3">
                    <p class="mb-1 text-sm font-semibold">Reglas de karma</p>
                    <p class="mb-3 text-xs text-fb-muted">
                        Crea, edita y desactiva reglas de karma (puntos por publicar, comentar, hitos, etc.)
                        desde el panel dedicado.
                    </p>
                    <a
                        href="/admin/karma"
                        class="inline-flex items-center gap-1.5 rounded bg-fb-accent px-3 py-1.5 text-sm font-medium text-white hover:opacity-90"
                    >
                        <FaIcon icon="fa-solid fa-star-half-stroke" />
                        Gestionar reglas de karma
                    </a>
                </div>

                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="flex items-center gap-2"><input v-model="form.registration_enabled" type="checkbox" /> Registro activo</label>
                    <label class="flex items-center gap-2"><input v-model="form.offline_mode" type="checkbox" /> Modo mantenimiento</label>
                    <label class="flex items-center gap-2"><input v-model="form.allow_tips" type="checkbox" /> Propinas activas</label>
                    <label class="flex items-center gap-2"><input v-model="form.allow_uploads" type="checkbox" /> Subidas activas</label>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Mensaje de mantenimiento</label>
                    <textarea v-model="form.offline_message" rows="3" class="gofio-input" placeholder="Mensaje que verán los visitantes en la pantalla de mantenimiento"></textarea>
                    <p class="mt-1 text-xs text-fb-muted">Solo el equipo staff podrá iniciar sesión mientras el modo mantenimiento esté activo.</p>
                </div>

                <button type="submit" class="gofio-btn-primary" :disabled="form.processing">Guardar configuración</button>
            </form>
        </div>
    </AdminLayout>
</template>
