<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { upload, show } from '@/actions/App/Http/Controllers/FileController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';


defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'insert', src: string): void;
}>();

const file = ref<File | null>(null);

const http = useHttp({
    file: null as File | null,
});

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        file.value = target.files[0];
    } else {
        file.value = null;
    }
}

function handleUpload() {
    if (!file.value) {
        toast.error('Silakan pilih file terlebih dahulu.');

        return;
    }

    http.file = file.value;

    http.post(upload().url, {
        onSuccess: (response: any) => {
            if (response.success) {
                toast.success('Gambar berhasil diunggah.');
                emit('insert', show(response.file.id).url);
                closeModal();
            } else {
                toast.error(response.message || 'Gagal mengunggah gambar.');
            }
        },
        onError: () => {
            toast.error('Gagal mengunggah gambar. Pastikan ukuran file maksimal 2MB.');
        }
    });
}

function closeModal() {
    emit('update:open', false);
    file.value = null;
}
</script>

<template>
    <Dialog :open="open" @update:open="closeModal">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Unggah Gambar</DialogTitle>
                <DialogDescription>
                    Pilih gambar dari perangkat Anda. Ukuran maksimal 2MB.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label htmlFor="image-file" required>File Gambar</Label>
                    <Input id="image-file" type="file" accept="image/*" @change="handleFileChange" />
                </div>
                <div v-if="http.processing" class="text-sm text-muted-foreground">
                    Mengunggah...
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="closeModal" :disabled="http.processing">Batal</Button>
                <Button @click="handleUpload" :disabled="!file || http.processing">Unggah</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
