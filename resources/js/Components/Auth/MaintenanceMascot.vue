<script setup>
/**
 * Mascota animada y mensaje amigable mostrados durante el modo mantenimiento
 * en las pantallas de autenticación.
 */

defineProps({
    message: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="maintenance-mascot-wrap">
        <!-- Escena ilustrada con herramientas flotantes y mascota de Gofio -->
        <div class="maintenance-scene">
            <div class="maintenance-tools">
                <span class="maintenance-tool maintenance-tool--wrench">🔧</span>
                <span class="maintenance-tool maintenance-tool--gear">⚙️</span>
                <span class="maintenance-tool maintenance-tool--spark">✨</span>
            </div>

            <div class="maintenance-mascot" aria-hidden="true">
                <div class="maintenance-mascot__shadow"></div>
                <div class="maintenance-mascot__body">
                    <div class="maintenance-mascot__face">
                        <span class="maintenance-mascot__eye maintenance-mascot__eye--left"></span>
                        <span class="maintenance-mascot__eye maintenance-mascot__eye--right"></span>
                        <span class="maintenance-mascot__cheek maintenance-mascot__cheek--left"></span>
                        <span class="maintenance-mascot__cheek maintenance-mascot__cheek--right"></span>
                        <span class="maintenance-mascot__smile"></span>
                    </div>
                    <div class="maintenance-mascot__belly">g</div>
                    <div class="maintenance-mascot__arm maintenance-mascot__arm--left"></div>
                    <div class="maintenance-mascot__arm maintenance-mascot__arm--right"></div>
                </div>
            </div>

            <!-- Globo de diálogo con título y mensaje personalizable -->
            <div class="maintenance-bubble">
                <p class="maintenance-bubble__title">¡Estamos en mantenimiento!</p>
                <p class="maintenance-bubble__text">
                    {{ message || 'Vuelve después para que continúe la diversión. Estamos trabajando para que este espacio sea ameno y único para ti.' }}
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.maintenance-mascot-wrap {
    width: 100%;
}

.maintenance-scene {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
    padding: 0.5rem 0 1rem;
}

.maintenance-tools {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.maintenance-tool {
    position: absolute;
    font-size: 1.25rem;
    opacity: 0.85;
    animation: maintenance-float 4s ease-in-out infinite;
}

.maintenance-tool--wrench {
    top: 8%;
    left: 12%;
    animation-delay: 0s;
}

.maintenance-tool--gear {
    top: 14%;
    right: 10%;
    animation-delay: 1.2s;
}

.maintenance-tool--spark {
    bottom: 28%;
    left: 18%;
    animation-delay: 2.1s;
}

.maintenance-mascot {
    position: relative;
    width: 8.5rem;
    height: 9rem;
    animation: maintenance-bounce 2.4s ease-in-out infinite;
}

.maintenance-mascot__shadow {
    position: absolute;
    bottom: -0.35rem;
    left: 50%;
    width: 5rem;
    height: 0.75rem;
    transform: translateX(-50%);
    border-radius: 9999px;
    background: rgba(15, 23, 42, 0.12);
    animation: maintenance-shadow 2.4s ease-in-out infinite;
}

.maintenance-mascot__body {
    position: relative;
    width: 100%;
    height: 100%;
    border-radius: 45% 45% 42% 42%;
    background: linear-gradient(160deg, #14b8a6 0%, #0d9488 45%, #0f766e 100%);
    box-shadow:
        inset 0 -8px 18px rgba(0, 0, 0, 0.12),
        0 12px 28px rgba(13, 148, 136, 0.35);
}

.maintenance-mascot__face {
    position: absolute;
    top: 1.65rem;
    left: 50%;
    width: 4.5rem;
    height: 3rem;
    transform: translateX(-50%);
}

.maintenance-mascot__eye {
    position: absolute;
    top: 0.55rem;
    width: 0.55rem;
    height: 0.75rem;
    border-radius: 9999px;
    background: #0f172a;
    animation: maintenance-blink 4.5s infinite;
}

.maintenance-mascot__eye--left {
    left: 0.95rem;
}

.maintenance-mascot__eye--right {
    right: 0.95rem;
}

.maintenance-mascot__cheek {
    position: absolute;
    top: 1.35rem;
    width: 0.85rem;
    height: 0.55rem;
    border-radius: 9999px;
    background: rgba(251, 113, 133, 0.45);
}

.maintenance-mascot__cheek--left {
    left: 0.35rem;
}

.maintenance-mascot__cheek--right {
    right: 0.35rem;
}

.maintenance-mascot__smile {
    position: absolute;
    bottom: 0.35rem;
    left: 50%;
    width: 1.5rem;
    height: 0.75rem;
    transform: translateX(-50%);
    border-bottom: 3px solid #0f172a;
    border-radius: 0 0 9999px 9999px;
}

.maintenance-mascot__belly {
    position: absolute;
    bottom: 1.35rem;
    left: 50%;
    transform: translateX(-50%);
    font-size: 1.35rem;
    font-weight: 900;
    color: rgba(255, 255, 255, 0.92);
    text-shadow: 0 1px 0 rgba(0, 0, 0, 0.08);
}

.maintenance-mascot__arm {
    position: absolute;
    top: 4.2rem;
    width: 1.15rem;
    height: 2.4rem;
    border-radius: 9999px;
    background: linear-gradient(180deg, #14b8a6, #0f766e);
}

.maintenance-mascot__arm--left {
    left: -0.35rem;
    transform: rotate(24deg);
    animation: maintenance-wave-left 2.4s ease-in-out infinite;
}

.maintenance-mascot__arm--right {
    right: -0.35rem;
    transform: rotate(-18deg);
    animation: maintenance-wave-right 2.4s ease-in-out infinite;
}

.maintenance-bubble {
    position: relative;
    max-width: 28rem;
    border-radius: 1.25rem;
    border: 2px solid rgba(255, 255, 255, 0.65);
    background: rgba(255, 255, 255, 0.92);
    padding: 1.1rem 1.25rem;
    text-align: center;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
}

.maintenance-bubble::before {
    content: '';
    position: absolute;
    top: -0.65rem;
    left: 50%;
    width: 1.1rem;
    height: 1.1rem;
    transform: translateX(-50%) rotate(45deg);
    background: rgba(255, 255, 255, 0.92);
    border-top: 2px solid rgba(255, 255, 255, 0.65);
    border-left: 2px solid rgba(255, 255, 255, 0.65);
}

.maintenance-bubble__title {
    font-size: 1.15rem;
    font-weight: 800;
    color: #0f766e;
}

.maintenance-bubble__text {
    margin-top: 0.45rem;
    font-size: 0.92rem;
    line-height: 1.55;
    color: #475569;
}

@keyframes maintenance-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes maintenance-shadow {
    0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.35; }
    50% { transform: translateX(-50%) scale(0.88); opacity: 0.22; }
}

@keyframes maintenance-blink {
    0%, 44%, 48%, 100% { transform: scaleY(1); }
    46% { transform: scaleY(0.12); }
}

@keyframes maintenance-wave-left {
    0%, 100% { transform: rotate(24deg); }
    50% { transform: rotate(8deg); }
}

@keyframes maintenance-wave-right {
    0%, 100% { transform: rotate(-18deg); }
    50% { transform: rotate(-34deg); }
}

@keyframes maintenance-float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-8px) rotate(8deg); }
}
</style>
