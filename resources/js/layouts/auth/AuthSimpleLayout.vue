<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { GraduationCap } from '@lucide/vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-[#F8FAFF] p-6 transition-colors duration-300 md:p-10 dark:bg-[#0F172A]"
    >
        <!-- Background Ambient Orb -->
        <div
            class="pointer-events-none absolute top-1/2 left-1/2 -z-10 h-[500px] w-[500px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-500/10 blur-[100px] dark:bg-blue-600/15"
        ></div>

        <div
            class="w-full max-w-md rounded-3xl border border-gray-200 bg-white p-8 shadow-xl transition-all duration-300 dark:border-slate-800 dark:bg-slate-900/80"
        >
            <div class="flex flex-col gap-6">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-md shadow-blue-500/20 transition-transform hover:scale-105"
                        >
                            <GraduationCap class="h-7 w-7" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-1 text-center">
                        <h1
                            class="text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white"
                        >
                            {{ title }}
                        </h1>
                        <p
                            class="text-center text-sm text-gray-500 dark:text-gray-400"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
