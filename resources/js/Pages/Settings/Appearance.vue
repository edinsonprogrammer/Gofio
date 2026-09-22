<script setup>
/**
 * Página de configuración de apariencia: selección de temas visuales
 * con restricciones según suscripción Creator Plus.
 */

import { Link } from '@inertiajs/vue3';
import GofioLayout from '@/Layouts/GofioLayout.vue';
import ThemePicker from '@/Components/Theme/ThemePicker.vue';

defineProps({
    appearance: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <GofioLayout>
        <template #feed>
            <div class="gofio-box overflow-hidden">
                <div class="gofio-box-header">Apariencia</div>
                <div class="space-y-4 p-4">
                    <p class="text-sm text-fb-muted">
                        Elige un skin para personalizar colores de la interfaz. Los temas marcados con Plus requieren
                        Creator Plus activo. Los cambios se aplican al instante.
                    </p>

                    <!-- Selector de temas disponibles para el usuario -->
                    <ThemePicker
                        :themes="appearance.themes"
                        :active-theme-id="appearance.active_theme_id"
                    />

                    <Link
                        v-if="!$page.props.auth.user?.is_creator_plus && !$page.props.auth.user?.is_staff"
                        href="/configuracion/creator-plus"
                        class="inline-block text-sm font-semibold text-fb-link hover:underline"
                    >
                        Desbloquea temas premium con Creator Plus →
                    </Link>
                </div>
            </div>
        </template>
    </GofioLayout>
</template>
