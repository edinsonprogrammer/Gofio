/**
 * Registra visitas únicas a posts visibles en el feed mediante IntersectionObserver.
 * Evita peticiones repetidas en la misma sesión del navegador.
 */

import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const SESSION_KEY = 'gofio:post_views_session';

/** Obtiene el conjunto de IDs ya enviados en esta sesión. */
function getSessionViewedIds() {
    try {
        const raw = sessionStorage.getItem(SESSION_KEY);
        return new Set(Array.isArray(JSON.parse(raw)) ? JSON.parse(raw) : []);
    } catch {
        return new Set();
    }
}

/** Marca un post como ya reportado en sessionStorage. */
function markSessionViewed(postId) {
    const viewed = getSessionViewedIds();
    viewed.add(postId);
    sessionStorage.setItem(SESSION_KEY, JSON.stringify([...viewed]));
}

/**
 * Observa un post en pantalla y reporta la visita una sola vez por sesión.
 */
export function usePostViewTracker(rootRef, postIdRef, onViewsUpdated, enabledRef = null) {
    const viewsCount = ref(null);
    let observer = null;
    let reported = false;

    const isEnabled = () => enabledRef?.value !== false;

    /** Envía la visita al backend si aún no se reportó en esta sesión. */
    const reportView = async (postId) => {
        if (! isEnabled() || ! postId || reported || getSessionViewedIds().has(postId)) {
            return;
        }

        reported = true;

        try {
            const { data } = await window.axios.post(`/api/posts/${postId}/view`);

            markSessionViewed(postId);

            if (typeof data?.views_count === 'number') {
                viewsCount.value = data.views_count;
                onViewsUpdated?.(data.views_count);
            }
        } catch {
            reported = false;
        }
    };

    /** Configura el observer de visibilidad sobre la tarjeta del post. */
    const setupObserver = () => {
        observer?.disconnect();
        observer = null;

        if (! isEnabled()) {
            return;
        }

        const el = rootRef.value;
        const postId = postIdRef.value;

        if (! el || ! postId || getSessionViewedIds().has(postId)) {
            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                if (! entries[0]?.isIntersecting) {
                    return;
                }

                observer?.disconnect();
                observer = null;
                reportView(postId);
            },
            { threshold: 0.55, rootMargin: '0px' },
        );

        observer.observe(el);
    };

    onMounted(() => {
        setupObserver();
    });

    watch([postIdRef, () => enabledRef?.value], () => {
        reported = false;
        setupObserver();
    });

    onBeforeUnmount(() => {
        observer?.disconnect();
        observer = null;
    });

    return { viewsCount };
}
