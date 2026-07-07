<script setup lang="ts">
import { component as Viewer } from 'v-viewer';
import 'viewerjs/dist/viewer.css';

defineProps<{
    src: string;
    alt?: string;
    title?: string;
}>();

const options = {
    inline: false,
    button: false,
    navbar: false,
    title: false,
    toolbar: false,
    tooltip: false,
    movable: true,
    zoomable: true,
    rotatable: false,
    scalable: true,
    transition: true,
    fullscreen: true,
    keyboard: false,
};
</script>

<template>
    <div class="zoomable-image-container my-4 flex justify-center">
        <viewer :options="options" class="inline-block">
            <img
                :src="src"
                :alt="alt || ''"
                :title="title || ''"
                class="max-h-[500px] w-auto max-w-full cursor-zoom-in rounded-lg transition-opacity hover:opacity-95"
            />
        </viewer>
    </div>
</template>
