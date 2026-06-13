<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Compass, Search } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import ClassroomDiscoveryController from '@/actions/App/Http/Controllers/ClassroomDiscoveryController';
import ClassroomCard from '@/components/classroom/ClassroomCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Input } from '@/components/ui/input';
import type { Classroom } from '@/types';

const props = defineProps<{
    classrooms: {
        data: Classroom[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        query: string | null;
    };
}>();

const searchQuery = ref(props.filters.query || '');

const performSearch = useDebounceFn((queryVal: string) => {
    router.get(
        ClassroomDiscoveryController.index.url(),
        { query: queryVal },
        {
            preserveState: true,
            replace: true,
        }
    );
}, 300);

watch(searchQuery, (newVal) => {
    performSearch(newVal);
});

const enrollInClassroom = (classroom: Classroom) => {
    router.post(
        ClassroomDiscoveryController.enroll.url(classroom.slug),
        {},
        {
            preserveScroll: true,
        }
    );
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Cari Kelas',
                href: '#',
            },
        ],
    },
});
</script>

<template>
    <div>
        <Head title="Cari Kelas Baru" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Dashboard Greeting Header -->
            <PageHeader
                title="Cari Kelas Baru"
                description="Temukan kelas yang menarik dan bergabunglah untuk mulai belajar bersama."
            />

            <!-- Search Area -->
            <div class="relative w-full max-w-sm animate-in slide-in-from-top-4 duration-300">
                <Search class="absolute top-2.5 left-3 h-4 w-4 text-muted-foreground" />
                <Input
                    type="text"
                    placeholder="Cari kelas, pengajar, atau kode kelas..."
                    v-model="searchQuery"
                    class="pl-9 pr-4"
                />
            </div>

            <!-- Main Content Grid / Empty State -->
            <div
                v-if="classrooms.data.length === 0"
                class="flex animate-in flex-col items-center justify-center rounded-xl border border-dashed bg-card/50 p-16 text-center shadow-xs backdrop-blur-xs duration-500 fade-in"
            >
                <div class="rounded-full bg-primary/15 p-5 text-primary">
                    <Compass class="h-12 w-12" />
                </div>
                <h3 class="mt-6 text-xl font-bold tracking-tight">
                    Kelas Tidak Ditemukan
                </h3>
                <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                    Tidak ada kelas publik yang sesuai dengan pencarian Anda. Silakan coba kata kunci lain atau periksa kembali kode kelas Anda.
                </p>
            </div>

            <div v-else class="space-y-6 animate-in fade-in duration-300">
                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <ClassroomCard
                        v-for="classroom in classrooms.data"
                        :key="classroom.id"
                        :classroom="classroom"
                        :show-enroll-action="true"
                        @enroll="enrollInClassroom"
                    />
                </div>

                <Pagination
                    :total="classrooms.total"
                    :current-page="classrooms.current_page"
                    :per-page="classrooms.per_page"
                />
            </div>
        </div>
    </div>
</template>
