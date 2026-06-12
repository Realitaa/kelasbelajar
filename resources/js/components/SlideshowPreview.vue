<script setup lang="ts">
import { ref, onUnmounted } from 'vue';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselPrevious,
    CarouselNext,
} from '@/components/ui/carousel';
import type { UnwrapRefCarouselApi } from '@/components/ui/carousel/interface';

defineProps<{
    images: { id?: number; url: string }[];
}>();

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

onUnmounted(() => {
    if (fadeTimeout) {
        clearTimeout(fadeTimeout);
    }
});
</script>

<template>
    <div class="my-4">
        <Carousel class="w-full max-w-2xl mx-auto" @init-api="setApi">
            <CarouselContent>
                <CarouselItem v-for="(img, index) in images" :key="index">
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
    </div>
</template>
