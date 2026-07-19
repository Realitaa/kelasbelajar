<script setup lang="ts">
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
import MathField from '@/components/ui/math-field/MathField.vue';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

const props = withDefaults(
    defineProps<{
        open: boolean;
        initialLatex?: string;
        initialIsBlock?: boolean;
    }>(),
    {
        initialLatex: '',
        initialIsBlock: true,
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'insert', payload: { latex: string; isBlock: boolean }): void;
}>();

const latex = ref('');
const isBlock = ref('true'); // 'true' = Block Math, 'false' = Inline Math

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            latex.value = props.initialLatex;
            isBlock.value = props.initialIsBlock ? 'true' : 'false';
        }
    },
);

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

    if (typeof window !== 'undefined' && (window as any).mathVirtualKeyboard) {
        (window as any).mathVirtualKeyboard.hide();
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="closeModal">
        <DialogContent
            class="sm:max-w-[600px]"
            @interact-outside="(e) => e.preventDefault()"
        >
            <DialogHeader>
                <DialogTitle>Sisipkan Formula Matematika</DialogTitle>
                <DialogDescription>
                    Ketik formula secara visual atau gunakan sintaks LaTeX. Anda
                    dapat memilih tipe tampilan inline atau terpisah.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-6 py-4">
                <div class="grid gap-2">
                    <Label for="math-editor" required
                        >Visual Editor / LaTeX</Label
                    >
                    <!-- MathLive provides WYSIWYG editing, so preview is built-in -->
                    <MathField id="math-editor" v-model="latex" />
                    <div class="text-[0.8rem] text-muted-foreground">
                        Petunjuk: Ketik \ lalu nama simbol (contoh: \alpha) atau
                        gunakan tombol di layar.
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label required>Gaya Tampilan</Label>
                    <RadioGroup v-model="isBlock" class="flex gap-4">
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="true" id="math-block" />
                            <Label for="math-block" class="cursor-pointer"
                                >Terpisah (Centered / Baris Baru)</Label
                            >
                        </div>
                        <div class="flex items-center space-x-2">
                            <RadioGroupItem value="false" id="math-inline" />
                            <Label for="math-inline" class="cursor-pointer"
                                >Sejajar Teks (Inline)</Label
                            >
                        </div>
                    </RadioGroup>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="closeModal">Batal</Button>
                <Button @click="handleInsert" :disabled="!latex.trim()"
                    >Sisipkan</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
