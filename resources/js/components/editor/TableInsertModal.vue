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

const props = withDefaults(
    defineProps<{
        open: boolean;
    }>(),
    {},
);

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (
        e: 'insert',
        payload: { rows: number; cols: number; withHeaderRow: boolean },
    ): void;
}>();

const rows = ref(3);
const cols = ref(3);
const withHeader = ref(true);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            rows.value = 3;
            cols.value = 3;
            withHeader.value = true;
        }
    },
);

function handleInsert() {
    emit('insert', {
        rows: Number(rows.value) || 3,
        cols: Number(cols.value) || 3,
        withHeaderRow: withHeader.value,
    });
    closeModal();
}

function closeModal() {
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="closeModal">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Sisipkan Tabel</DialogTitle>
                <DialogDescription>
                    Tentukan jumlah baris dan kolom untuk tabel Anda.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="table-rows" class="text-right">Baris</Label>
                    <input
                        id="table-rows"
                        type="number"
                        v-model="rows"
                        min="1"
                        max="100"
                        class="col-span-3 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="table-cols" class="text-right">Kolom</Label>
                    <input
                        id="table-cols"
                        type="number"
                        v-model="cols"
                        min="1"
                        max="20"
                        class="col-span-3 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                </div>
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="table-header" class="text-right"
                        >Gunakan Header</Label
                    >
                    <div class="col-span-3 flex items-center space-x-2">
                        <input
                            id="table-header"
                            type="checkbox"
                            v-model="withHeader"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <Label
                            for="table-header"
                            class="cursor-pointer text-sm font-normal"
                        >
                            Ya, tambahkan baris header
                        </Label>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="closeModal">Batal</Button>
                <Button @click="handleInsert">Sisipkan</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
