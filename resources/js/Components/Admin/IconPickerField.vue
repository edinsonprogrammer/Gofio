<script setup>
/**
 * Campo de formulario para elegir icono Font Awesome o de paquete.
 */

import { computed, ref } from 'vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import IconPackPickerModal from '@/Components/Admin/IconPackPickerModal.vue';
import { iconLabel } from '@/utils/gofioIcon';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    defaultIcon: {
        type: String,
        default: 'fa-solid fa-star',
    },
    previewColor: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: 'Icono',
    },
    required: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const pickerOpen = ref(false);

const displayIcon = computed(() => props.modelValue || props.defaultIcon);
const selectedLabel = computed(() => iconLabel(props.modelValue) || 'Sin seleccionar');

const previewStyle = computed(() => (props.previewColor ? { color: props.previewColor } : undefined));


/** Despliega el popover con las reacciones disponibles. */
const openPicker = () => {
    pickerOpen.value = true;
};


/** Procesa la selección del usuario y emite el resultado al padre. */
const onSelect = (value) => {
    emit('update:modelValue', value);
};


/** Quita el icono seleccionado y restablece el campo. */
const clearIcon = () => {
    emit('update:modelValue', '');
};
</script>

<template>
    <!-- Campo de formulario con vista previa del icono elegido -->

    <div class="icon-picker-field">
        <label v-if="label" class="gofio-field-label">{{ label }}</label>
        <div class="icon-picker-field__row">
            <button
                type="button"
                class="icon-picker-field__preview"
                :style="previewStyle"
                :title="modelValue || 'Vista previa'"
                @click="openPicker"
            >
                <FaIcon :icon="displayIcon" />
            </button>
            <div class="icon-picker-field__meta">
                <button type="button" class="gofio-btn-secondary text-xs" @click="openPicker">
                    Elegir icono
                </button>
                <p class="icon-picker-field__name">{{ selectedLabel }}</p>
            </div>
            <button
                v-if="modelValue"
                type="button"
                class="icon-picker-field__clear text-xs text-fb-link hover:underline"
                @click="clearIcon"
            >
                Quitar
            </button>
        </div>
        <input
            type="hidden"
            :value="modelValue"
            :required="required"
        />
        <IconPackPickerModal
            :open="pickerOpen"
            :model-value="modelValue"
            @update:open="pickerOpen = $event"
            @select="onSelect"
        />
    </div>
</template>

<style scoped>
.icon-picker-field__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

.icon-picker-field__preview {
    display: inline-flex;
    height: 2rem;
    width: 2rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    border: 1px solid var(--color-border);
    background: #fff;
    font-size: 1rem;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.icon-picker-field__preview:hover {
    border-color: color-mix(in srgb, var(--color-accent, var(--color-brand)) 45%, var(--color-border));
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-accent, var(--color-brand)) 18%, transparent);
}

.icon-picker-field__meta {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 0.15rem;
}

.icon-picker-field__name {
    max-width: 12rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.65rem;
    color: var(--color-muted);
}

.icon-picker-field__clear {
    margin-left: auto;
}
</style>
