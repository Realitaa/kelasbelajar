<script setup lang="ts">
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import ColorPicker from '@/components/ui/color-picker/ColorPicker.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        open: boolean;
        modelValue: string;
        title?: string;
    }>(),
    {
        title: 'Warna Teks',
    },
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'update:modelValue', value: string): void;
    (e: 'confirm', value: string): void;
}>();

const localColor = ref('');

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            localColor.value = props.modelValue || '';
        }
    },
    { immediate: true },
);

function handleConfirm() {
    emit('update:modelValue', localColor.value);
    emit('confirm', localColor.value);
    emit('update:open', false);
}

function handleCancel() {
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-[400px]">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
            </DialogHeader>
            <div class="py-4">
                <ColorPicker v-model="localColor" />
            </div>
            <DialogFooter>
                <Button variant="outline" @click="handleCancel">Batal</Button>
                <Button @click="handleConfirm">Terapkan</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
