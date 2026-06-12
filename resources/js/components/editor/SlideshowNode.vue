<script setup lang="ts">
import { Edit2 } from '@lucide/vue';
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
import { ref, inject, onUnmounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import type { UnwrapRefCarouselApi } from '@/components/ui/carousel/interface';
import SlideshowManagementModal from './SlideshowManagementModal.vue';

const props = defineProps(nodeViewProps);

const isEducator = inject('isEducator', false);

const isModalOpen = ref(false);

const api = ref<UnwrapRefCarouselApi>();
const current = ref(1);
const count = ref(0);
const showPageIndicator = ref(false);
let fadeTimeout: any = null;

function triggerPageIndicator() {
    showPageIndicator.value = true;

    if (fadeTimeout) {
        clearTimeout(fadeTimeout);
    }

    fadeTimeout = setTimeout(() => {
        showPageIndicator.value = false;
    }, 3000);
}

function setApi(val: any) {
    api.value = val;

    if (!api.value) {
        return;
    }

    count.value = api.value.scrollSnapList().length;
    current.value = api.value.selectedScrollSnap() + 1;
    
    triggerPageIndicator();

    api.value.on('select', () => {
        if (!api.value) {
            return;
        }

        current.value = api.value.selectedScrollSnap() + 1;
        triggerPageIndicator();
    });
}

watch(() => props.node.attrs.images, () => {
    if (api.value) {
        setTimeout(() => {
            if (!api.value) {
                return;
            }

            count.value = api.value.scrollSnapList().length;
            current.value = Math.min(api.value.selectedScrollSnap() + 1, count.value);
            triggerPageIndicator();
        }, 100);
    }
}, { deep: true });

onUnmounted(() => {
    if (fadeTimeout) {
        clearTimeout(fadeTimeout);
    }
});

function openModal() {
    isModalOpen.value = true;
}

function handleSave(images: any[]) {
    props.updateAttributes({
        images,
    });
    isModalOpen.value = false;
}
</script>

<template>
    <NodeViewWrapper class="relative slideshow-node my-4 rounded-lg border bg-muted/30 p-4 group" data-drag-handle>
        <!-- Editor Controls overlay -->
        <div v-if="isEducator" class="absolute top-2 right-2 z-10 opacity-0 transition-opacity hover:opacity-100 group-hover:opacity-100">
            <Button size="sm" variant="secondary" class="gap-1 shadow-sm" @click="openModal">
                <Edit2 class="w-4 h-4" /> Edit Slideshow
            </Button>
        </div>

        <div v-if="!props.node.attrs.images || props.node.attrs.images.length === 0" class="flex items-center justify-center h-48 border-2 border-dashed rounded-md bg-muted/50">
            <span class="text-sm text-muted-foreground">Slideshow kosong. {{ isEducator ? 'Klik Edit Slideshow untuk menambahkan gambar.' : '' }}</span>
        </div>
        
        <Carousel v-else class="w-full max-w-2xl mx-auto" @init-api="setApi">
            <CarouselContent>
                <CarouselItem v-for="(img, index) in props.node.attrs.images" :key="index">
                    <div class="p-1">
                        <img :src="img.url" class="rounded-md w-full h-[400px] object-cover" alt="Slideshow Image" />
                    </div>
                </CarouselItem>
            </CarouselContent>

            <!-- Slide Indicator bottom left -->
            <div 
                class="absolute bottom-4 left-4 z-10 bg-black/75 backdrop-blur-md text-white px-3 py-1.5 rounded-lg text-xs font-semibold tracking-wider transition-all duration-300 pointer-events-none select-none"
                :class="showPageIndicator ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'"
            >
                {{ current }} / {{ count }}
            </div>

            <CarouselPrevious />
            <CarouselNext />
        </Carousel>

        <SlideshowManagementModal 
            v-if="isEducator" 
            :open="isModalOpen" 
            :initial-images="props.node.attrs.images"
            @update:open="isModalOpen = $event"
            @save="handleSave"
        />
    </NodeViewWrapper>
</template>
