<script setup lang="ts">
import { ref, computed } from 'vue';
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
    (e: 'insert', url: string): void;
}>();

const url = ref('');

const embedUrl = computed(() => {
    if (!url.value.trim()) {
        return '';
    }
    
    // Regex to capture YouTube video IDs
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
    const match = url.value.match(regExp);
    
    if (match && match[2].length === 11) {
        return `https://www.youtube.com/embed/${match[2]}`;
    }
    
    return '';
});

const isValidYoutube = computed(() => {
    return embedUrl.value !== '';
});

function handleInsert() {
    if (!isValidYoutube.value) {
        return;
    }

    emit('insert', url.value.trim());
    closeModal();
}

function closeModal() {
    emit('update:open', false);
    url.value = '';
}
</script>

<template>
    <Dialog :open="open" @update:open="closeModal">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Sisipkan Video YouTube</DialogTitle>
                <DialogDescription>
                    Masukkan URL video YouTube yang ingin Anda sisipkan ke dalam materi.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="youtube-url" required>Tautan YouTube</Label>
                    <Input
                        id="youtube-url"
                        placeholder="Contoh: https://www.youtube.com/watch?v=dQw4w9WgXcQ"
                        v-model="url"
                        class="w-full"
                    />
                </div>

                <div class="grid gap-2">
                    <Label>Pratinjau Video</Label>
                    <div class="w-full aspect-video rounded-md border border-input bg-muted/40 overflow-hidden flex items-center justify-center relative">
                        <iframe
                            v-if="isValidYoutube"
                            :src="embedUrl"
                            class="w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                        <span v-else class="text-xs text-muted-foreground italic">Masukkan tautan YouTube valid untuk pratinjau...</span>
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="closeModal">Batal</Button>
                <Button @click="handleInsert" :disabled="!isValidYoutube">Sisipkan</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
