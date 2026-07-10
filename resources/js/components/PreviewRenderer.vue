<script setup lang="ts">
import { Color } from '@tiptap/extension-color';
import Image from '@tiptap/extension-image';
import { Mathematics } from '@tiptap/extension-mathematics';
import { TableCell } from '@tiptap/extension-table/cell';
import { TableHeader } from '@tiptap/extension-table/header';
import { TableRow } from '@tiptap/extension-table/row';
import { Table } from '@tiptap/extension-table/table';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import Youtube from '@tiptap/extension-youtube';
import { generateHTML } from '@tiptap/html';
import StarterKit from '@tiptap/starter-kit';
import katex from 'katex';
import { computed, ref, nextTick, watch, onMounted } from 'vue';
import SlideshowPreview from './SlideshowPreview.vue';
import ZoomableImage from './ZoomableImage.vue';

const props = defineProps<{
    content: any;
}>();

const viewerOptions = {
    inline: false,
    button: false,
    navbar: false,
    title: false,
    toolbar: false,
    tooltip: false,
    movable: true,
    zoomable: true,
    rotatable: false,
    scalable: true,
    transition: true,
    fullscreen: true,
    keyboard: false,
};

const extensions = [
    StarterKit,
    Image.configure({ inline: true }),
    Youtube.configure({ inline: false, width: 640, height: 480 }),
    Mathematics.configure({ katexOptions: { throwOnError: false } }),
    Table.configure({ resizable: true }),
    TableRow,
    TableHeader,
    TableCell,
    TextAlign.configure({
        types: ['heading', 'paragraph'],
    }),
    TextStyle,
    Color,
];

const containerRef = ref<HTMLElement | null>(null);

const blocks = computed(() => {
    if (!props.content) {
        return [];
    }

    try {
        const json =
            typeof props.content === 'string'
                ? JSON.parse(props.content)
                : props.content;

        if (
            !json ||
            (Object.keys(json).length === 0 && json.constructor === Object)
        ) {
            return [];
        }

        if (json.type === 'doc' && Array.isArray(json.content)) {
            return json.content;
        }

        return [];
    } catch (error) {
        console.error('Failed to parse Tiptap JSON', error);

        return [];
    }
});

function renderHtmlNode(node: any) {
    try {
        return generateHTML({ type: 'doc', content: [node] }, extensions);
    } catch (error) {
        console.error('Failed to generate HTML for node', error);

        return '';
    }
}

function renderMath() {
    nextTick(() => {
        if (!containerRef.value) {
            return;
        }

        const mathElements =
            containerRef.value.querySelectorAll('[data-latex]');
        mathElements.forEach((el) => {
            const latex = el.getAttribute('data-latex') || '';
            const isBlock = el.getAttribute('data-type') === 'block-math';

            try {
                katex.render(latex, el as HTMLElement, {
                    displayMode: isBlock,
                    throwOnError: false,
                });
            } catch (error) {
                console.error('KaTeX rendering error in preview', error);
            }
        });
    });
}

watch(
    () => props.content,
    () => {
        renderMath();
    },
    { immediate: true, deep: true },
);

onMounted(() => {
    renderMath();
});
</script>

<template>
    <div
        ref="containerRef"
        class="tiptap-preview px-4 py-2"
        v-viewer="viewerOptions"
    >
        <template v-for="(node, index) in blocks" :key="index">
            <SlideshowPreview
                v-if="node.type === 'slideshow'"
                :images="node.attrs?.images || []"
            />
            <ZoomableImage
                v-else-if="node.type === 'image'"
                :src="node.attrs?.src"
                :alt="node.attrs?.alt"
                :title="node.attrs?.title"
            />
            <div v-else v-html="renderHtmlNode(node)"></div>
        </template>
        <div
            v-if="blocks.length === 0"
            class="py-8 text-center text-sm text-muted-foreground italic"
        >
            Belum ada konten.
        </div>
    </div>
</template>

<style>
/* Custom styling for the tiptap preview content to make it look clean and premium */
.tiptap-preview {
    font-family: inherit;
    line-height: 1.75;
}

.tiptap-preview h1 {
    font-size: 2.25em;
    font-weight: 800;
    margin-top: 0;
    margin-bottom: 0.88em;
    line-height: 1.1;
}

.tiptap-preview h2 {
    font-size: 1.5em;
    font-weight: 700;
    margin-top: 2em;
    margin-bottom: 1em;
    line-height: 1.33;
}

.tiptap-preview h3 {
    font-size: 1.25em;
    font-weight: 600;
    margin-top: 1.6em;
    margin-bottom: 0.6em;
    line-height: 1.6;
}

.tiptap-preview p {
    margin-top: 0;
    margin-bottom: 1.25em;
}

.tiptap-preview ul {
    list-style-type: disc;
    margin-top: 0;
    margin-bottom: 1.25em;
    padding-left: 1.625em;
}

.tiptap-preview ol {
    list-style-type: decimal;
    margin-top: 0;
    margin-bottom: 1.25em;
    padding-left: 1.625em;
}

.tiptap-preview li {
    margin-top: 0.25em;
    margin-bottom: 0.25em;
}

.tiptap-preview blockquote {
    font-weight: 500;
    font-style: italic;
    color: var(--color-muted-foreground);
    border-left-width: 4px;
    border-left-color: var(--color-border);
    margin-top: 1.6em;
    margin-bottom: 1.6em;
    padding-left: 1em;
}

.tiptap-preview code {
    font-family: monospace;
    font-size: 0.875em;
    background-color: var(--color-muted);
    padding: 0.2em 0.4em;
    border-radius: 0.25rem;
}

.tiptap-preview pre {
    font-family: monospace;
    font-size: 0.875em;
    background-color: var(--color-muted);
    padding: 1em;
    border-radius: 0.375rem;
    overflow-x: auto;
    margin-top: 1.6em;
    margin-bottom: 1.6em;
}

.tiptap-preview pre code {
    background-color: transparent;
    padding: 0;
    border-radius: 0;
    font-size: inherit;
    color: inherit;
}

.tiptap-preview hr {
    border: 0;
    border-top: 1px solid var(--color-border);
    margin-top: 3em;
    margin-bottom: 3em;
}

.tiptap-preview a {
    color: var(--color-primary);
    text-decoration: underline;
    font-weight: 500;
}

.tiptap-preview table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin-top: 1.6em;
    margin-bottom: 1.6em;
    overflow: hidden;
}

.tiptap-preview table td,
.tiptap-preview table th {
    min-width: 1em;
    border: 1px solid var(--color-border);
    padding: 0.5em 0.75em;
    vertical-align: top;
    box-sizing: border-box;
    position: relative;
}

.tiptap-preview table th {
    font-weight: 600;
    text-align: left;
    background-color: var(--color-muted);
}

/* Centered styling for media elements */
.tiptap-preview img,
.tiptap-preview iframe,
.tiptap-preview [data-youtube-video] {
    display: block;
    margin-left: auto;
    margin-right: auto;
    max-width: 100%;
}
.tiptap-preview [data-youtube-video] iframe {
    display: block;
    margin-left: auto;
    margin-right: auto;
}

/* Ensure horizontal scrolling for overflowing math elements */
.tiptap-preview .katex-display,
.tiptap-preview [data-type="block-math"],
.tiptap-preview [data-type="inline-math"] {
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}
</style>
