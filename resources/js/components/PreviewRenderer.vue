<script setup lang="ts">
import { generateHTML } from '@tiptap/html';
import StarterKit from '@tiptap/starter-kit';
import { computed } from 'vue';

const props = defineProps<{
    content: any;
}>();

const htmlContent = computed(() => {
    if (!props.content) {
        return '';
    }

    try {
        const json = typeof props.content === 'string' ? JSON.parse(props.content) : props.content;
        
        // Return empty string if the json structure is empty/blank
        if (!json || (Object.keys(json).length === 0 && json.constructor === Object)) {
            return '';
        }
        
        return generateHTML(json, [StarterKit]);
    } catch (error) {
        console.error('Failed to generate HTML from Tiptap JSON', error);

        return '';
    }
});
</script>

<template>
    <div class="tiptap-preview px-4 py-2" v-html="htmlContent"></div>
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
</style>
