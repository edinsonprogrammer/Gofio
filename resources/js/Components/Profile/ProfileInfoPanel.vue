<script setup>
/**
 * Panel de identidad del perfil: biografía, país, edad, WhatsApp
 * y enlaces a redes sociales con iconos de marca.
 */

import { computed } from 'vue';
import FaIcon from '@/Components/UI/FaIcon.vue';
import { resolveCountryFlag } from '@/utils/countries';
import {
    displayHandle,
    facebookUrl,
    instagramUrl,
    whatsappUrl,
    xUrl,
} from '@/utils/socialLinks';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
});

const flag = computed(() => resolveCountryFlag(props.profile.country, props.profile.country_code));

const hasPersonalInfo = computed(() => (
    props.profile.bio
    || props.profile.bio_gif_url
    || props.profile.country
    || props.profile.age
    || props.profile.whatsapp
));

const hasSocial = computed(() => (
    props.profile.instagram
    || props.profile.facebook
    || props.profile.social_x
));

const whatsappLink = computed(() => whatsappUrl(props.profile.whatsapp));
const instagramLink = computed(() => instagramUrl(props.profile.instagram));
const facebookLink = computed(() => facebookUrl(props.profile.facebook));
const xLink = computed(() => xUrl(props.profile.social_x));
</script>

<template>
    <div v-if="hasPersonalInfo || hasSocial" class="profile-identity">
        <div class="profile-identity__accent" aria-hidden="true" />

        <!-- Biografía destacada con comillas decorativas -->
        <blockquote v-if="profile.bio" class="profile-identity__bio">
            <span class="profile-identity__quote-mark" aria-hidden="true">"</span>
            <span class="profile-identity__bio-text">{{ profile.bio }}</span>
            <span class="profile-identity__quote-mark profile-identity__quote-mark--close" aria-hidden="true">"</span>
        </blockquote>

        <!-- GIF opcional de GIPHY en la biografía -->
        <figure v-if="profile.bio_gif_url" class="profile-identity__gif">
            <img
                :src="profile.bio_gif_url"
                alt="GIF de biografía"
                class="profile-identity__gif-media"
                loading="lazy"
            />
            <figcaption class="profile-identity__gif-credit">
                <a href="https://giphy.com/" target="_blank" rel="noopener noreferrer">Powered by GIPHY</a>
            </figcaption>
        </figure>

        <!-- Datos personales: país, edad y contacto WhatsApp -->
        <div v-if="hasPersonalInfo" class="profile-identity__meta">
            <span v-if="profile.country" class="profile-meta-pill profile-meta-pill--country">
                <span class="profile-meta-pill__flag">{{ flag || '🌍' }}</span>
                <span>{{ profile.country }}</span>
            </span>

            <span v-if="profile.age" class="profile-meta-pill">
                <FaIcon icon="fa-solid fa-cake-candles" class="profile-meta-pill__icon" />
                <span>{{ profile.age }} años</span>
            </span>

            <a
                v-if="profile.whatsapp && whatsappLink"
                :href="whatsappLink"
                target="_blank"
                rel="noopener noreferrer"
                class="profile-meta-pill profile-meta-pill--whatsapp"
            >
                <FaIcon icon="fa-brands fa-whatsapp" class="profile-meta-pill__icon" />
                <span>{{ profile.whatsapp }}</span>
            </a>
            <span v-else-if="profile.whatsapp" class="profile-meta-pill profile-meta-pill--whatsapp">
                <FaIcon icon="fa-brands fa-whatsapp" class="profile-meta-pill__icon" />
                <span>{{ profile.whatsapp }}</span>
            </span>
        </div>

        <!-- Enlaces externos a redes sociales del usuario -->
        <div v-if="hasSocial" class="profile-identity__social">
            <p class="profile-identity__social-label">Conecta conmigo</p>
            <div class="profile-social-row">
                <a
                    v-if="instagramLink"
                    :href="instagramLink"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="profile-social-link profile-social-link--instagram"
                >
                    <span class="profile-social-link__icon">
                        <FaIcon icon="fa-brands fa-instagram" />
                    </span>
                    <span class="profile-social-link__text">{{ displayHandle(profile.instagram) }}</span>
                </a>
                <a
                    v-if="facebookLink"
                    :href="facebookLink"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="profile-social-link profile-social-link--facebook"
                >
                    <span class="profile-social-link__icon">
                        <FaIcon icon="fa-brands fa-facebook-f" />
                    </span>
                    <span class="profile-social-link__text">{{ displayHandle(profile.facebook) }}</span>
                </a>
                <a
                    v-if="xLink"
                    :href="xLink"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="profile-social-link profile-social-link--x"
                >
                    <span class="profile-social-link__icon">
                        <FaIcon icon="fa-brands fa-x-twitter" />
                    </span>
                    <span class="profile-social-link__text">{{ displayHandle(profile.social_x) }}</span>
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.profile-identity {
    position: relative;
    margin-top: 1.25rem;
    padding: 1.15rem 1.25rem 1.25rem;
    border-radius: 1rem;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.95) 0%,
        var(--color-accent-soft, #ecfdf5) 55%,
        rgba(255, 255, 255, 0.9) 100%
    );
    box-shadow: 0 10px 30px -12px rgba(13, 148, 136, 0.28);
    overflow: hidden;
}

.profile-identity__accent {
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, var(--color-brand), var(--color-brand-hover));
}

.profile-identity__bio {
    position: relative;
    margin: 0 0 1rem;
    padding: 0 0 0 0.15rem;
    border: 0;
    font-size: 0.95rem;
    line-height: 1.65;
    color: var(--text-principal);
}

.profile-identity__bio-text {
    white-space: pre-wrap;
}

.profile-identity__gif {
    margin: 0 0 1rem;
    text-align: center;
}

.profile-identity__gif-media {
    max-height: 10rem;
    border-radius: 0.75rem;
    box-shadow: 0 8px 24px -12px rgba(15, 23, 42, 0.25);
}

.profile-identity__gif-credit {
    margin-top: 0.35rem;
    font-size: 0.625rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.profile-identity__gif-credit a {
    color: var(--color-muted, #64748b);
    text-decoration: none;
}

.profile-identity__gif-credit a:hover {
    color: var(--color-brand);
}

.profile-identity__quote-mark {
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1;
    color: var(--color-brand);
    opacity: 0.85;
}

.profile-identity__quote-mark--close {
    margin-left: 0.1rem;
}

.profile-identity__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.65rem;
}

.profile-meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.85rem;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.82);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-principal);
    box-shadow: inset 0 0 0 1px rgba(13, 148, 136, 0.12);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.profile-meta-pill--country {
    padding-left: 0.65rem;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.95), rgba(236, 253, 245, 0.95));
}

.profile-meta-pill__flag {
    font-size: 1.35rem;
    line-height: 1;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.08));
}

.profile-meta-pill__icon {
    font-size: 0.95rem;
    color: var(--color-brand);
}

.profile-meta-pill--whatsapp .profile-meta-pill__icon {
    color: #25d366;
}

.profile-meta-pill--whatsapp:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(37, 211, 102, 0.18);
}

.profile-identity__social {
    margin-top: 1.1rem;
    padding-top: 1rem;
    border-top: 1px dashed rgba(13, 148, 136, 0.22);
}

.profile-identity__social-label {
    margin-bottom: 0.65rem;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--color-brand-hover);
}

.profile-social-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
}

.profile-social-link {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.45rem 0.9rem 0.45rem 0.45rem;
    border-radius: 9999px;
    background: white;
    font-size: 0.82rem;
    font-weight: 700;
    box-shadow: 0 4px 14px -6px rgba(15, 23, 42, 0.18);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.profile-social-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px -8px rgba(15, 23, 42, 0.25);
}

.profile-social-link__icon {
    display: flex;
    height: 1.85rem;
    width: 1.85rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    font-size: 0.95rem;
    color: white;
}

.profile-social-link--instagram {
    color: #c13584;
}

.profile-social-link--instagram .profile-social-link__icon {
    background: linear-gradient(135deg, #f58529, #dd2a7b, #8134af);
}

.profile-social-link--facebook {
    color: #1877f2;
}

.profile-social-link--facebook .profile-social-link__icon {
    background: #1877f2;
}

.profile-social-link--x {
    color: #0f1419;
}

.profile-social-link--x .profile-social-link__icon {
    background: #0f1419;
}

.profile-social-link__text {
    padding-right: 0.15rem;
    min-width: 0;
}

@media (max-width: 639px) {
    .profile-identity {
        margin-top: 1rem;
        padding: 0.875rem 0.875rem 1rem;
        border-radius: 0.75rem;
    }

    .profile-identity__bio {
        font-size: 0.875rem;
        line-height: 1.55;
    }

    .profile-identity__meta {
        flex-direction: column;
        align-items: stretch;
        gap: 0.45rem;
    }

    .profile-meta-pill {
        width: 100%;
        max-width: 100%;
        font-size: 0.8125rem;
        padding: 0.45rem 0.75rem;
    }

    .profile-meta-pill__flag {
        font-size: 1.15rem;
    }

    .profile-social-row {
        flex-direction: column;
        gap: 0.45rem;
    }

    .profile-social-link {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }

    .profile-social-link__text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
</style>
