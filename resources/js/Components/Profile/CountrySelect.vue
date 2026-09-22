<script setup>
/**
 * Selector autocompletable de país con bandera emoji para el perfil.
 */

import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { COUNTRIES, countryFlag, filterCountries, findCountryByCode } from '@/utils/countries';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    countryCode: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'update:countryCode']);

const rootRef = ref(null);
const query = ref(props.modelValue ?? '');
const open = ref(false);

const selectedFlag = computed(() => countryFlag(props.countryCode));
const results = computed(() => filterCountries(query.value));


/** Sincroniza país y código ISO cuando cambian las props del perfil. */
const syncFromProps = () => {
    query.value = props.modelValue ?? '';
};

watch(() => props.modelValue, syncFromProps);


/** Asigna país y código ISO al seleccionar una opción. */
const selectCountry = (country) => {
    query.value = country.name;
    emit('update:modelValue', country.name);
    emit('update:countryCode', country.code);
    open.value = false;
};


/** Filtra países según el texto tecleado en el autocompletado. */
const onInput = (event) => {
    query.value = event.target.value;
    emit('update:modelValue', query.value);
    open.value = true;

    const exact = COUNTRIES.find((c) => c.name.toLowerCase() === query.value.trim().toLowerCase());
    if (exact) {
        emit('update:countryCode', exact.code);
    } else if (!query.value.trim()) {
        emit('update:countryCode', '');
    }
};


/** Muestra sugerencias al enfocar el campo de país. */
const onFocus = () => {
    open.value = true;
};


/** Cierra paneles al hacer clic fuera del componente. */
const onDocumentClick = (event) => {
    if (!rootRef.value?.contains(event.target)) {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', onDocumentClick);
    if (props.countryCode && !props.modelValue) {
        const found = findCountryByCode(props.countryCode);
        if (found) {
            query.value = found.name;
            emit('update:modelValue', found.name);
        }
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocumentClick);
});
</script>

<template>
    <!-- Selector de país con autocompletado y bandera -->

    <div ref="rootRef" class="country-select">
        <div class="country-select__input-wrap gofio-input">
            <span v-if="selectedFlag" class="country-select__flag">{{ selectedFlag }}</span>
            <input
                :value="query"
                type="text"
                class="country-select__input"
                placeholder="Escribe tu país..."
                autocomplete="off"
                @input="onInput"
                @focus="onFocus"
            />
        </div>

        <ul v-if="open && results.length" class="country-select__dropdown">
            <li
                v-for="country in results"
                :key="country.code"
                class="country-select__option"
                @mousedown.prevent="selectCountry(country)"
            >
                <span class="country-select__flag">{{ countryFlag(country.code) }}</span>
                <span>{{ country.name }}</span>
            </li>
        </ul>

        <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
    </div>
</template>

<style scoped>
.country-select {
    position: relative;
    max-width: 20rem;
}

.country-select__input-wrap {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.country-select__flag {
    font-size: 1.25rem;
    line-height: 1;
}

.country-select__input {
    width: 100%;
    border: 0;
    background: transparent;
    outline: none;
    padding: 0;
}

.country-select__dropdown {
    position: absolute;
    z-index: 30;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    max-height: 14rem;
    overflow-y: auto;
    border: 1px solid var(--color-border);
    border-radius: 0.5rem;
    background: white;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
}

.country-select__option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    cursor: pointer;
}

.country-select__option:hover {
    background: var(--color-panel);
}
</style>
