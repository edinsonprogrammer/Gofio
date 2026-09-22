/**
 * Cliente del proxy GIPHY de Gofio: estado de configuración, tendencias y búsqueda.
 */

import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/** Identificador del icono de GIPHY en el paquete Gemas. */
export const GIPHY_PACK_ICON = 'pack:gemas/imagenes/giphy.svg';

/**
 * Expone si GIPHY está configurado en el servidor y helpers de petición.
 */
export function useGiphy() {
    const page = usePage();

    const configured = computed(() => page.props.giphy?.configured === true);

    /** Obtiene GIFs en tendencia desde el backend. */
    const fetchTrending = async (limit = 24, offset = 0) => {
        const { data } = await window.axios.get('/api/giphy/trending', {
            params: { limit, offset },
        });

        return data?.data ?? [];
    };

    /** Busca GIFs por término desde el backend. */
    const search = async (query, limit = 24, offset = 0) => {
        const trimmed = (query ?? '').trim();

        if (! trimmed) {
            return [];
        }

        const { data } = await window.axios.get('/api/giphy/search', {
            params: { q: trimmed, limit, offset },
        });

        return data?.data ?? [];
    };

    return {
        configured,
        fetchTrending,
        search,
        packIcon: GIPHY_PACK_ICON,
    };
}
