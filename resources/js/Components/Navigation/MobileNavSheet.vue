<script setup>

/**

 * Panel flotante estilo macOS (vidrio + blur) que se despliega hacia arriba desde la barra móvil.

 */



import { onBeforeUnmount, watch } from 'vue';

import FaIcon from '@/Components/UI/FaIcon.vue';



const props = defineProps({

    open: {

        type: Boolean,

        default: false,

    },

    title: {

        type: String,

        default: '',

    },

    icon: {

        type: String,

        default: 'fa-solid fa-circle-info',

    },

});



const emit = defineEmits(['close']);



/** Bloquea el scroll del documento mientras el panel está abierto. */

const syncBodyLock = (isOpen) => {

    document.documentElement.classList.toggle('mobile-nav-sheet-open', isOpen);

};



/** Cierra el panel al pulsar la tecla Escape. */

const onKeydown = (event) => {

    if (event.key === 'Escape' && props.open) {

        emit('close');

    }

};



watch(() => props.open, (isOpen) => {

    syncBodyLock(isOpen);



    if (isOpen) {

        window.addEventListener('keydown', onKeydown);

        return;

    }



    window.removeEventListener('keydown', onKeydown);

}, { immediate: true });



onBeforeUnmount(() => {

    document.documentElement.classList.remove('mobile-nav-sheet-open');

    window.removeEventListener('keydown', onKeydown);

});

</script>



<template>

    <Teleport to="body">

        <Transition name="mobile-nav-sheet">

            <div

                v-if="open"

                class="mobile-nav-sheet"

                role="presentation"

            >

                <!-- Capa oscurecida con blur suave -->

                <button

                    type="button"

                    class="mobile-nav-sheet__backdrop"

                    aria-label="Cerrar menú"

                    @click="emit('close')"

                />



                <!-- Globo de diálogo con efecto vidrio macOS -->

                <div

                    class="mobile-nav-sheet__panel"

                    role="dialog"

                    :aria-label="title"

                    aria-modal="true"

                >

                    <div class="mobile-nav-sheet__handle" aria-hidden="true" />



                    <header class="mobile-nav-sheet__header">

                        <span class="mobile-nav-sheet__header-icon">

                            <FaIcon :icon="icon" />

                        </span>

                        <h2 class="mobile-nav-sheet__title">{{ title }}</h2>

                        <button

                            type="button"

                            class="mobile-nav-sheet__close"

                            aria-label="Cerrar"

                            @click="emit('close')"

                        >

                            <FaIcon icon="fa-solid fa-xmark" />

                        </button>

                    </header>



                    <div class="mobile-nav-sheet__body">

                        <slot />

                    </div>

                </div>

            </div>

        </Transition>

    </Teleport>

</template>


