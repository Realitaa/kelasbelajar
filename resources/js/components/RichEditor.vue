<script setup lang="ts">
import type { EditorToolbarItem } from '@nuxt/ui';
import UApp from '@nuxt/ui/components/App.vue';
import UEditor from '@nuxt/ui/components/Editor.vue';
import UEditorToolbar from '@nuxt/ui/components/EditorToolbar.vue';
import { computed } from 'vue';

const props = defineProps<{
    modelValue: any;
    placeholder?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const value = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
});

// Configure fixed toolbar items for formatting
const toolbarItems = [
    [
        { kind: 'undo', icon: 'i-lucide-undo', tooltip: { text: 'Batal' } },
        { kind: 'redo', icon: 'i-lucide-redo', tooltip: { text: 'Ulangi' } }
    ],
    [
        { kind: 'heading', level: 1, icon: 'i-lucide-heading-1', tooltip: { text: 'Judul 1' } },
        { kind: 'heading', level: 2, icon: 'i-lucide-heading-2', tooltip: { text: 'Judul 2' } },
        { kind: 'heading', level: 3, icon: 'i-lucide-heading-3', tooltip: { text: 'Judul 3' } },
        { kind: 'heading', level: 4, icon: 'i-lucide-heading-4', tooltip: { text: 'Judul 4' } },
        { kind: 'heading', level: 5, icon: 'i-lucide-heading-5', tooltip: { text: 'Judul 5' } },
        { kind: 'heading', level: 6, icon: 'i-lucide-heading-6', tooltip: { text: 'Judul 6' } }
    ],
    [
        { kind: 'mark', mark: 'bold', icon: 'i-lucide-bold', tooltip: { text: 'Tebal' } },
        { kind: 'mark', mark: 'italic', icon: 'i-lucide-italic', tooltip: { text: 'Miring' } },
        { kind: 'mark', mark: 'underline', icon: 'i-lucide-underline', tooltip: { text: 'Garis Bawah' } },
        { kind: 'mark', mark: 'strike', icon: 'i-lucide-strikethrough', tooltip: { text: 'Coret' } }
    ],
    [
        { kind: 'bulletList', icon: 'i-lucide-list', tooltip: { text: 'Daftar Bulat' } },
        { kind: 'orderedList', icon: 'i-lucide-list-ordered', tooltip: { text: 'Daftar Angka' } }
    ],
    [
        { kind: 'alignLeft', icon: 'i-lucide-align-left', tooltip: { text: 'Rata Kiri' } },
        { kind: 'alignCenter', icon: 'i-lucide-align-center', tooltip: { text: 'Rata Tengah' } },
        { kind: 'alignRight', icon: 'i-lucide-align-right', tooltip: { text: 'Rata Kanan' } },
        { kind: 'justify', icon: 'i-lucide-align-justify', tooltip: { text: 'Rata Kanan Kiri' } }
    ],
    [
        { kind: 'codeBlock', icon: 'i-lucide-code', tooltip: { text: 'Blok Kode' } },
        { kind: 'link', icon: 'i-lucide-link', tooltip: { text: 'Tautan' } }
    ]
] satisfies EditorToolbarItem[][];
</script>

<template>
    <UApp class="h-full w-full">
        <UEditor
            v-slot="{ editor }"
            v-model="value"
            :placeholder="placeholder || 'Mulai menulis...'"
            class="flex flex-col h-full border border-border rounded-lg overflow-hidden bg-background focus-within:border-primary/50 transition-colors"
        >
            <UEditorToolbar
                :editor="editor"
                :items="toolbarItems"
                class="border-b border-border px-3 py-1.5 bg-muted/30 overflow-x-auto shrink-0 mb-2"
            />
        </UEditor>
    </UApp>
</template>
