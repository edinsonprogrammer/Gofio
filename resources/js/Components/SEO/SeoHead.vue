<script setup>
/**
 * Metadatos SEO por página: title, description, Open Graph, Twitter Cards y JSON-LD.
 */

import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    seo: {
        type: Object,
        default: null,
    },
});

const page = usePage();

/** Resuelve metadatos de la página o los valores globales del sitio. */
const meta = computed(() => props.seo ?? page.props.site?.default ?? {});

const jsonLd = computed(() => {
    if (!meta.value?.json_ld) {
        return '';
    }

    return JSON.stringify(meta.value.json_ld);
});
</script>

<template>
    <Head>
        <title>{{ meta.title }}</title>
        <meta head-key="description" name="description" :content="meta.description" />
        <meta v-if="meta.keywords" head-key="keywords" name="keywords" :content="meta.keywords" />
        <meta head-key="robots" name="robots" :content="meta.robots || 'index,follow'" />
        <link head-key="canonical" rel="canonical" :href="meta.canonical" />

        <meta head-key="og:title" property="og:title" :content="meta.title" />
        <meta head-key="og:description" property="og:description" :content="meta.description" />
        <meta head-key="og:type" property="og:type" :content="meta.type || 'website'" />
        <meta head-key="og:url" property="og:url" :content="meta.canonical" />
        <meta head-key="og:site_name" property="og:site_name" :content="meta.site_name" />
        <meta v-if="meta.image" head-key="og:image" property="og:image" :content="meta.image" />

        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="meta.title" />
        <meta head-key="twitter:description" name="twitter:description" :content="meta.description" />
        <meta v-if="meta.image" head-key="twitter:image" name="twitter:image" :content="meta.image" />

        <component
            :is="'script'"
            v-if="jsonLd"
            head-key="json-ld"
            type="application/ld+json"
        >{{ jsonLd }}</component>
    </Head>
</template>
