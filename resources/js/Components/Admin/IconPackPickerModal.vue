<script setup>
/**
 * Modal de exploración y selección de iconos de paquetes subidos.
 */

import { computed, onUnmounted, ref, watch } from 'vue';

import FaIcon from '@/Components/UI/FaIcon.vue';



const props = defineProps({

    open: {

        type: Boolean,

        default: false,

    },

    modelValue: {

        type: String,

        default: '',

    },

});



const emit = defineEmits(['update:open', 'select']);



const PAGE_SIZE = 96;



const loading = ref(false);

const error = ref('');

const query = ref('');

const packs = ref([]);

const activePackSlug = ref('');

const activeGroupKey = ref('');

const visibleCount = ref(PAGE_SIZE);



const activePack = computed(() => packs.value.find((pack) => pack.slug === activePackSlug.value) ?? null);



const activeGroup = computed(() => {

    const groups = activePack.value?.groups ?? [];



    if (! groups.length) {

        return null;

    }



    return groups.find((group) => group.key === activeGroupKey.value) ?? groups[0];

});



const iconsInActiveGroup = computed(() => {

    const icons = activePack.value?.icons ?? [];

    const groupKey = activeGroup.value?.key;



    if (! groupKey) {

        return icons;

    }



    return icons.filter((icon) => icon.group === groupKey);

});



const filteredIcons = computed(() => {

    const icons = iconsInActiveGroup.value;

    const term = query.value.trim().toLowerCase();



    if (! term) {

        return icons;

    }



    return icons.filter((icon) => {
        const label = (icon.label ?? '').toLowerCase();
        const value = (icon.value ?? '').toLowerCase();
        const relative = (icon.relative ?? '').toLowerCase();

        return label.includes(term) || value.includes(term) || relative.includes(term);
    });

});



const visibleIcons = computed(() => filteredIcons.value.slice(0, visibleCount.value));



const canLoadMore = computed(() => visibleCount.value < filteredIcons.value.length);




/** Bloquea el scroll del documento mientras el modal está abierto. */
const lockBodyScroll = (locked) => {

    document.body.style.overflow = locked ? 'hidden' : '';

};




/** Cierra el modal al pulsar la tecla Escape. */
const onEscape = (event) => {

    if (event.key === 'Escape') {

        emit('update:open', false);

    }

};




/** Selecciona el grupo de iconos por defecto al abrir el modal. */
const pickDefaultGroup = (pack) => {

    if (! pack?.groups?.length) {

        activeGroupKey.value = '';

        return;

    }



    const preferred = pack.groups.find((group) => group.key === 'imagenes')

        ?? pack.groups.find((group) => group.key === 'personalizados')

        ?? pack.groups[0];



    activeGroupKey.value = preferred.key;

};




/** Carga la biblioteca de iconos del paquete desde el servidor. */
const loadLibrary = async () => {

    loading.value = true;

    error.value = '';



    try {

        const { data } = await window.axios.get('/admin/iconos/biblioteca', { params: { fresh: 1 } });

        packs.value = data.data?.packs ?? [];



        if (! activePackSlug.value && packs.value.length) {

            activePackSlug.value = packs.value[0].slug;

        }



        const pack = packs.value.find((item) => item.slug === activePackSlug.value) ?? packs.value[0];



        if (pack) {

            activePackSlug.value = pack.slug;

            pickDefaultGroup(pack);

        }

    } catch {

        error.value = 'No se pudo cargar la biblioteca de iconos.';

        packs.value = [];

    } finally {

        loading.value = false;

        visibleCount.value = PAGE_SIZE;

    }

};



watch(

    () => props.open,

    (visible) => {

        if (visible) {

            query.value = '';

            visibleCount.value = PAGE_SIZE;

            lockBodyScroll(true);

            document.addEventListener('keydown', onEscape);

            loadLibrary();

            return;

        }



        lockBodyScroll(false);

        document.removeEventListener('keydown', onEscape);

    },

);



watch(activePackSlug, (slug) => {

    const pack = packs.value.find((item) => item.slug === slug);



    if (pack) {

        pickDefaultGroup(pack);

    }



    visibleCount.value = PAGE_SIZE;

    query.value = '';

});



watch(activeGroupKey, () => {

    visibleCount.value = PAGE_SIZE;

});



watch(query, () => {

    visibleCount.value = PAGE_SIZE;

});



onUnmounted(() => {

    lockBodyScroll(false);

    document.removeEventListener('keydown', onEscape);

});




/** Confirma el icono elegido y lo emite al formulario padre. */
const selectIcon = (icon) => {

    emit('select', icon.value);

    emit('update:open', false);

};




/** Cierra el panel o modal y restablece el estado temporal. */
const close = () => {

    emit('update:open', false);

};




/** Solicita la siguiente página de iconos para scroll infinito. */
const loadMore = () => {

    visibleCount.value += PAGE_SIZE;

};
</script>



<template>
    <!-- Modal de exploración y selección de iconos de paquetes -->


    <Teleport to="body">

        <div

            v-if="open"

            class="icon-pack-picker-overlay"

            role="presentation"

            @click.self="close"

        >

            <div

                class="icon-pack-picker gofio-box"

                role="dialog"

                aria-modal="true"

                aria-labelledby="icon-pack-picker-title"

            >

    <!-- Cabecera de la sección -->
                <header class="icon-pack-picker__header">

                    <div class="icon-pack-picker__heading">

                        <h2 id="icon-pack-picker-title" class="icon-pack-picker__title">

                            Elegir icono

                        </h2>

                        <p class="icon-pack-picker__subtitle">

                            PNG, JPG, SVG y más en <code>icon-packs/gemas/imagenes/</code>

                        </p>

                    </div>

                    <button type="button" class="icon-pack-picker__close" aria-label="Cerrar" @click="close">

                        <FaIcon icon="fa-solid fa-xmark" />

                    </button>

                </header>



                <div class="icon-pack-picker__toolbar">

                    <div v-if="packs.length" class="icon-pack-picker__tabs">

                        <button

                            v-for="pack in packs"

                            :key="pack.slug"

                            type="button"

                            class="icon-pack-picker__tab"

                            :class="{ 'icon-pack-picker__tab--active': pack.slug === activePackSlug }"

                            @click="activePackSlug = pack.slug"

                        >

                            {{ pack.name }}

                        </button>

                    </div>



                    <div v-if="activePack?.groups?.length" class="icon-pack-picker__subtabs">

                        <button

                            v-for="group in activePack.groups"

                            :key="group.key"

                            type="button"

                            class="icon-pack-picker__subtab"

                            :class="{ 'icon-pack-picker__subtab--active': group.key === activeGroupKey }"

                            @click="activeGroupKey = group.key"

                        >

                            {{ group.label }}

                            <span class="icon-pack-picker__subtab-count">{{ group.count }}</span>

                        </button>

                    </div>



                    <input

                        v-model="query"

                        type="search"

                        class="gofio-input icon-pack-picker__search"

                        placeholder="Buscar por nombre o archivo…"

                        autocomplete="off"

                    />

                </div>



                <div class="icon-pack-picker__body">

                    <p v-if="loading" class="icon-pack-picker__empty">Cargando iconos…</p>

                    <p v-else-if="error" class="icon-pack-picker__empty icon-pack-picker__empty--error">{{ error }}</p>

                    <p v-else-if="!packs.length" class="icon-pack-picker__empty">

                        No hay paquetes en <code>icon-packs/</code>. Crea uno con <code>icon-pack.json</code> e imágenes.

                    </p>

                    <p v-else-if="!filteredIcons.length" class="icon-pack-picker__empty">

                        No hay iconos que coincidan con tu búsqueda.

                    </p>

                    <template v-else>

                        <p class="icon-pack-picker__meta">

                            Mostrando {{ visibleIcons.length }} de {{ filteredIcons.length }} en

                            <strong>{{ activeGroup?.label ?? 'todos' }}</strong>

                        </p>

                        <div class="icon-pack-picker__grid">

                            <button

                                v-for="icon in visibleIcons"

                                :key="`${icon.type}:${icon.value}`"

                                type="button"

                                class="icon-pack-picker__item"

                                :class="{ 'icon-pack-picker__item--selected': modelValue === icon.value }"

                                :title="icon.value"

                                @click="selectIcon(icon)"

                            >

                                <span class="icon-pack-picker__item-preview">

                                    <img

                                        v-if="icon.type === 'image'"

                                        :src="icon.url"

                                        alt=""

                                        class="icon-pack-picker__item-img"

                                        loading="lazy"

                                    />

                                    <FaIcon v-else :icon="icon.value" class="icon-pack-picker__item-fa" />

                                </span>

                                <span class="icon-pack-picker__item-label">{{ icon.label }}</span>

                            </button>

                        </div>

                        <div v-if="canLoadMore" class="icon-pack-picker__more-wrap">

                            <button type="button" class="gofio-btn-secondary text-xs" @click="loadMore">

                                Cargar más ({{ filteredIcons.length - visibleIcons.length }} restantes)

                            </button>

                        </div>

                    </template>

                </div>

            </div>

        </div>

    </Teleport>

</template>


