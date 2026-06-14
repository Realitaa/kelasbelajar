<script setup lang="ts">
import UApp from '@nuxt/ui/components/App.vue';
import UEditor from '@nuxt/ui/components/Editor.vue';
import UEditorToolbar from '@nuxt/ui/components/EditorToolbar.vue';
import Image from '@tiptap/extension-image';
import { Mathematics } from '@tiptap/extension-mathematics';
import Youtube from '@tiptap/extension-youtube';
import { computed, ref, provide, watch } from 'vue';
import 'katex/dist/katex.min.css';
import { cn } from '@/lib/utils';
import { SlideshowExtension } from './editor/extensions/SlideshowExtension';
import ImageUploadModal from './editor/ImageUploadModal.vue';
import MathInsertModal from './editor/MathInsertModal.vue';
import YoutubeInsertModal from './editor/YoutubeInsertModal.vue';

const props = defineProps<{
    class?: string;
    modelValue: any;
    placeholder?: string;
    isEducator?: boolean;
}>();

const emit = defineEmits(['update:modelValue']);

provide('isEducator', props.isEducator);

const value = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const isImageModalOpen = ref(false);
const isMathModalOpen = ref(false);
const isYoutubeModalOpen = ref(false);
const editorRef = ref<any>(null);

const mathToEdit = ref<{
    latex: string;
    isBlock: boolean;
    pos?: number;
} | null>(null);

watch(isMathModalOpen, (isOpen) => {
    if (!isOpen) {
        mathToEdit.value = null;
    }
});

const extensions = computed(() => {
    const exts: any[] = [
        Mathematics.configure({
            katexOptions: { throwOnError: false },
            inlineOptions: {
                onClick: (node: any, pos: number) => {
                    editorRef.value?.editor?.commands?.setNodeSelection?.(pos);
                },
            },
            blockOptions: {
                onClick: (node: any, pos: number) => {
                    editorRef.value?.editor?.commands?.setNodeSelection?.(pos);
                },
            },
        }),
        Youtube.configure({
            inline: false,
            width: 640,
            height: 480,
        }),
        Image.configure({
            inline: true,
            allowBase64: true,
        }),
    ];

    if (props.isEducator) {
        exts.push(SlideshowExtension);
    }

    return exts;
});

// Configure fixed toolbar items for formatting
const toolbarItems = computed(() => {
    const items: any[][] = [
        [
            { kind: 'undo', icon: 'i-lucide-undo', tooltip: { text: 'Batal' } },
            {
                kind: 'redo',
                icon: 'i-lucide-redo',
                tooltip: { text: 'Ulangi' },
            },
        ],
        [
            {
                kind: 'heading',
                level: 1,
                icon: 'i-lucide-heading-1',
                tooltip: { text: 'Judul 1' },
            },
            {
                kind: 'heading',
                level: 2,
                icon: 'i-lucide-heading-2',
                tooltip: { text: 'Judul 2' },
            },
            {
                kind: 'heading',
                level: 3,
                icon: 'i-lucide-heading-3',
                tooltip: { text: 'Judul 3' },
            },
            {
                kind: 'heading',
                level: 4,
                icon: 'i-lucide-heading-4',
                tooltip: { text: 'Judul 4' },
            },
            {
                kind: 'heading',
                level: 5,
                icon: 'i-lucide-heading-5',
                tooltip: { text: 'Judul 5' },
            },
            {
                kind: 'heading',
                level: 6,
                icon: 'i-lucide-heading-6',
                tooltip: { text: 'Judul 6' },
            },
        ],
        [
            {
                kind: 'mark',
                mark: 'bold',
                icon: 'i-lucide-bold',
                tooltip: { text: 'Tebal' },
            },
            {
                kind: 'mark',
                mark: 'italic',
                icon: 'i-lucide-italic',
                tooltip: { text: 'Miring' },
            },
            {
                kind: 'mark',
                mark: 'underline',
                icon: 'i-lucide-underline',
                tooltip: { text: 'Garis Bawah' },
            },
            {
                kind: 'mark',
                mark: 'strike',
                icon: 'i-lucide-strikethrough',
                tooltip: { text: 'Coret' },
            },
        ],
        [
            {
                kind: 'bulletList',
                icon: 'i-lucide-list',
                tooltip: { text: 'Daftar Bulat' },
            },
            {
                kind: 'orderedList',
                icon: 'i-lucide-list-ordered',
                tooltip: { text: 'Daftar Angka' },
            },
        ],
        [
            {
                kind: 'alignLeft',
                icon: 'i-lucide-align-left',
                tooltip: { text: 'Rata Kiri' },
            },
            {
                kind: 'alignCenter',
                icon: 'i-lucide-align-center',
                tooltip: { text: 'Rata Tengah' },
            },
            {
                kind: 'alignRight',
                icon: 'i-lucide-align-right',
                tooltip: { text: 'Rata Kanan' },
            },
            {
                kind: 'justify',
                icon: 'i-lucide-align-justify',
                tooltip: { text: 'Rata Kanan Kiri' },
            },
        ],
        [
            {
                kind: 'codeBlock',
                icon: 'i-lucide-code',
                tooltip: { text: 'Blok Kode' },
            },
            {
                kind: 'link',
                icon: 'i-lucide-link',
                tooltip: { text: 'Tautan' },
            },
        ],
        [
            {
                icon: 'i-lucide-sigma',
                tooltip: { text: 'Matematika' },
                onClick: () => {
                    isMathModalOpen.value = true;
                },
            },
            {
                icon: 'i-lucide-youtube',
                tooltip: { text: 'Youtube Video' },
                onClick: () => {
                    isYoutubeModalOpen.value = true;
                },
            },
            {
                icon: 'i-lucide-image',
                tooltip: { text: 'Gambar' },
                onClick: () => {
                    isImageModalOpen.value = true;
                },
            },
        ],
    ];

    if (props.isEducator) {
        items[6].push({
            icon: 'vaadin:presentation',
            tooltip: { text: 'Slideshow' },
            onClick: () => {
                if (editorRef.value?.editor) {
                    editorRef.value.editor.commands.insertContent({
                        type: 'slideshow',
                        attrs: { images: [] },
                    });
                }
            },
        });
    }

    return items;
});

const mathBubbleItems = computed(() => [
    [
        {
            icon: 'i-lucide-pencil',
            tooltip: { text: 'Ubah Formula' },
            onClick: () => {
                const editor = editorRef.value?.editor;

                if (!editor) {
                    return;
                }

                const { state } = editor;
                const { selection } = state;
                const node = selection.node;

                if (
                    node &&
                    (node.type.name === 'inlineMath' ||
                        node.type.name === 'blockMath')
                ) {
                    mathToEdit.value = {
                        latex: node.attrs.latex || '',
                        isBlock: node.type.name === 'blockMath',
                        pos: selection.from,
                    };
                    isMathModalOpen.value = true;
                }
            },
        },
        {
            icon: 'i-lucide-trash-2',
            tooltip: { text: 'Hapus' },
            onClick: () => {
                const editor = editorRef.value?.editor;

                if (!editor) {
                    return;
                }

                const { state } = editor;
                const { selection } = state;
                const node = selection.node;

                if (node && node.type.name === 'inlineMath') {
                    editor.commands.deleteInlineMath({ pos: selection.from });
                } else if (node && node.type.name === 'blockMath') {
                    editor.commands.deleteBlockMath({ pos: selection.from });
                }
            },
        },
    ],
]);

function shouldShowMathBubble({ editor }: { editor: any }) {
    return editor.isActive('inlineMath') || editor.isActive('blockMath');
}

function handleImageInsert(src: string) {
    if (editorRef.value?.editor) {
        editorRef.value.editor.commands.setImage({ src });
    }
}

function handleMathInsert(payload: { latex: string; isBlock: boolean }) {
    const editor = editorRef.value?.editor;

    if (!editor) {
        return;
    }

    if (mathToEdit.value && typeof mathToEdit.value.pos === 'number') {
        const { pos, isBlock: oldIsBlock } = mathToEdit.value;

        if (payload.isBlock !== oldIsBlock) {
            if (oldIsBlock) {
                editor.commands.deleteBlockMath({ pos });
            } else {
                editor.commands.deleteInlineMath({ pos });
            }

            if (payload.isBlock) {
                editor.commands.insertBlockMath({ latex: payload.latex, pos });
            } else {
                editor.commands.insertInlineMath({ latex: payload.latex, pos });
            }
        } else {
            if (payload.isBlock) {
                editor.commands.updateBlockMath({ latex: payload.latex, pos });
            } else {
                editor.commands.updateInlineMath({ latex: payload.latex, pos });
            }
        }
    } else {
        if (payload.isBlock) {
            editor.commands.insertBlockMath({ latex: payload.latex });
        } else {
            editor.commands.insertInlineMath({ latex: payload.latex });
        }
    }
}

function handleYoutubeInsert(url: string) {
    if (editorRef.value?.editor) {
        editorRef.value.editor.commands.setYoutubeVideo({ src: url });
    }
}
</script>

<template>
    <UApp class="h-full w-full">
        <UEditor
            ref="editorRef"
            v-slot="{ editor }"
            v-model="value"
            :placeholder="placeholder || 'Mulai menulis...'"
            :extensions="extensions"
            :class="
                cn(
                    'flex h-full flex-col overflow-hidden rounded-lg border border-border bg-background transition-colors focus-within:border-primary/50',
                    props.class,
                )
            "
        >
            <UEditorToolbar
                :editor="editor"
                :items="toolbarItems"
                class="mb-2 shrink-0 overflow-x-auto border-b border-border bg-muted/30 px-3 py-1.5"
            />

            <UEditorToolbar
                :editor="editor"
                layout="bubble"
                :items="mathBubbleItems"
                :should-show="shouldShowMathBubble"
            />
        </UEditor>

        <ImageUploadModal
            v-model:open="isImageModalOpen"
            @insert="handleImageInsert"
        />
        <MathInsertModal
            v-model:open="isMathModalOpen"
            :initial-latex="mathToEdit?.latex || ''"
            :initial-is-block="mathToEdit ? mathToEdit.isBlock : true"
            @insert="handleMathInsert"
        />
        <YoutubeInsertModal
            v-model:open="isYoutubeModalOpen"
            @insert="handleYoutubeInsert"
        />
    </UApp>
</template>
