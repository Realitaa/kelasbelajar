<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ModuleTree from '@/components/classroom/ModuleTree.vue';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/classrooms';
import type { Classroom } from '@/types/classroom';

defineProps<{
    classroom: Classroom;
}>();

defineOptions({
    layout: (props: { classroom: Classroom }) => ({
        breadcrumbs: [
            {
                title: 'Daftar Kelas',
                href: index(),
            },
            {
                title: props.classroom.title,
                href: '#',
            },
            {
                title: 'Manajemen Kelas',
                current: true,
            },
        ],
    }),
});
</script>

<template>
    <div>
        <Head title="Manajemen Kelas" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Dashboard Greeting Header -->
            <PageHeader
                :title="`Manajemen Kelas: ${classroom.title}`"
                description="Kelola modul pembelajaran, materi dan kuis yang tersedia di kelas ini."
            />

            <!-- Module Tree Management -->
            <ModuleTree :classroom="classroom" />
        </div>
    </div>
</template>
