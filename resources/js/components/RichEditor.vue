<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import UApp from '@nuxt/ui/components/App.vue';
import UEditor from '@nuxt/ui/components/Editor.vue';
import UEditorToolbar from '@nuxt/ui/components/EditorToolbar.vue';
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
import { computed, ref, provide, watch } from 'vue';
import 'katex/dist/katex.min.css';
import { toast } from 'vue-sonner';
import { upload, show } from '@/actions/App/Http/Controllers/FileController';
import { Button } from '@/components/ui/button';
import ColorPicker from '@/components/ui/color-picker/ColorPicker.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { SlideshowExtension } from './editor/extensions/SlideshowExtension';
import ImageUploadModal from './editor/ImageUploadModal.vue';
import MathEditorModal from './editor/MathEditorModal.vue';
import TableInsertModal from './editor/TableInsertModal.vue';
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
const isTableModalOpen = ref(false);
const isColorPickerOpen = ref(false);
const selectedTextColor = ref('');
const editorRef = ref<any>(null);

function applyTextColor() {
    if (editorRef.value?.editor) {
        if (selectedTextColor.value) {
            editorRef.value.editor.commands.setColor(selectedTextColor.value);
        } else {
            editorRef.value.editor.commands.unsetColor();
        }
    }

    isColorPickerOpen.value = false;
}

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
            allowBase64: false,
        }),
        Table.configure({
            resizable: true,
        }),
        TableRow,
        TableHeader,
        TableCell,
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        TextStyle,
        Color,
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
                icon: 'i-lucide-type',
                tooltip: { text: 'Format Teks & Perataan' },
                items: [
                    [
                        {
                            kind: 'heading',
                            level: 1,
                            icon: 'i-lucide-heading-1',
                            label: 'Judul 1',
                        },
                        {
                            kind: 'heading',
                            level: 2,
                            icon: 'i-lucide-heading-2',
                            label: 'Judul 2',
                        },
                        {
                            kind: 'heading',
                            level: 3,
                            icon: 'i-lucide-heading-3',
                            label: 'Judul 3',
                        },
                        {
                            kind: 'heading',
                            level: 4,
                            icon: 'i-lucide-heading-4',
                            label: 'Judul 4',
                        },
                        {
                            kind: 'heading',
                            level: 5,
                            icon: 'i-lucide-heading-5',
                            label: 'Judul 5',
                        },
                        {
                            kind: 'heading',
                            level: 6,
                            icon: 'i-lucide-heading-6',
                            label: 'Judul 6',
                        },
                        {
                            kind: 'paragraph',
                            icon: 'i-lucide-pilcrow',
                            label: 'Paragraf',
                        },
                        {
                            icon: 'i-lucide-palette',
                            label: 'Warna Teks',
                            onClick: () => {
                                selectedTextColor.value = editorRef.value?.editor?.getAttributes('textStyle').color || '';
                                isColorPickerOpen.value = true;
                            },
                        },
                    ],
                    [
                        {
                            kind: 'textAlign',
                            align: 'left',
                            icon: 'i-lucide-align-left',
                            label: 'Rata Kiri',
                        },
                        {
                            kind: 'textAlign',
                            align: 'center',
                            icon: 'i-lucide-align-center',
                            label: 'Rata Tengah',
                        },
                        {
                            kind: 'textAlign',
                            align: 'right',
                            icon: 'i-lucide-align-right',
                            label: 'Rata Kanan',
                        },
                        {
                            kind: 'textAlign',
                            align: 'justify',
                            icon: 'i-lucide-align-justify',
                            label: 'Rata Kanan Kiri',
                        },
                    ],
                ],
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
                icon: 'i-lucide-table',
                tooltip: { text: 'Tabel' },
                onClick: () => {
                    isTableModalOpen.value = true;
                },
            },
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
        items[items.length - 1].push({
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

const tableBubbleItems = computed(() => [
    [
        {
            icon: 'i-lucide-arrow-up-to-line',
            tooltip: { text: 'Tambah Baris Sebelum' },
            onClick: () => editorRef.value?.editor?.commands.addRowBefore(),
        },
        {
            icon: 'i-lucide-arrow-down-to-line',
            tooltip: { text: 'Tambah Baris Sesudah' },
            onClick: () => editorRef.value?.editor?.commands.addRowAfter(),
        },
        {
            icon: 'i-lucide-trash',
            tooltip: { text: 'Hapus Baris' },
            onClick: () => editorRef.value?.editor?.commands.deleteRow(),
        },
    ],
    [
        {
            icon: 'i-lucide-arrow-left-to-line',
            tooltip: { text: 'Tambah Kolom Sebelum' },
            onClick: () => editorRef.value?.editor?.commands.addColumnBefore(),
        },
        {
            icon: 'i-lucide-arrow-right-to-line',
            tooltip: { text: 'Tambah Kolom Sesudah' },
            onClick: () => editorRef.value?.editor?.commands.addColumnAfter(),
        },
        {
            icon: 'i-lucide-trash-2',
            tooltip: { text: 'Hapus Kolom' },
            onClick: () => editorRef.value?.editor?.commands.deleteColumn(),
        },
    ],
    [
        {
            icon: 'i-lucide-combine',
            tooltip: { text: 'Gabung Sel' },
            onClick: () => editorRef.value?.editor?.commands.mergeCells(),
        },
        {
            icon: 'i-lucide-split-square-horizontal',
            tooltip: { text: 'Pisah Sel' },
            onClick: () => editorRef.value?.editor?.commands.splitCell(),
        },
    ],
    [
        {
            icon: 'i-lucide-table-properties',
            tooltip: { text: 'Hapus Tabel' },
            onClick: () => editorRef.value?.editor?.commands.deleteTable(),
        },
    ],
]);

function shouldShowTableBubble({ editor }: { editor: any }) {
    return editor.isActive('table');
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

function handleTableInsert(payload: {
    rows: number;
    cols: number;
    withHeaderRow: boolean;
}) {
    if (editorRef.value?.editor) {
        editorRef.value.editor.commands.insertTable(payload);
    }
}

const uploadHttp = useHttp({
    file: null as File | null,
});

function uploadFile(file: File, view: any, pos?: number) {
    if (uploadHttp.processing) {
        toast.error(
            'Ada pengunggahan gambar yang sedang berjalan. Silakan tunggu.',
        );

        return;
    }

    uploadHttp.file = file;

    const toastId = toast.loading('Mengunggah gambar...');

    uploadHttp.post(upload().url, {
        onSuccess: (response: any) => {
            if (response.success) {
                toast.success('Gambar berhasil diunggah.', { id: toastId });
                const imageUrl = show(response.file.id).url;

                const { schema } = view.state;
                const node = schema.nodes.image.create({ src: imageUrl });

                let transaction;

                if (typeof pos === 'number') {
                    transaction = view.state.tr.insert(pos, node);
                } else {
                    transaction = view.state.tr.replaceSelectionWith(node);
                }

                view.dispatch(transaction);
            } else {
                toast.error(response.message || 'Gagal mengunggah gambar.', {
                    id: toastId,
                });
            }
        },
        onError: () => {
            toast.error(
                'Gagal mengunggah gambar. Pastikan ukuran file maksimal 2MB.',
                { id: toastId },
            );
        },
    });
}

function handlePasteEvent(view: any, event: ClipboardEvent) {
    const items = Array.from(event.clipboardData?.items || []);
    const imageItems = items.filter((item) => item.type.startsWith('image/'));

    if (imageItems.length > 0) {
        event.preventDefault();

        imageItems.forEach((item) => {
            const file = item.getAsFile();

            if (file) {
                uploadFile(file, view);
            }
        });

        return true;
    }

    return false;
}

function handleDropEvent(view: any, event: DragEvent) {
    const files = Array.from(event.dataTransfer?.files || []);
    const imageFiles = files.filter((file) => file.type.startsWith('image/'));

    if (imageFiles.length > 0) {
        event.preventDefault();

        const coordinates = view.posAtCoords({
            left: event.clientX,
            top: event.clientY,
        });
        const pos = coordinates ? coordinates.pos : view.state.selection.from;

        imageFiles.forEach((file) => {
            uploadFile(file, view, pos);
        });

        return true;
    }

    return false;
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
                    'flex h-full max-h-[95vh] flex-col overflow-hidden rounded-lg border border-border bg-background transition-colors focus-within:border-primary/50',
                    props.class,
                )
            "
            :ui="{
                content: 'relative size-full flex-1 max-h-[95vh] overflow-auto',
            }"
            :editor-props="{
                handlePaste: handlePasteEvent,
                handleDrop: handleDropEvent,
            }"
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
            <UEditorToolbar
                :editor="editor"
                layout="bubble"
                :items="tableBubbleItems"
                :should-show="shouldShowTableBubble"
            />
        </UEditor>

        <ImageUploadModal
            v-model:open="isImageModalOpen"
            @insert="handleImageInsert"
        />
        <TableInsertModal
            v-model:open="isTableModalOpen"
            @insert="handleTableInsert"
        />
        <MathEditorModal
            v-model:open="isMathModalOpen"
            :initial-latex="mathToEdit?.latex || ''"
            :initial-is-block="mathToEdit ? mathToEdit.isBlock : true"
            @insert="handleMathInsert"
        />
        <YoutubeInsertModal
            v-model:open="isYoutubeModalOpen"
            @insert="handleYoutubeInsert"
        />

        <Dialog :open="isColorPickerOpen" @update:open="isColorPickerOpen = $event">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Warna Teks</DialogTitle>
                </DialogHeader>
                <div class="py-4">
                    <ColorPicker v-model="selectedTextColor" />
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="isColorPickerOpen = false">Batal</Button>
                    <Button @click="applyTextColor">Terapkan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </UApp>
</template>

<style>
.ProseMirror table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin-top: 1.6em;
    margin-bottom: 1.6em;
    overflow: hidden;
}

.ProseMirror table td,
.ProseMirror table th {
    min-width: 1em;
    border: 1px solid var(--color-border);
    padding: 0.5em 0.75em;
    vertical-align: top;
    box-sizing: border-box;
    position: relative;
}

.ProseMirror table th {
    font-weight: 600;
    text-align: left;
    background-color: var(--color-muted);
}

.ProseMirror table .selectedCell:after {
    z-index: 2;
    position: absolute;
    content: '';
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    background: rgba(200, 200, 255, 0.4);
    pointer-events: none;
}

.ProseMirror table .column-resize-handle {
    position: absolute;
    right: -2px;
    top: 0;
    bottom: 0;
    width: 4px;
    z-index: 20;
    background-color: var(--color-primary);
    pointer-events: none;
}

/* Centered styling for media elements in editor */
.ProseMirror img,
.ProseMirror iframe,
.ProseMirror [data-youtube-video] {
    display: block;
    margin-left: auto;
    margin-right: auto;
    max-width: 100%;
}
.ProseMirror [data-youtube-video] iframe {
    display: block;
    margin-left: auto;
    margin-right: auto;
}

/* Ensure horizontal scrolling for overflowing math elements */
.ProseMirror .katex-display,
.ProseMirror [data-type="block-math"],
.ProseMirror [data-type="inline-math"] {
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}
</style>
