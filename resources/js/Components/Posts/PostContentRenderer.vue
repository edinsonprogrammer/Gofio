<script setup>
/**
 * Renderizador de bloques Editor.js de una publicación: encabezados, párrafos,
 * código con syntax highlighting, imágenes, embeds, citas y listas.
 */

import { onMounted, ref } from 'vue';
import hljs from 'highlight.js/lib/core';
import javascript from 'highlight.js/lib/languages/javascript';
import typescript from 'highlight.js/lib/languages/typescript';
import php from 'highlight.js/lib/languages/php';
import python from 'highlight.js/lib/languages/python';
import bash from 'highlight.js/lib/languages/bash';
import xml from 'highlight.js/lib/languages/xml';
import css from 'highlight.js/lib/languages/css';
import sql from 'highlight.js/lib/languages/sql';
import json from 'highlight.js/lib/languages/json';
import 'highlight.js/styles/github-dark.css';
import FaIcon from '@/Components/UI/FaIcon.vue';

hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('typescript', typescript);
hljs.registerLanguage('php', php);
hljs.registerLanguage('python', python);
hljs.registerLanguage('bash', bash);
hljs.registerLanguage('html', xml);
hljs.registerLanguage('css', css);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('json', json);

const props = defineProps({
    content: {
        type: Object,
        default: () => ({ blocks: [] }),
    },
});

/** Referencias a elementos <code> para aplicar resaltado tras el montaje. */
const codeRefs = ref([]);

onMounted(() => {
    codeRefs.value.forEach((el) => {
        if (el) {
            hljs.highlightElement(el);
        }
    });
});

/** Bloques de contenido extraídos del JSON de Editor.js. */
const blocks = props.content?.blocks ?? [];
</script>

<template>
    <div class="post-content space-y-3">
        <template v-for="(block, index) in blocks" :key="index">
            <!-- Encabezados h2–h4 según nivel del bloque -->
            <component
                :is="block.type === 'header' ? `h${block.data.level || 2}` : 'div'"
                v-if="block.type === 'header'"
                class="font-bold text-textPrincipal"
                :class="{
                    'text-2xl': block.data.level === 2,
                    'text-xl': block.data.level === 3,
                    'text-lg': block.data.level === 4,
                }"
                v-html="block.data.text"
            />

            <!-- Párrafo de texto enriquecido -->
            <div
                v-else-if="block.type === 'paragraph'"
                class="post-rich-text text-sm leading-relaxed text-textPrincipal"
                v-html="block.data.text"
            />

            <!-- Bloque de código con etiqueta de lenguaje y resaltado -->
            <div v-else-if="block.type === 'code'" class="overflow-hidden rounded-lg bg-[#0d1117]">
                <div class="flex items-center justify-between border-b border-white/10 bg-[#161b22] px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide text-slate-400">
                    <span><FaIcon icon="fa-solid fa-code" class="mr-1.5" />{{ block.data.language || 'código' }}</span>
                </div>
                <pre class="max-h-96 overflow-auto p-3 text-xs"><code
                    :ref="(el) => codeRefs.push(el)"
                    :class="`language-${block.data.language || 'plaintext'}`"
                >{{ block.data.code }}</code></pre>
            </div>

            <!-- Imagen con pie de foto opcional -->
            <figure v-else-if="block.type === 'image' && block.data.url" class="text-center">
                <img :src="block.data.url" :alt="block.data.caption || ''" class="mx-auto max-h-96 rounded" />
                <figcaption v-if="block.data.caption" class="mt-1 text-xs text-fb-muted">
                    {{ block.data.caption }}
                </figcaption>
            </figure>

            <!-- GIF de GIPHY con atribución de marca -->
            <figure v-else-if="block.type === 'gif' && block.data.url" class="post-gif-block text-center">
                <img
                    :src="block.data.url"
                    :alt="block.data.title || 'GIF'"
                    class="post-gif-block__media mx-auto max-h-96 rounded"
                    loading="lazy"
                />
                <figcaption class="post-gif-block__credit">
                    <a href="https://giphy.com/" target="_blank" rel="noopener noreferrer">Powered by GIPHY</a>
                </figcaption>
            </figure>

            <!-- Contenido embebido (vídeo u otro iframe) -->
            <div v-else-if="block.type === 'embed' && block.data.embed" class="aspect-video overflow-hidden rounded">
                <iframe
                    :src="block.data.embed"
                    class="h-full w-full"
                    frameborder="0"
                    allowfullscreen
                />
            </div>

            <!-- Cita con autor opcional -->
            <blockquote
                v-else-if="block.type === 'quote'"
                class="border-l-4 border-brandColor bg-[#F5F6F7] px-4 py-2 text-sm italic text-textPrincipal"
            >
                <p v-html="block.data.text" />
                <footer v-if="block.data.caption" class="mt-1 text-xs not-italic text-fb-muted">
                    — {{ block.data.caption }}
                </footer>
            </blockquote>

            <!-- Lista ordenada o con viñetas -->
            <component
                :is="block.data.style === 'ordered' ? 'ol' : 'ul'"
                v-else-if="block.type === 'list'"
                class="list-inside space-y-1 text-sm text-textPrincipal"
                :class="block.data.style === 'ordered' ? 'list-decimal' : 'list-disc'"
            >
                <li v-for="(item, li) in block.data.items" :key="li" v-html="item" />
            </component>

            <!-- Separador horizontal -->
            <hr v-else-if="block.type === 'delimiter'" class="my-4 border-fb-border" />
        </template>
    </div>
</template>

<style scoped>
.post-rich-text :deep(ul),
.post-rich-text :deep(ol) {
    margin: 0.5rem 0 0.5rem 1.25rem;
    padding-left: 0.5rem;
}

.post-rich-text :deep(ul) {
    list-style-type: disc;
    list-style-position: outside;
}

.post-rich-text :deep(ol) {
    list-style-type: decimal;
    list-style-position: outside;
}

.post-rich-text :deep(li) {
    display: list-item;
    margin: 0.2rem 0;
}

.post-rich-text :deep(div[style*="text-align:center"]) {
    text-align: center;
}

.post-rich-text :deep(div[style*="text-align:right"]) {
    text-align: right;
}

.post-rich-text :deep(div[style*="text-align:left"]) {
    text-align: left;
}

.post-gif-block__credit {
    margin-top: 0.35rem;
    font-size: 0.625rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.post-gif-block__credit a {
    color: var(--color-muted, #64748b);
    text-decoration: none;
}

.post-gif-block__credit a:hover {
    color: var(--color-brand);
}
</style>
