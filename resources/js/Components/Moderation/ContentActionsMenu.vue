<script setup>
/**
 * Menú contextual de acciones de moderación sobre posts y comentarios.
 */

import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ReportModal from '@/Components/Moderation/ReportModal.vue';

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (value) => ['post', 'comment'].includes(value),
    },
    targetId: {
        type: Number,
        required: true,
    },
    ownerId: {
        type: Number,
        required: true,
    },
    shareUrl: {
        type: String,
        default: '',
    },
    shareTitle: {
        type: String,
        default: 'Contenido en Gofio',
    },
});

const VIEWPORT_PADDING = 12;
const MENU_GAP = 6;

const page = usePage();
const open = ref(false);
const showReport = ref(false);
const triggerRef = ref(null);
const menuRef = ref(null);
const toast = ref('');
const menuStyle = ref({
    top: '0px',
    left: '0px',
    visibility: 'hidden',
});
const toastStyle = ref({
    top: '0px',
    left: '0px',
    visibility: 'hidden',
});

const canInteract = computed(() => {
    const userId = page.props.auth?.user?.id;
    return Boolean(userId);
});

const isOwner = computed(() => page.props.auth?.user?.id === props.ownerId);

const storageKey = computed(() => (props.type === 'post' ? 'gofio_saved_posts' : 'gofio_saved_comments'));


/** Indica si el post está guardado en favoritos del usuario. */
const isSaved = () => {
    try {
        const saved = JSON.parse(localStorage.getItem(storageKey.value) || '[]');
        return saved.includes(props.targetId);
    } catch {
        return false;
    }
};

const saved = ref(isSaved());


/** Cierra el menú flotante de acciones. */
const closeMenu = () => {
    open.value = false;
};


/** Limita un valor numérico entre mínimo y máximo. */
const clamp = (value, min, max) => Math.min(Math.max(value, min), max);


/** Calcula la posición del panel respecto al botón ancla. */
const positionFloatingPanel = (trigger, panel, preferBelow = true) => {
    if (!trigger || !panel) {
        return { top: VIEWPORT_PADDING, left: VIEWPORT_PADDING, visibility: 'visible' };
    }

    const rect = trigger.getBoundingClientRect();
    const panelRect = panel.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    const maxLeft = viewportWidth - panelRect.width - VIEWPORT_PADDING;
    const maxTop = viewportHeight - panelRect.height - VIEWPORT_PADDING;

    let left = rect.right - panelRect.width;
    left = clamp(left, VIEWPORT_PADDING, Math.max(VIEWPORT_PADDING, maxLeft));

    let top = preferBelow ? rect.bottom + MENU_GAP : rect.top - panelRect.height - MENU_GAP;

    if (preferBelow && top + panelRect.height > viewportHeight - VIEWPORT_PADDING) {
        top = rect.top - panelRect.height - MENU_GAP;
    } else if (!preferBelow && top < VIEWPORT_PADDING) {
        top = rect.bottom + MENU_GAP;
    }

    top = clamp(top, VIEWPORT_PADDING, Math.max(VIEWPORT_PADDING, maxTop));

    return {
        top: `${top}px`,
        left: `${left}px`,
        visibility: 'visible',
    };
};


/** Recalcula la posición del menú al redimensionar o hacer scroll. */
const updateMenuPosition = async () => {
    await nextTick();
    menuStyle.value = positionFloatingPanel(triggerRef.value, menuRef.value, true);
};


/** Ajusta la posición del toast de confirmación junto al menú. */
const updateToastPosition = async () => {
    await nextTick();
    toastStyle.value = positionFloatingPanel(triggerRef.value, null, true);

    if (!triggerRef.value) {
        return;
    }

    const rect = triggerRef.value.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const toastWidth = 140;
    const toastHeight = 28;

    let left = rect.right - toastWidth;
    left = clamp(left, VIEWPORT_PADDING, viewportWidth - toastWidth - VIEWPORT_PADDING);

    let top = rect.bottom + MENU_GAP;
    if (top + toastHeight > viewportHeight - VIEWPORT_PADDING) {
        top = rect.top - toastHeight - MENU_GAP;
    }
    top = clamp(top, VIEWPORT_PADDING, viewportHeight - toastHeight - VIEWPORT_PADDING);

    toastStyle.value = {
        top: `${top}px`,
        left: `${left}px`,
        visibility: 'visible',
    };
};

watch(open, (isOpen) => {
    if (isOpen) {
        menuStyle.value = { top: '0px', left: '0px', visibility: 'hidden' };
        updateMenuPosition();
    }
});

watch(toast, (message) => {
    if (message) {
        toastStyle.value = { top: '0px', left: '0px', visibility: 'hidden' };
        updateToastPosition();
    }
});


/** Cierra paneles al hacer clic fuera del componente. */
const onDocumentClick = (event) => {
    const target = event.target;
    const clickedTrigger = triggerRef.value?.contains(target);
    const clickedMenu = menuRef.value?.contains(target);

    if (!clickedTrigger && !clickedMenu) {
        closeMenu();
    }
};


/** Reubica el menú cuando cambia el tamaño de la ventana. */
const onViewportChange = () => {
    if (open.value) {
        updateMenuPosition();
    }
    if (toast.value) {
        updateToastPosition();
    }
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('scroll', onViewportChange, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
});


/** Muestra un mensaje breve de confirmación tras una acción. */
const showToast = (message) => {
    toast.value = message;
    setTimeout(() => {
        toast.value = '';
    }, 2200);
};


/** Guarda o quita el post de favoritos del usuario. */
const toggleSave = () => {
    try {
        const current = JSON.parse(localStorage.getItem(storageKey.value) || '[]');
        const exists = current.includes(props.targetId);
        const next = exists
            ? current.filter((id) => id !== props.targetId)
            : [...current, props.targetId];

        localStorage.setItem(storageKey.value, JSON.stringify(next));
        saved.value = !exists;
        showToast(exists ? 'Eliminado de guardados' : 'Guardado');
    } catch {
        showToast('No se pudo guardar');
    }

    closeMenu();
};


/** Copia el enlace del contenido al portapapeles o usa Web Share API. */
const share = async () => {
    const url = props.shareUrl || window.location.href;

    try {
        if (navigator.share) {
            await navigator.share({
                title: props.shareTitle,
                url,
            });
        } else {
            await navigator.clipboard.writeText(url);
            showToast('Enlace copiado');
        }
    } catch {
        showToast('No se pudo compartir');
    }

    closeMenu();
};


/** Abre el modal de denuncia del contenido actual. */
const openReport = () => {
    closeMenu();
    showReport.value = true;
};


/** Muestra u oculta el menú de acciones del post. */
const toggleMenu = () => {
    open.value = !open.value;
};
</script>

<template>
    <!-- Menú flotante de acciones sobre publicaciones -->

    <div v-if="canInteract" class="shrink-0">
        <button
            ref="triggerRef"
            type="button"
            class="inline-flex h-7 w-7 items-center justify-center rounded-full text-fb-muted transition hover:bg-[#E4E6EB] hover:text-fb-link"
            aria-label="Más opciones"
            aria-haspopup="menu"
            :aria-expanded="open"
            @click.stop="toggleMenu"
        >
            <i class="fa-solid fa-ellipsis-vertical text-xs"></i>
        </button>

    <!-- Modal superpuesto -->
        <Teleport to="body">
            <div
                v-if="open"
                ref="menuRef"
                role="menu"
                class="content-actions-menu fixed z-[200] min-w-[10.5rem] max-w-[calc(100vw-24px)] overflow-hidden rounded-lg border border-fb-border bg-white py-1 shadow-xl"
                :style="menuStyle"
            >
                <button
                    v-if="!isOwner"
                    type="button"
                    role="menuitem"
                    class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm text-red-600 hover:bg-[#F5F6F7]"
                    @click="openReport"
                >
                    <i class="fa-regular fa-flag w-4 text-center text-xs"></i>
                    Denunciar
                </button>
                <button
                    type="button"
                    role="menuitem"
                    class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm hover:bg-[#F5F6F7]"
                    @click="toggleSave"
                >
                    <i class="fa-regular fa-bookmark w-4 text-center text-xs"></i>
                    {{ saved ? 'Quitar guardado' : 'Guardar' }}
                </button>
                <button
                    type="button"
                    role="menuitem"
                    class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm hover:bg-[#F5F6F7]"
                    @click="share"
                >
                    <i class="fa-solid fa-share-nodes w-4 text-center text-xs"></i>
                    Compartir
                </button>
            </div>

            <p
                v-if="toast"
                class="pointer-events-none fixed z-[201] max-w-[calc(100vw-24px)] whitespace-nowrap rounded bg-slate-800 px-2.5 py-1.5 text-[10px] text-white shadow-lg"
                :style="toastStyle"
            >
                {{ toast }}
            </p>
        </Teleport>

        <ReportModal
            v-if="!isOwner"
            :show="showReport"
            :type="type"
            :target-id="targetId"
            @close="showReport = false"
        />
    </div>
</template>
