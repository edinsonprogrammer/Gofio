<script setup>
/**
 * Página de inicio de sesión con dos modos: acceso normal de usuarios
 * y pantalla de mantenimiento con login exclusivo para staff.
 */

import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import SiteCopyrightFooter from '@/Components/UI/SiteCopyrightFooter.vue';
import SiteBrand from '@/Components/UI/SiteBrand.vue';
import SeoHead from '@/Components/SEO/SeoHead.vue';
import { SITE_COPYRIGHT } from '@/constants/siteCopyright';
import MaintenanceMascot from '@/Components/Auth/MaintenanceMascot.vue';

const props = defineProps({
    maintenance: {
        type: Object,
        default: () => ({ active: false, message: '', staff_only: true }),
    },
    seo: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const flashError = computed(() => page.props.flash?.error ?? '');

/** Formulario Inertia con credenciales y opción de recordar sesión. */
const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const isMaintenance = computed(() => props.maintenance?.active === true);
</script>

<template>
    <SeoHead :seo="seo" />
    <!-- Vista de mantenimiento: mascota animada y login restringido a staff -->
    <div v-if="isMaintenance" class="maintenance-page min-h-screen overflow-x-hidden">
        <div class="maintenance-page__bg"></div>
        <div class="maintenance-page__grid"></div>

        <div class="maintenance-page__content mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10">
            <SiteBrand href="/login" variant="maintenance" />

            <MaintenanceMascot :message="maintenance.message" />

            <div class="maintenance-login-card mt-6">
                <div class="maintenance-login-card__header">
                    <FaIcon icon="fa-solid fa-shield-halved" />
                    <div>
                        <p class="text-sm font-bold">Acceso exclusivo staff</p>
                        <p class="text-xs opacity-90">Inicia sesión para continuar trabajando en Gofio</p>
                    </div>
                </div>

                <form class="space-y-3 p-4" @submit.prevent="form.post('/login')">
                    <div>
                        <label class="maintenance-field-label">Usuario o correo</label>
                        <input
                            v-model="form.login"
                            type="text"
                            class="maintenance-input"
                            autocomplete="username"
                            required
                        />
                    </div>
                    <div>
                        <label class="maintenance-field-label">Contraseña</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="maintenance-input"
                            autocomplete="current-password"
                            required
                        />
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-600">
                        <input v-model="form.remember" type="checkbox" class="rounded" />
                        Recordarme en este equipo
                    </label>

                    <p v-if="form.errors.login" class="text-xs text-red-600">{{ form.errors.login }}</p>
                    <p v-else-if="flashError" class="text-xs text-red-600">{{ flashError }}</p>

                    <button type="submit" class="maintenance-submit-btn" :disabled="form.processing">
                        {{ form.processing ? 'Entrando...' : 'Entrar como staff' }}
                    </button>
                </form>
            </div>

            <p class="maintenance-page__footer mt-6 text-center text-xs text-slate-500">
                {{ SITE_COPYRIGHT }}
            </p>
        </div>
    </div>

    <!-- Vista normal: login en header y landing de registro -->
    <div v-else class="login-page min-h-screen overflow-x-hidden bg-backgroundPrincipal">
        <header class="taringa-topbar login-header px-4 py-3 text-white">
            <div class="login-header-inner mx-auto max-w-5xl">
                <SiteBrand href="/" variant="login-header" />

                <form class="login-header-form" @submit.prevent="form.post('/login')">
                    <div class="login-header-fields">
                        <div class="login-header-field">
                            <label class="login-header-label">Correo o usuario</label>
                            <input v-model="form.login" type="text" class="taringa-input login-header-input" autocomplete="username" />
                        </div>
                        <div class="login-header-field">
                            <label class="login-header-label">Contraseña</label>
                            <input v-model="form.password" type="password" class="taringa-input login-header-input" autocomplete="current-password" />
                        </div>
                    </div>
                    <div class="login-header-actions">
                        <label class="login-header-remember">
                            <input v-model="form.remember" type="checkbox" class="rounded" />
                            recordarme
                        </label>
                        <button type="submit" class="taringa-btn login-header-submit" :disabled="form.processing">
                            Entrar
                        </button>
                    </div>
                </form>
            </div>
            <p v-if="form.errors.login" class="mx-auto max-w-5xl px-1 pt-1 text-right text-xs text-red-300">
                {{ form.errors.login }}
            </p>
        </header>

        <main class="mx-auto grid max-w-5xl gap-6 px-4 py-8 sm:gap-8 sm:py-10 md:grid-cols-[1.2fr_1fr]">
            <!-- Columna informativa con beneficios de la plataforma -->
            <section>
                <div class="mb-4">
                    <span class="taringa-stat-pill">
                        <FaIcon icon="fa-solid fa-bolt" class="text-brandColor" />
                        100% gratis
                    </span>
                </div>

                <h1 class="text-2xl font-extrabold leading-tight text-brandColor sm:text-4xl">
                    Gofio te conecta con la comunidad y con las cosas que te importan.
                </h1>
                <p class="mt-4 text-lg text-fb-muted">
                    Compartí posts, ganá karma, subí de rango, coleccioná medallas y descubrí lo que se comenta hoy.
                </p>

                <ul class="mt-6 space-y-3 text-sm sm:text-base">
                    <li class="flex items-center gap-3">
                        <span class="taringa-step-badge"><FaIcon icon="fa-solid fa-user-group" /></span>
                        Haz amistades sin límites y comparte lo que más te gusta.
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="taringa-step-badge"><FaIcon icon="fa-solid fa-photo-film" /></span>
                        Encuentra videos y música de tu banda favorita.
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="taringa-step-badge"><FaIcon icon="fa-solid fa-coins" /></span>
                        Crea contenido de calidad y sé un creador que gana :)
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="taringa-step-badge"><FaIcon icon="fa-solid fa-sparkles" /></span>
                        <span class="font-semibold text-brandColor">¡Esto y mucho más en Gofio!</span>
                    </li>
                </ul>
            </section>

            <!-- Tarjeta de llamada a la acción para registro -->
            <section class="taringa-card relative">
                <div class="taringa-ribbon">¡GRATIS!</div>
                <div class="taringa-card-header">
                    <FaIcon icon="fa-solid fa-user-plus" />
                    Registrate, ¡es gratis!
                </div>
                <div class="p-5">
                    <p class="text-sm text-fb-muted">
                        Unite en segundos: elegí tu usuario, tu @nick y ya estás dentro de la comunidad.
                    </p>
                    <Link href="/registro" class="taringa-btn mt-4 block text-center">
                        Crear mi cuenta
                    </Link>
                    <p class="mt-4 text-center text-xs text-fb-muted">
                        <span class="hidden sm:inline">¿Ya tenés cuenta? Iniciá sesión arriba a la derecha ↑</span>
                        <span class="sm:hidden">¿Ya tenés cuenta? Iniciá sesión arriba ↑</span>
                    </p>
                </div>
            </section>
        </main>

        <SiteCopyrightFooter />
    </div>
</template>

<style scoped>
.maintenance-page {
    position: relative;
    color: #0f172a;
}

.maintenance-page__bg {
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at 20% 20%, rgba(251, 191, 36, 0.18), transparent 32%),
        radial-gradient(circle at 80% 10%, rgba(45, 212, 191, 0.22), transparent 28%),
        linear-gradient(160deg, #ecfdf5 0%, #f0fdfa 35%, #fff7ed 100%);
    z-index: 0;
}

.maintenance-page__grid {
    position: fixed;
    inset: 0;
    opacity: 0.35;
    background-image:
        linear-gradient(rgba(13, 148, 136, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(13, 148, 136, 0.05) 1px, transparent 1px);
    background-size: 28px 28px;
    z-index: 0;
}

.maintenance-page__content {
    position: relative;
    z-index: 1;
}

.maintenance-page__brand {
    display: block;
    margin-bottom: 1rem;
    text-align: center;
    font-size: 2.15rem;
    font-weight: 900;
    color: #0f766e;
}

.maintenance-login-card {
    overflow: hidden;
    border-radius: 1rem;
    border: 1px solid rgba(13, 148, 136, 0.18);
    background: rgba(255, 255, 255, 0.88);
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(8px);
}

.maintenance-login-card__header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(135deg, #0f766e, #14b8a6);
    padding: 0.9rem 1rem;
    color: white;
}

.maintenance-field-label {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
}

.maintenance-input {
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
    background: white;
    padding: 0.55rem 0.75rem;
    font-size: 0.875rem;
    outline: none;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.maintenance-input:focus {
    border-color: #14b8a6;
    box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.18);
}

.maintenance-submit-btn {
    width: 100%;
    border-radius: 0.55rem;
    border: none;
    background: linear-gradient(135deg, #0f766e, #14b8a6);
    padding: 0.65rem 1rem;
    font-size: 0.875rem;
    font-weight: 700;
    color: white;
    transition: transform 0.15s ease, opacity 0.15s ease;
}

.maintenance-submit-btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.maintenance-submit-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
