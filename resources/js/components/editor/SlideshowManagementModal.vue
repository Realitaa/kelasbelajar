<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { GripVertical, X, Upload } from '@lucide/vue';
import { ref, watch } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { toast } from 'vue-sonner';
import { upload, remove, show } from '@/actions/App/Http/Controllers/FileController';
import { Button } from '@/components/ui/button';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
    initialImages: any[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'save', images: any[]): void;
}>();

const images = ref<any[]>([]);

watch(() => props.open, (newVal) => {
    if (newVal) {
        images.value = JSON.parse(JSON.stringify(props.initialImages || []));
    }
});

const http = useHttp({
    file: null as File | null,
});
const fileInput = ref<HTMLInputElement | null>(null);

function triggerFileInput() {
    fileInput.value?.click();
}

function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        uploadFile(target.files[0]);
    }

    // reset
    if (target) {
        target.value = '';
    }
}

function uploadFile(file: File) {
    http.file = file;

    http.post(upload().url, {
        onSuccess: (response: any) => {
            if (response.success) {
                images.value.push({
                    id: response.file.id,
                    url: show(response.file.id).url
                });
                toast.success('Gambar berhasil ditambahkan.');
            } else {
                toast.error(response.message || 'Gagal mengunggah gambar.');
            }
        },
        onError: () => {
            toast.error('Gagal mengunggah gambar. Pastikan ukuran file maksimal 2MB.');
        }
    });
}

function removeImage(index: number) {
    const img = images.value[index];
    images.value.splice(index, 1);
    
    // Call remove route
    if (img.id) {
        http.delete(remove(img.id).url, {
            onSuccess: () => {
                toast.success('Gambar dihapus dari server.');
            },
            onError: () => {
                // If it fails (e.g., already attached and not temporary), we ignore it or show error
            }
        });
    }
}

function handleSave() {
    emit('save', images.value);
}

function handleCancel() {
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="handleCancel">
        <!-- 80% width and height modal -->
        <DialogContent class="max-w-[80vw]! h-[80vh]! flex flex-col p-0">
            <DialogHeader class="p-6 border-b shrink-0">
                <DialogTitle>Kelola Slideshow</DialogTitle>
            </DialogHeader>

            <div class="flex-1 flex overflow-hidden">
                <!-- Left side: List & Upload -->
                <div class="w-1/3 border-r flex flex-col bg-muted/10">
                    <div class="p-4 border-b">
                        <Button class="w-full gap-2" @click="triggerFileInput" :disabled="http.processing">
                            <Upload class="w-4 h-4" /> Unggah Gambar
                        </Button>
                        <input type="file" class="hidden" ref="fileInput" accept="image/*" @change="handleFileChange" />
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-4">
                        <VueDraggable v-model="images" :animation="150" handle=".drag-handle" class="flex flex-col gap-2">
                            <div v-for="(img, index) in images" :key="img.id + '-' + index" class="flex items-center gap-3 p-2 bg-background border rounded-md shadow-sm">
                                <button class="drag-handle cursor-grab text-muted-foreground hover:text-foreground">
                                    <GripVertical class="w-5 h-5" />
                                </button>
                                <img :src="img.url" class="w-12 h-12 object-cover rounded" />
                                <div class="flex-1 text-sm truncate text-muted-foreground">Image {{ index + 1 }}</div>
                                <button class="text-muted-foreground hover:text-destructive p-1" @click="removeImage(index)">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </VueDraggable>
                        
                        <div v-if="images.length === 0" class="text-center text-muted-foreground text-sm py-8">
                            Belum ada gambar.
                        </div>
                    </div>
                </div>

                <!-- Right side: Preview -->
                <div class="w-2/3 p-6 flex flex-col items-center justify-center bg-muted/5">
                    <h3 class="text-sm font-medium text-muted-foreground mb-4">Pratinjau Slideshow</h3>
                    <div v-if="images.length === 0" class="flex items-center justify-center aspect-video w-11/12 border-2 border-dashed rounded-lg bg-background">
                        <span class="text-muted-foreground text-sm">Tidak ada gambar untuk ditampilkan.</span>
                    </div>
                    <Carousel v-else class="w-11/12 aspect-video">
                        <CarouselContent>
                            <CarouselItem v-for="(img, index) in images" :key="'preview-' + index">
                                <div class="p-1">
                                    <img :src="img.url" class="rounded-lg w-full aspect-video object-cover shadow-sm" />
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                        <CarouselPrevious />
                        <CarouselNext />
                    </Carousel>
                </div>
            </div>

            <DialogFooter class="p-6 border-t shrink-0 bg-background">
                <Button variant="outline" @click="handleCancel">Batal</Button>
                <Button @click="handleSave">Simpan</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
