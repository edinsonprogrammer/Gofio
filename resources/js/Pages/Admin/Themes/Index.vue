<script setup>
/**
 * CRUD de temas visuales: temas de carpeta y combinaciones de colores del panel.
 * Las combinaciones solo alteran la paleta; la estructura de la página no cambia.
 */

import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import { applyGofioTheme } from '@/composables/useGofioTheme';
import { buildPaletteFromBases, normalizeHex } from '@/utils/themeColorMix';

const props = defineProps({
    themes: {
        type: Array,
        default: () => [],
    },
    variableKeys: {
        type: Array,
        default: () => [],
    },
    variableGroups: {
        type: Object,
        default: () => ({}),
    },
    colorGroups: {
        type: Object,
        default: () => ({}),
    },
    variableSectors: {
        type: Object,
        default: () => ({}),
    },
    defaults: {
        type: Object,
        default: () => ({}),
    },
    themesPath: {
        type: String,
        default: '',
    },
});

const editingId = ref(null);
const saving = ref(false);
const syncing = ref(false);
const deletingId = ref(null);
const applyingId = ref(null);
const colorsFeedback = ref('');

/** Colores base para la mezcla automática de la paleta. */
const mixBases = reactive({
    brand: '#0D9488',
    background: '#F1F5F9',
    accent: '#2DD4BF',
});

/** Intensidad de mezcla entre marca y acento (0 suave, 1 contrastada). */
const blendRatio = ref(0.5);

const emptyForm = () => ({
    name: '',
    slug: '',
    description: '',
    is_default: false,
    requires_creator_plus: false,
    is_active: true,
    variables: { ...props.defaults },
});

const form = reactive(emptyForm());

/** Tema que se está editando actualmente. */
const editingTheme = computed(() =>
    props.themes.find((t) => t.id === editingId.value) ?? null,
);

/** Solo lectura para temas importados desde carpeta. */
const isFolderEdit = computed(() => editingTheme.value?.source === 'folder');

/** Formulario de combinación de colores (no altera layout). */
const isColorForm = computed(() => editingId.value !== null && !isFolderEdit.value);

/** Grupos visibles según el tipo de edición. */
const activeColorGroups = computed(() => {
    if (Object.keys(props.colorGroups).length) {
        return props.colorGroups;
    }
    return props.variableGroups;
});

/** Temas instalados desde carpeta del proyecto. */
const folderThemes = computed(() => props.themes.filter((t) => t.source === 'folder'));

/** Combinaciones de colores creadas o duplicadas desde el panel admin. */
const colorCombinations = computed(() => props.themes.filter((t) => t.source !== 'folder'));

/** Etiqueta legible de una variable CSS. */
const labelFor = (key) => key.replace(/^--/, '').replace(/-/g, ' ');

/** Sector de la interfaz donde se aplica una variable. */
const sectorFor = (key) => props.variableSectors[key] ?? 'Variable de color';

/** Genera un slug único sugerido a partir de un nombre. */
const slugify = (name) => name
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 60);

/** Sincroniza los pickers base con los colores actuales del formulario. */
const syncMixBasesFromForm = () => {
    mixBases.brand = form.variables['--color-brand'] ?? props.defaults['--color-brand'] ?? '#0D9488';
    mixBases.background = form.variables['--bg-principal'] ?? props.defaults['--bg-principal'] ?? '#F1F5F9';
    mixBases.accent = form.variables['--color-accent'] ?? props.defaults['--color-accent'] ?? '#2DD4BF';
};

/** Variables CSS completas listas para inyectar en la interfaz. */
const mergedVariables = () => ({ ...props.defaults, ...form.variables });

/** Aplica la paleta actual del formulario a toda la interfaz (vista previa en vivo). */
const applyColorsToInterface = () => {
    applyGofioTheme(
        mergedVariables(),
        form.slug || 'preview-combination',
        null,
        'default',
        null,
    );
};

/** Mezcla los tres colores base y los reparte en todos los sectores del formulario. */
const applyColorMix = () => {
    mixBases.brand = normalizeHex(mixBases.brand, props.defaults['--color-brand'] ?? '#0D9488');
    mixBases.background = normalizeHex(mixBases.background, props.defaults['--bg-principal'] ?? '#F1F5F9');
    mixBases.accent = normalizeHex(mixBases.accent, props.defaults['--color-accent'] ?? '#2DD4BF');

    const mixed = buildPaletteFromBases(
        {
            brand: mixBases.brand,
            background: mixBases.background,
            accent: mixBases.accent,
        },
        blendRatio.value,
    );

    // Asignación por clave para mantener la reactividad de Vue en el formulario.
    Object.entries(mixed).forEach(([key, value]) => {
        form.variables[key] = value;
    });

    applyColorsToInterface();
    colorsFeedback.value = 'Colores aplicados a todos los sectores. Puedes ajustar cada uno o guardar la combinación.';
};

/** Activa una combinación guardada en la sesión del administrador. */
const applyCombinationColors = async (theme) => {
    if (theme.source === 'folder') return;

    applyingId.value = theme.id;
    colorsFeedback.value = '';

    try {
        applyGofioTheme(
            { ...props.defaults, ...theme.variables },
            theme.slug,
            null,
            'default',
            null,
        );

        await window.axios.post('/api/themes/select', { theme_id: theme.id });
        colorsFeedback.value = `Combinación «${theme.name}» aplicada en la interfaz.`;
    } catch (error) {
        colorsFeedback.value = error.response?.data?.message
            || error.response?.data?.errors?.theme_id?.[0]
            || 'No se pudo aplicar la combinación de colores.';
    } finally {
        applyingId.value = null;
    }
};

/** Colores para la miniatura de vista previa por sectores. */
const previewColors = computed(() => ({
    background: form.variables['--bg-principal'] ?? '#F1F5F9',
    surface: form.variables['--color-surface'] ?? '#FFFFFF',
    panel: form.variables['--color-panel'] ?? '#F0FDFA',
    border: form.variables['--color-border'] ?? '#CBD5E1',
    brand: form.variables['--color-brand'] ?? '#0D9488',
    brandHover: form.variables['--color-brand-hover'] ?? '#0F766E',
    topbarFrom: form.variables['--color-topbar-gradient-from'] ?? '#0F766E',
    topbarTo: form.variables['--color-topbar-gradient-to'] ?? '#14B8A6',
    accent: form.variables['--color-accent'] ?? '#2DD4BF',
    text: form.variables['--text-principal'] ?? '#0F172A',
}));

/** Prepara el formulario para crear una combinación de colores nueva. */
const startCreate = () => {
    editingId.value = 'new';
    colorsFeedback.value = '';
    Object.assign(form, emptyForm());
    syncMixBasesFromForm();
};

/** Duplica los colores de un tema existente como nueva combinación editable. */
const startDuplicate = (theme) => {
    editingId.value = 'new';
    colorsFeedback.value = '';
    const copyName = `${theme.name} (copia)`;
    Object.assign(form, {
        ...emptyForm(),
        name: copyName,
        slug: slugify(`${theme.slug}-copia-${Date.now().toString(36).slice(-4)}`),
        description: theme.description ? `Basado en ${theme.name}` : `Copia de ${theme.name}`,
        requires_creator_plus: theme.requires_creator_plus,
        variables: { ...props.defaults, ...theme.variables },
    });
    syncMixBasesFromForm();
};

/** Carga los datos del tema en el formulario de edición. */
const startEdit = (theme) => {
    editingId.value = theme.id;
    colorsFeedback.value = '';
    Object.assign(form, {
        name: theme.name,
        slug: theme.slug,
        description: theme.description ?? '',
        is_default: theme.is_default,
        requires_creator_plus: theme.requires_creator_plus,
        is_active: theme.is_active,
        variables: { ...props.defaults, ...theme.variables },
    });
    if (theme.source !== 'folder') {
        syncMixBasesFromForm();
    }
};

/** Cancela la edición y oculta el formulario. */
const cancel = () => {
    editingId.value = null;
};

/** Persiste los cambios mediante petición al backend. */
const save = () => {
    saving.value = true;
    const payload = { ...form };

    if (editingId.value === 'new') {
        router.post('/admin/temas', payload, {
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
                editingId.value = null;
            },
        });
    } else {
        router.put(`/admin/temas/${editingId.value}`, payload, {
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
                editingId.value = null;
            },
        });
    }
};

/** Elimina una combinación de colores creada en el panel. */
const destroyCombination = (theme) => {
    if (theme.source === 'folder') return;

    const usersNote = theme.users_count > 0
        ? ` ${theme.users_count} usuario(s) volverán al tema predeterminado.`
        : '';

    if (! confirm(`¿Eliminar la combinación «${theme.name}»?${usersNote}`)) return;

    deletingId.value = theme.id;
    router.delete(`/admin/temas/${theme.id}`, {
        preserveScroll: true,
        onFinish: () => { deletingId.value = null; },
    });
};

/** Sincroniza las carpetas de assets del tema con el servidor. */
const syncFolders = () => {
    syncing.value = true;
    router.post('/admin/temas/sincronizar', {}, {
        preserveScroll: true,
        onFinish: () => { syncing.value = false; },
    });
};

/** Muestra el color principal de una combinación en la lista. */
const themeSwatch = (theme) => theme.variables['--color-brand'] ?? '#0D9488';
</script>

<template>
    <!-- Gestión de temas visuales y combinaciones de colores -->
    <AdminLayout>
        <!-- Temas instalados desde carpeta -->
        <div class="gofio-box overflow-hidden">
            <div class="gofio-box-header flex items-center justify-between">
                <span>Temas instalados (carpeta)</span>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-fb-link hover:underline disabled:opacity-50"
                    :disabled="syncing"
                    @click="syncFolders"
                >
                    <FaIcon icon="fa-solid fa-rotate" :class="{ 'animate-spin': syncing }" />
                    {{ syncing ? 'Sincronizando...' : 'Sincronizar carpetas' }}
                </button>
            </div>

            <p class="border-b border-fb-border bg-[#F0FDFA] px-4 py-2 text-xs text-fb-muted">
                <FaIcon icon="fa-solid fa-folder-open" class="mr-1" />
                Copia una carpeta con <code class="rounded bg-white px-1 py-0.5">theme.json</code> en
                <code class="rounded bg-white px-1 py-0.5">{{ themesPath || 'themes/' }}</code>
                y sincroniza. El diseño y la estructura se editan en los archivos de la carpeta.
            </p>

            <ul v-if="folderThemes.length" class="divide-y divide-fb-border">
                <li
                    v-for="theme in folderThemes"
                    :key="theme.id"
                    class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
                >
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-1.5 font-semibold">
                            {{ theme.name }}
                            <span v-if="theme.is_default" class="rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-semibold text-green-700">
                                Predeterminado
                            </span>
                            <span
                                class="rounded bg-teal-100 px-1.5 py-0.5 text-[10px] font-semibold text-teal-800"
                                :title="`themes/${theme.slug}/`"
                            >
                                <FaIcon icon="fa-solid fa-folder" class="mr-0.5" />Carpeta
                            </span>
                            <span v-if="!theme.is_active" class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">
                                Inactivo
                            </span>
                        </p>
                        <p class="text-xs text-fb-muted">
                            {{ theme.slug }}
                            <template v-if="theme.author"> · por {{ theme.author }}</template>
                            <template v-if="theme.has_custom_css"> · CSS personalizado</template>
                            <template v-if="theme.users_count"> · {{ theme.users_count }} usuario(s)</template>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span
                            class="inline-block h-5 w-5 rounded border border-fb-border"
                            :style="{ background: themeSwatch(theme) }"
                        />
                        <button type="button" class="text-xs font-semibold text-fb-link hover:underline" @click="startDuplicate(theme)">
                            Duplicar colores
                        </button>
                        <button type="button" class="text-xs font-semibold text-fb-link hover:underline" @click="startEdit(theme)">
                            Editar
                        </button>
                    </div>
                </li>
            </ul>
            <p v-else class="p-6 text-center text-sm text-fb-muted">No hay temas de carpeta instalados.</p>
        </div>

        <!-- Combinaciones de colores creadas en el panel -->
        <div class="gofio-box mt-4 overflow-hidden">
            <div class="gofio-box-header flex items-center justify-between">
                <span>Combinaciones de colores</span>
                <button type="button" class="text-xs font-semibold text-fb-link hover:underline" @click="startCreate">
                    + Nueva combinación
                </button>
            </div>

            <p class="border-b border-fb-border bg-[#EFF6FF] px-4 py-2 text-xs text-fb-muted">
                Solo cambian colores de la interfaz; la estructura de la página permanece igual.
                Usa <strong>Aplicar colores</strong> para ver la paleta en vivo antes de guardar.
            </p>

            <p
                v-if="colorsFeedback && !editingId"
                class="border-b border-fb-border bg-green-50 px-4 py-2 text-xs text-green-800"
            >
                {{ colorsFeedback }}
            </p>

            <ul v-if="colorCombinations.length" class="divide-y divide-fb-border">
                <li
                    v-for="theme in colorCombinations"
                    :key="theme.id"
                    class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
                >
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-1.5 font-semibold">
                            {{ theme.name }}
                            <span v-if="theme.is_default" class="rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-semibold text-green-700">
                                Predeterminado
                            </span>
                            <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700">
                                Combinación
                            </span>
                            <span v-if="!theme.is_active" class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold text-red-700">
                                Inactiva
                            </span>
                        </p>
                        <p class="text-xs text-fb-muted">
                            {{ theme.slug }}
                            <template v-if="theme.users_count"> · {{ theme.users_count }} usuario(s) activos</template>
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                        <div class="flex items-center gap-1">
                            <span
                                class="inline-block h-5 w-5 rounded border border-fb-border"
                                :style="{ background: theme.variables['--color-brand'] ?? '#0D9488' }"
                                title="Marca"
                            />
                            <span
                                class="inline-block h-5 w-5 rounded border border-fb-border"
                                :style="{ background: theme.variables['--bg-principal'] ?? '#F1F5F9' }"
                                title="Fondo"
                            />
                            <span
                                class="inline-block h-5 w-5 rounded border border-fb-border"
                                :style="{ background: theme.variables['--color-accent'] ?? '#2DD4BF' }"
                                title="Acento"
                            />
                        </div>
                        <button type="button" class="text-xs font-semibold text-fb-link hover:underline" @click="startDuplicate(theme)">
                            Duplicar
                        </button>
                        <button type="button" class="text-xs font-semibold text-fb-link hover:underline" @click="startEdit(theme)">
                            Editar
                        </button>
                        <button
                            type="button"
                            class="text-xs font-semibold text-teal-700 hover:underline disabled:opacity-50"
                            :disabled="applyingId === theme.id"
                            @click="applyCombinationColors(theme)"
                        >
                            {{ applyingId === theme.id ? 'Aplicando…' : 'Aplicar colores' }}
                        </button>
                        <button
                            type="button"
                            class="text-xs font-semibold text-red-600 hover:underline disabled:opacity-50"
                            :disabled="deletingId === theme.id || theme.is_default"
                            :title="theme.is_default ? 'No se puede eliminar el tema predeterminado' : 'Eliminar combinación'"
                            @click="destroyCombination(theme)"
                        >
                            {{ deletingId === theme.id ? 'Eliminando…' : 'Eliminar' }}
                        </button>
                    </div>
                </li>
            </ul>
            <p v-else class="p-6 text-center text-sm text-fb-muted">
                No hay combinaciones de colores. Crea una nueva o duplica los colores de un tema instalado.
            </p>
        </div>

        <!-- Formulario de creación / edición -->
        <div v-if="editingId" class="gofio-box mt-4 overflow-hidden">
            <div class="gofio-box-header">
                {{
                    editingId === 'new'
                        ? 'Nueva combinación de colores'
                        : isFolderEdit
                            ? `Editar tema de carpeta: ${form.name}`
                            : `Editar combinación: ${form.name}`
                }}
            </div>

            <form class="space-y-4 p-4" @submit.prevent="save">
                <p v-if="isFolderEdit" class="rounded border border-teal-200 bg-teal-50 px-3 py-2 text-xs text-teal-900">
                    <FaIcon icon="fa-solid fa-folder" class="mr-1" />
                    Este tema proviene de <code>themes/{{ form.slug }}/</code>. Colores y estructura se
                    gestionan en la carpeta. Desde aquí solo controlas estado, predeterminado y Creator Plus.
                </p>

                <p v-else class="rounded border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs text-indigo-900">
                    <FaIcon icon="fa-solid fa-palette" class="mr-1" />
                    Esta combinación solo modifica colores. El layout, sidebars y dimensiones de la página no cambian.
                </p>

                <div v-if="!isFolderEdit" class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Nombre</label>
                        <input v-model="form.name" type="text" class="gofio-input" required />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Identificador</label>
                        <input v-model="form.slug" type="text" class="gofio-input" required />
                    </div>
                </div>

                <div v-if="!isFolderEdit">
                    <label class="mb-1 block text-sm font-medium">Descripción</label>
                    <input v-model="form.description" type="text" class="gofio-input" />
                </div>

                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="flex items-center gap-2">
                        <input v-model="form.is_default" type="checkbox" />
                        Tema por defecto
                    </label>
                    <label class="flex items-center gap-2">
                        <input v-model="form.requires_creator_plus" type="checkbox" />
                        Requiere Creator Plus
                    </label>
                    <label class="flex items-center gap-2">
                        <input v-model="form.is_active" type="checkbox" />
                        Activo
                    </label>
                </div>

                <!-- Mezcla rápida de paleta (solo combinaciones de color) -->
                <div v-if="isColorForm" class="rounded-lg border border-fb-border bg-[#F8FAFC] p-4">
                    <p class="mb-3 text-sm font-semibold">Mezcla de colores</p>
                    <p class="mb-3 text-xs text-fb-muted">
                        Elige tres colores base y Gofio repartirá tonos derivados en cada sector
                        (topbar, paneles, botones, bordes). Puedes ajustar cada sector después.
                    </p>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">Marca (botones, enlaces)</label>
                            <div class="flex items-center gap-2">
                                <input v-model="mixBases.brand" type="color" class="h-9 w-9 cursor-pointer rounded border border-fb-border p-0.5" />
                                <input v-model="mixBases.brand" type="text" class="gofio-input flex-1 text-xs" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">Fondo general</label>
                            <div class="flex items-center gap-2">
                                <input v-model="mixBases.background" type="color" class="h-9 w-9 cursor-pointer rounded border border-fb-border p-0.5" />
                                <input v-model="mixBases.background" type="text" class="gofio-input flex-1 text-xs" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-fb-muted">Acento (detalles, topbar)</label>
                            <div class="flex items-center gap-2">
                                <input v-model="mixBases.accent" type="color" class="h-9 w-9 cursor-pointer rounded border border-fb-border p-0.5" />
                                <input v-model="mixBases.accent" type="text" class="gofio-input flex-1 text-xs" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="mb-1 flex items-center justify-between text-xs font-medium text-fb-muted">
                            <span>Intensidad de mezcla marca ↔ acento</span>
                            <span>{{ Math.round(blendRatio * 100) }}%</span>
                        </label>
                        <input v-model.number="blendRatio" type="range" min="0" max="1" step="0.05" class="w-full" />
                    </div>

                    <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                        <button
                            type="button"
                            class="gofio-btn-primary inline-flex w-full items-center justify-center gap-2 sm:w-auto"
                            @click="applyColorMix"
                        >
                            <FaIcon icon="fa-solid fa-palette" />
                            Aplicar colores
                        </button>
                        <button
                            type="button"
                            class="gofio-btn-secondary inline-flex w-full items-center justify-center gap-2 sm:w-auto"
                            @click="applyColorsToInterface"
                        >
                            <FaIcon icon="fa-solid fa-eye" />
                            Vista previa en interfaz
                        </button>
                    </div>

                    <p v-if="colorsFeedback" class="mt-3 rounded border border-green-200 bg-green-50 px-3 py-2 text-xs text-green-800">
                        {{ colorsFeedback }}
                    </p>
                </div>

                <!-- Vista previa por sectores -->
                <div v-if="isColorForm" class="rounded-lg border border-fb-border p-4">
                    <p class="mb-2 text-sm font-semibold">Vista previa por sectores</p>
                    <div
                        class="overflow-hidden rounded-lg border"
                        :style="{ borderColor: previewColors.border, background: previewColors.background }"
                    >
                        <div
                            class="flex h-6 items-center justify-between px-2 text-[10px] font-bold text-white"
                            :style="{ background: `linear-gradient(to right, ${previewColors.topbarFrom}, ${previewColors.topbarTo})` }"
                        >
                            <span>Barra superior</span>
                            <span :style="{ color: previewColors.accent }">Acento</span>
                        </div>
                        <div class="flex min-h-[5rem]">
                            <div class="w-[28%] p-2" :style="{ background: previewColors.panel }">
                                <p class="text-[9px] font-semibold" :style="{ color: previewColors.text }">Panel lateral</p>
                            </div>
                            <div class="flex flex-1 flex-col gap-2 p-2">
                                <div
                                    class="rounded border p-2"
                                    :style="{ background: previewColors.surface, borderColor: previewColors.border }"
                                >
                                    <p class="text-[9px] font-semibold" :style="{ color: previewColors.text }">Tarjeta / publicación</p>
                                </div>
                                <div
                                    class="inline-flex self-start rounded px-2 py-1 text-[9px] font-bold text-white"
                                    :style="{ background: `linear-gradient(180deg, ${previewColors.brand}, ${previewColors.brandHover})` }"
                                >
                                    Botón primario
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colores por sector -->
                <div v-if="isColorForm">
                    <p class="mb-3 text-sm font-semibold">Colores por sector</p>
                    <p class="mb-4 text-xs text-fb-muted">
                        Cada variable indica qué parte de la interfaz recibe ese color. Ajusta manualmente
                        cualquier sector tras aplicar la mezcla.
                    </p>

                    <div v-for="(groupKeys, groupName) in activeColorGroups" :key="groupName" class="mb-5">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-fb-muted">{{ groupName }}</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="key in groupKeys"
                                :key="key"
                                class="rounded border border-fb-border bg-white p-2"
                            >
                                <label class="mb-0.5 block text-xs font-medium capitalize">{{ labelFor(key) }}</label>
                                <p class="mb-2 text-[11px] leading-snug text-fb-muted">{{ sectorFor(key) }}</p>
                                <div class="flex items-center gap-1">
                                    <input
                                        v-model="form.variables[key]"
                                        type="color"
                                        class="h-8 w-8 shrink-0 cursor-pointer rounded border border-fb-border p-0.5"
                                        :title="form.variables[key]"
                                    />
                                    <input
                                        v-model="form.variables[key]"
                                        type="text"
                                        class="gofio-input min-w-0 flex-1 text-xs"
                                        :placeholder="defaults[key] ?? ''"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Variables de carpeta (solo lectura) -->
                <div v-else-if="isFolderEdit">
                    <p class="mb-3 text-sm font-semibold text-fb-muted">Paleta definida en la carpeta (solo lectura)</p>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="key in variableKeys.filter((k) => k.startsWith('--color') || k.startsWith('--bg'))" :key="key">
                            <label class="mb-1 block text-xs capitalize text-fb-muted">{{ labelFor(key) }}</label>
                            <div class="flex items-center gap-1">
                                <span
                                    class="inline-block h-8 w-8 rounded border border-fb-border"
                                    :style="{ background: form.variables[key] ?? defaults[key] }"
                                />
                                <input
                                    :value="form.variables[key] ?? defaults[key]"
                                    type="text"
                                    class="gofio-input flex-1 text-xs"
                                    disabled
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="gofio-btn-primary" :disabled="saving">
                        {{ saving ? 'Guardando...' : 'Guardar' }}
                    </button>
                    <button
                        v-if="isColorForm"
                        type="button"
                        class="gofio-btn-secondary"
                        @click="applyColorsToInterface"
                    >
                        Aplicar colores
                    </button>
                    <button type="button" class="gofio-btn-secondary" @click="cancel">Cancelar</button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
