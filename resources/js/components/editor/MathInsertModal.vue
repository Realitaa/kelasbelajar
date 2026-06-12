<script setup lang="ts">
import katex from 'katex';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';

const props = withDefaults(defineProps<{
    open: boolean;
    initialLatex?: string;
    initialIsBlock?: boolean;
}>(), {
    initialLatex: '',
    initialIsBlock: true,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'insert', payload: { latex: string; isBlock: boolean }): void;
}>();

const latex = ref('');
const isBlock = ref('true'); // 'true' = Block Math, 'false' = Inline Math

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        latex.value = props.initialLatex;
        isBlock.value = props.initialIsBlock ? 'true' : 'false';
    }
});
const previewHtml = ref('');
const previewError = ref('');

function updatePreview() {
    if (!latex.value.trim()) {
        previewHtml.value = '';
        previewError.value = '';

        return;
    }

    try {
        previewHtml.value = katex.renderToString(latex.value, {
            displayMode: isBlock.value === 'true',
            throwOnError: true,
        });
        previewError.value = '';
    } catch (error: any) {
        previewError.value = error.message || 'Error rendering LaTeX';

        // fallback render without throwing so they can see partial results
        try {
            previewHtml.value = katex.renderToString(latex.value, {
                displayMode: isBlock.value === 'true',
                throwOnError: false,
            });
        } catch {
            // do nothing
        }
    }
}

watch([latex, isBlock], () => {
    updatePreview();
});

function handleInsert() {
    if (!latex.value.trim()) {
return;
}

    emit('insert', {
        latex: latex.value,
        isBlock: isBlock.value === 'true',
    });
    closeModal();
}

function closeModal() {
    emit('update:open', false);
    latex.value = '';
    isBlock.value = 'true';
    previewHtml.value = '';
    previewError.value = '';
}
</script>

<template>
    <Dialog :open="open" @update:open="closeModal">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Sisipkan Formula Matematika</DialogTitle>
                <DialogDescription>
                    Tulis formula LaTeX di bawah ini. Anda dapat memilih tipe tampilan inline atau terpisah.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="latex-code">Formula LaTeX</Label>
                    <Textarea
                        id="latex-code"
                        placeholder="Contoh: \int_{a}^{b} x^2 \, dx = \frac{b^3 - a^3}{3}"
                        v-model="latex"
                        rows="3"
                        class="font-mono"
                    />
                </div>

                <div class="grid gap-2">
                    <Label>Gaya Tampilan</Label>
                    <RadioGroup v-model="isBlock" class="flex gap-4">
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="true" id="math-block" />
                            <Label for="math-block" class="cursor-pointer">Terpisah (Centered / Baris Baru)</Label>
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="false" id="math-inline" />
                            <Label for="math-inline" class="cursor-pointer">Sejajar Teks (Inline)</Label>
                        </div>
                    </RadioGroup>
                </div>

                <div class="grid gap-2">
                    <Label>Pratinjau</Label>
                    <div class="min-h-[80px] w-full rounded-md border border-input bg-muted/40 p-4 flex items-center justify-center overflow-x-auto">
                        <div v-if="previewHtml" v-html="previewHtml"></div>
                        <span v-else class="text-xs text-muted-foreground italic">Formula matematika akan dirender di sini...</span>
                    </div>
                    <div v-if="previewError" class="text-xs text-destructive mt-1 font-mono break-all">
                        {{ previewError }}
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="closeModal">Batal</Button>
                <Button @click="handleInsert" :disabled="!latex.trim() || !!previewError">Sisipkan</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
