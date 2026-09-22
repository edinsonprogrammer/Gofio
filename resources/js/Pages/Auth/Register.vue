<script setup>
/**
 * Página de registro de nuevos usuarios con formulario en tres pasos
 * (usuario/@nick, correo y contraseña) y estadísticas de la comunidad.
 */

import { Link, useForm } from '@inertiajs/vue3';
import FaIcon from '@/Components/UI/FaIcon.vue';
import SiteCopyrightFooter from '@/Components/UI/SiteCopyrightFooter.vue';
import SiteBrand from '@/Components/UI/SiteBrand.vue';
import SeoHead from '@/Components/SEO/SeoHead.vue';

defineProps({
    stats: {
        type: Object,
        default: () => ({ users: 0, posts: 0 }),
    },
    seo: {
        type: Object,
        default: null,
    },
});

/** Formulario Inertia con validación del lado servidor. */
const form = useForm({
    username: '',
    nick: '',
    email: '',
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <SeoHead :seo="seo" />
    <div class="min-h-screen bg-backgroundPrincipal">
        <!-- Cabecera con marca y enlace al login -->
        <header class="taringa-topbar login-header px-4 py-3 text-white sm:py-4">
            <div class="mx-auto flex max-w-3xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <SiteBrand href="/" variant="login-lg" />
                <Link href="/login" class="text-center text-sm text-white/80 hover:text-white hover:underline sm:text-right">
                    ¿Ya tenés cuenta? Iniciá sesión
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-md px-4 py-8 sm:py-10">
            <!-- Indicadores de tamaño de la comunidad -->
            <div class="mb-4 flex flex-wrap justify-center gap-2">
                <span class="taringa-stat-pill">
                    <FaIcon icon="fa-solid fa-users" class="text-brandColor" />
                    {{ stats.users.toLocaleString('es') }} usuarios
                </span>
                <span class="taringa-stat-pill">
                    <FaIcon icon="fa-solid fa-fire" class="text-orange-500" />
                    {{ stats.posts.toLocaleString('es') }} posts
                </span>
            </div>

            <!-- Formulario de alta de cuenta -->
            <section class="taringa-card relative">
                <div class="taringa-ribbon">¡GRATIS!</div>
                <div class="taringa-card-header">
                    <FaIcon icon="fa-solid fa-user-plus" />
                    Registrate en gofio!
                </div>

                <form class="space-y-5 p-6" @submit.prevent="form.post('/registro')">
                    <p class="text-sm text-fb-muted">Creá tu cuenta en unos segundos y sumate a la comunidad.</p>

                    <div>
                        <div class="mb-1 flex items-center gap-2">
                            <span class="taringa-step-badge">1</span>
                            <label class="text-sm font-semibold">Elegí tu usuario</label>
                        </div>
                        <input v-model="form.username" type="text" class="taringa-input" autocomplete="username" placeholder="nombre_de_usuario" />
                        <p v-if="form.errors.username" class="mt-1 text-xs text-red-600">{{ form.errors.username }}</p>

                        <div class="mt-3 flex items-center gap-1 taringa-input">
                            <span class="text-fb-muted">@</span>
                            <input
                                v-model="form.nick"
                                type="text"
                                class="w-full border-0 bg-transparent p-0 text-sm outline-none focus:ring-0"
                                placeholder="tu_nick (opcional)"
                                autocomplete="off"
                            />
                        </div>
                        <p class="mt-1 text-xs text-fb-muted">Con tu @nick te van a poder encontrar más fácil en el buscador.</p>
                        <p v-if="form.errors.nick" class="mt-1 text-xs text-red-600">{{ form.errors.nick }}</p>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center gap-2">
                            <span class="taringa-step-badge">2</span>
                            <label class="text-sm font-semibold">Tu correo electrónico</label>
                        </div>
                        <input v-model="form.email" type="email" class="taringa-input" autocomplete="email" placeholder="tu@correo.com" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center gap-2">
                            <span class="taringa-step-badge">3</span>
                            <label class="text-sm font-semibold">Elegí tu contraseña</label>
                        </div>
                        <input v-model="form.password" type="password" class="taringa-input" autocomplete="new-password" placeholder="Contraseña" />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            class="taringa-input mt-2"
                            autocomplete="new-password"
                            placeholder="Repetí la contraseña"
                        />
                        <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <button type="submit" class="taringa-btn py-3 text-base" :disabled="form.processing">
                        Crear mi cuenta
                    </button>

                    <p class="text-center text-[11px] text-fb-muted">
                        Al registrarte aceptás formar parte de la comunidad gofio<span class="text-brandColor">!</span>
                    </p>
                </form>
            </section>
        </main>

        <SiteCopyrightFooter />
    </div>
</template>
