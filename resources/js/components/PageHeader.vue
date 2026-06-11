<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

interface LinkProps {
    title?: string;
    label?: string;
    to?: string;
    onClick?: (payload: MouseEvent) => void;
    icon?: any;
    variant?: any;
    class?: string;
    [key: string]: any;
}

const props = defineProps<{
    title: string;
    description?: string;
    links?: LinkProps | LinkProps[];
}>();

const normalizedLinks = computed(() => {
    if (!props.links) {
        return [];
    }

    return Array.isArray(props.links) ? props.links : [props.links];
});
</script>

<template>
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex flex-col gap-1">
            <h1 class="text-3xl font-extrabold tracking-tight">{{ title }}</h1>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div
            v-if="normalizedLinks.length"
            class="flex flex-wrap items-center gap-2"
        >
            <template v-for="(link, index) in normalizedLinks" :key="index">
                <!-- If it has an onClick, render as a button executing that click -->
                <Button
                    v-if="link.onClick"
                    v-bind="link"
                    @click="link.onClick"
                    class="w-full gap-2 sm:w-auto"
                >
                    <component
                        v-if="link.icon"
                        :is="link.icon"
                        class="h-4 w-4"
                    />
                    {{ link.title || link.label }}
                </Button>
                <!-- If it has a 'to' prop, render as an Inertia link inside Button -->
                <Button
                    v-else-if="link.to"
                    as-child
                    v-bind="link"
                    class="w-full gap-2 sm:w-auto"
                >
                    <Link :href="link.to">
                        <component
                            v-if="link.icon"
                            :is="link.icon"
                            class="h-4 w-4"
                        />
                        {{ link.title || link.label }}
                    </Link>
                </Button>
            </template>
        </div>
    </div>
</template>
