<script setup>

/**

 * Barra de pestañas inferior en móvil (estilo Facebook) con acceso a categorías,

 * estadísticas y cuenta mediante paneles tipo macOS.

 */



import { Link, usePage } from '@inertiajs/vue3';

import { computed, ref, watch } from 'vue';

import FaIcon from '@/Components/UI/FaIcon.vue';

import RankBadge from '@/Components/User/RankBadge.vue';

import MobileNavSheet from '@/Components/Navigation/MobileNavSheet.vue';



const props = defineProps({

    categories: {

        type: Array,

        default: () => [],

    },

    formattedBalance: {

        type: String,

        default: '0,00',

    },

    isHome: {

        type: Boolean,

        default: false,

    },

    selectedCategorySlug: {

        type: String,

        default: null,

    },

    profileUrl: {

        type: String,

        default: '#',

    },

});



const emit = defineEmits(['select-category']);



const page = usePage();

const user = computed(() => page.props.auth.user);



/** Panel activo: categorías, estadísticas o cuenta. */

const activeSheet = ref(null);



/** Cierra cualquier panel abierto. */

const closeSheet = () => {

    activeSheet.value = null;

};



/** Alterna un panel; si ya está abierto, lo cierra. */

const toggleSheet = (sheetId) => {

    activeSheet.value = activeSheet.value === sheetId ? null : sheetId;

};



/** Selecciona categoría y cierra el panel. */

const pickCategory = (slug) => {

    emit('select-category', slug);

    closeSheet();

};



/** Cierra paneles al cambiar de ruta. */

watch(() => page.url, closeSheet);

</script>



<template>

    <!-- Barra inferior fija: visible solo en pantallas pequeñas -->

    <nav class="mobile-bottom-nav lg:hidden" aria-label="Navegación principal móvil">

        <Link

            href="/"

            class="mobile-bottom-nav__item"

            :class="{ 'mobile-bottom-nav__item--active': isHome && !selectedCategorySlug }"

        >

            <FaIcon icon="fa-solid fa-house" class="mobile-bottom-nav__icon" />

            <span class="mobile-bottom-nav__label">Inicio</span>

        </Link>



        <button

            type="button"

            class="mobile-bottom-nav__item"

            :class="{ 'mobile-bottom-nav__item--active': activeSheet === 'categories' }"

            aria-label="Categorías"

            @click="toggleSheet('categories')"

        >

            <FaIcon icon="fa-solid fa-folder-open" class="mobile-bottom-nav__icon" />

            <span class="mobile-bottom-nav__label">Categorías</span>

        </button>



        <button

            type="button"

            class="mobile-bottom-nav__item"

            :class="{ 'mobile-bottom-nav__item--active': activeSheet === 'stats' }"

            aria-label="Estadísticas"

            @click="toggleSheet('stats')"

        >

            <FaIcon icon="fa-solid fa-chart-simple" class="mobile-bottom-nav__icon" />

            <span class="mobile-bottom-nav__label">Estadísticas</span>

        </button>



        <button

            type="button"

            class="mobile-bottom-nav__item"

            :class="{ 'mobile-bottom-nav__item--active': activeSheet === 'account' }"

            aria-label="Cuenta"

            @click="toggleSheet('account')"

        >

            <FaIcon icon="fa-solid fa-user-gear" class="mobile-bottom-nav__icon" />

            <span class="mobile-bottom-nav__label">Cuenta</span>

        </button>

    </nav>



    <!-- Panel: categorías del feed -->

    <MobileNavSheet

        :open="activeSheet === 'categories'"

        title="Categorías"

        icon="fa-solid fa-folder-open"

        @close="closeSheet"

    >

        <ul class="mobile-nav-menu">

            <li>

                <button

                    type="button"

                    class="mobile-nav-menu__item"

                    :class="{ 'mobile-nav-menu__item--active': isHome && !selectedCategorySlug }"

                    @click="pickCategory(null)"

                >

                    <FaIcon icon="fa-solid fa-house" class="mobile-nav-menu__icon" />

                    <span>Todas las publicaciones</span>

                </button>

            </li>

            <li v-for="cat in categories" :key="cat.id">

                <button

                    type="button"

                    class="mobile-nav-menu__item"

                    :class="{ 'mobile-nav-menu__item--active': selectedCategorySlug === cat.slug }"

                    @click="pickCategory(cat.slug)"

                >

                    <FaIcon :icon="cat.icon" class="mobile-nav-menu__icon" />

                    <span>{{ cat.name }}</span>

                </button>

            </li>

        </ul>

    </MobileNavSheet>



    <!-- Panel: karma y monedas del usuario -->

    <MobileNavSheet

        :open="activeSheet === 'stats'"

        title="Estadísticas"

        icon="fa-solid fa-chart-simple"

        @close="closeSheet"

    >

        <div class="mobile-nav-stats">

            <Link :href="profileUrl" class="mobile-nav-stats__profile" @click="closeSheet">

                <div class="mobile-nav-stats__avatar">

                    {{ user?.username?.charAt(0).toUpperCase() }}

                </div>

                <div class="min-w-0">

                    <p class="mobile-nav-stats__username">{{ user?.username }}</p>

                    <RankBadge v-if="user?.rango" :rango="user.rango" />

                </div>

            </Link>



            <div class="mobile-nav-stats__grid">

                <div class="mobile-nav-stats__card">

                    <span class="mobile-nav-stats__card-icon mobile-nav-stats__card-icon--karma">

                        <FaIcon icon="fa-solid fa-bolt" />

                    </span>

                    <div>

                        <p class="mobile-nav-stats__card-label">Karma</p>

                        <p class="mobile-nav-stats__card-value">{{ user?.karma ?? 0 }}</p>

                    </div>

                </div>

                <div class="mobile-nav-stats__card">

                    <span class="mobile-nav-stats__card-icon mobile-nav-stats__card-icon--coins">

                        <FaIcon icon="fa-solid fa-coins" />

                    </span>

                    <div>

                        <p class="mobile-nav-stats__card-label">Monedas</p>

                        <p class="mobile-nav-stats__card-value">{{ formattedBalance }}</p>

                    </div>

                </div>

            </div>

        </div>

    </MobileNavSheet>



    <!-- Panel: opciones de cuenta -->

    <MobileNavSheet

        :open="activeSheet === 'account'"

        title="Cuenta"

        icon="fa-solid fa-user-gear"

        @close="closeSheet"

    >

        <ul class="mobile-nav-menu">

            <li>

                <Link href="/configuracion/perfil" class="mobile-nav-menu__item mobile-nav-menu__link" @click="closeSheet">

                    <FaIcon icon="fa-solid fa-at" class="mobile-nav-menu__icon" />

                    <span>Perfil / @nick</span>

                </Link>

            </li>

            <li v-if="user?.can_customize_appearance">

                <Link href="/configuracion/apariencia" class="mobile-nav-menu__item mobile-nav-menu__link" @click="closeSheet">

                    <FaIcon icon="fa-solid fa-palette" class="mobile-nav-menu__icon" />

                    <span>Apariencia</span>

                </Link>

            </li>

            <li>

                <Link href="/configuracion/verificacion" class="mobile-nav-menu__item mobile-nav-menu__link" @click="closeSheet">

                    <FaIcon icon="fa-solid fa-circle-check" class="mobile-nav-menu__icon" />

                    <span>Verificación</span>

                </Link>

            </li>

            <li>

                <Link href="/configuracion/creator-plus" class="mobile-nav-menu__item mobile-nav-menu__link" @click="closeSheet">

                    <FaIcon icon="fa-solid fa-crown" class="mobile-nav-menu__icon mobile-nav-menu__icon--gold" />

                    <span>Creator Plus</span>

                </Link>

            </li>

            <li v-if="user?.is_admin">

                <Link href="/admin" class="mobile-nav-menu__item mobile-nav-menu__link mobile-nav-menu__link--admin" @click="closeSheet">

                    <FaIcon icon="fa-solid fa-gauge-high" class="mobile-nav-menu__icon" />

                    <span>Panel admin</span>

                </Link>

            </li>

            <li v-else-if="user?.is_staff">

                <Link href="/admin/moderacion" class="mobile-nav-menu__item mobile-nav-menu__link mobile-nav-menu__link--staff" @click="closeSheet">

                    <FaIcon icon="fa-solid fa-shield-halved" class="mobile-nav-menu__icon" />

                    <span>Moderación</span>

                </Link>

            </li>

        </ul>

    </MobileNavSheet>

</template>


