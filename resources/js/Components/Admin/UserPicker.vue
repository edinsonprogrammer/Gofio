<script setup>
/**
 * Selector autocompletable de usuarios para formularios administrativos.
 */

import { ref, watch } from 'vue';
import FaIcon from '@/Components/UI/FaIcon.vue';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Escribe un @nick o nombre de usuario…',
    },
});

const emit = defineEmits(['update:modelValue']);

const query = ref('');
const results = ref([]);
const loading = ref(false);
const open = ref(false);
const selectedUser = ref(null);
let debounceTimer = null;
let requestSeq = 0;


/** Limpia el usuario seleccionado en el picker. */
const clearSelection = () => {
    selectedUser.value = null;
    query.value = '';
    results.value = [];
    emit('update:modelValue', '');
};


/** Filtra resultados según el texto de búsqueda ingresado. */
const search = async (q) => {
    const seq = ++requestSeq;
    loading.value = true;

    try {
        const { data } = await window.axios.get('/admin/usuarios/buscar', { params: { q } });
        if (seq === requestSeq) {
            results.value = data.data ?? [];
        }
    } catch {
        if (seq === requestSeq) {
            results.value = [];
        }
    } finally {
        if (seq === requestSeq) {
            loading.value = false;
        }
    }
};

watch(query, (value) => {
    if (selectedUser.value) return;

    clearTimeout(debounceTimer);

    if (!value || value.trim().length < 1) {
        results.value = [];
        return;
    }

    debounceTimer = setTimeout(() => search(value.trim()), 250);
});


/** Selecciona el elemento activo y actualiza el estado de la interfaz. */
const select = (user) => {
    selectedUser.value = user;
    query.value = '';
    results.value = [];
    open.value = false;
    emit('update:modelValue', user.id);
};


/** Oculta el dropdown cuando el campo pierde el foco. */
const onBlur = () => {
    setTimeout(() => { open.value = false; }, 150);
};

watch(() => props.modelValue, (value) => {
    if (!value) {
        selectedUser.value = null;
    }
});
</script>

<template>
    <!-- Autocompletado de usuarios para formularios administrativos -->

    <div class="relative">
        <div v-if="selectedUser" class="gofio-input flex items-center justify-between gap-2 text-xs">
            <span>
                <strong>{{ selectedUser.username }}</strong>
                <span v-if="selectedUser.nick" class="ml-1 text-fb-muted">@{{ selectedUser.nick }}</span>
            </span>
            <button type="button" class="text-fb-muted hover:text-red-600" title="Cambiar usuario" @click="clearSelection">
                <FaIcon icon="fa-solid fa-xmark" />
            </button>
        </div>

        <div v-else class="flex items-center gofio-input gap-1.5">
            <FaIcon icon="fa-solid fa-magnifying-glass" class="text-[0.85em] text-fb-muted" />
            <input
                v-model="query"
                type="text"
                class="w-full border-0 bg-transparent p-0 text-xs outline-none focus:ring-0"
                :placeholder="placeholder"
                autocomplete="off"
                @focus="open = true"
                @blur="onBlur"
            />
            <FaIcon v-if="loading" icon="fa-solid fa-spinner" class="animate-spin text-[0.85em] text-fb-muted" />
        </div>

        <ul
            v-if="open && !selectedUser && (results.length || (query.trim().length >= 1 && !loading))"
            class="absolute z-20 mt-1 max-h-56 w-full min-w-[200px] overflow-y-auto rounded border border-fb-border bg-white text-xs shadow-lg"
        >
            <li v-if="!results.length" class="px-3 py-2 text-fb-muted">Sin coincidencias.</li>
            <li
                v-for="user in results"
                :key="user.id"
                class="cursor-pointer px-3 py-2 gofio-hover-panel"
                @mousedown.prevent="select(user)"
            >
                <strong>{{ user.username }}</strong>
                <span v-if="user.nick" class="ml-1 text-fb-muted">@{{ user.nick }}</span>
                <span class="ml-1 text-fb-muted">· {{ user.karma }} karma</span>
            </li>
        </ul>
    </div>
</template>
