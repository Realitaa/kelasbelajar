<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ContentEditor from '@/components/classroom/ContentEditor.vue';
import ModuleTree from '@/components/classroom/ModuleTree.vue';
import PageHeader from '@/components/PageHeader.vue';
import { index } from '@/routes/classrooms';
import type { Classroom } from '@/types/classroom';
import QuizEditor from '@/components/classroom/QuizEditor.vue';

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

const isManage = ref(true);
const editorMode = ref<'content' | 'quiz' | null>(null);
const activeContent = ref<any>(null);
const contentEditorRef = ref<InstanceType<typeof ContentEditor> | null>(null);
const quizEditorRef = ref<InstanceType<typeof QuizEditor> | null>(null);

function handleEditContent(item: any) {
    if (editorMode.value === 'content' && contentEditorRef.value?.isDirty) {
        if (!confirm('Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin berpindah?')) {
            return;
        }
    } else if (editorMode.value === 'quiz' && quizEditorRef.value?.isDirty) {
        if (!confirm('Anda memiliki perubahan soal kuis yang belum disimpan. Apakah Anda yakin ingin berpindah?')) {
            return;
        }
    }

    activeContent.value = item;
    editorMode.value = 'content';
    isManage.value = false;
}

function handleEditQuiz(item: any) {
    if (editorMode.value === 'content' && contentEditorRef.value?.isDirty) {
        if (!confirm('Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin berpindah?')) {
            return;
        }
    } else if (editorMode.value === 'quiz' && quizEditorRef.value?.isDirty) {
        if (!confirm('Anda memiliki perubahan soal kuis yang belum disimpan. Apakah Anda yakin ingin berpindah?')) {
            return;
        }
    }

    activeContent.value = item;
    editorMode.value = 'quiz';
    isManage.value = false;
}

function handleReturnManage() {
    if (editorMode.value === 'content' && contentEditorRef.value?.isDirty) {
        if (!confirm('Anda memiliki perubahan yang belum disimpan. Apakah Anda yakin ingin keluar?')) {
            return;
        }
    } else if (editorMode.value === 'quiz' && quizEditorRef.value?.isDirty) {
        if (!confirm('Anda memiliki perubahan soal kuis yang belum disimpan. Apakah Anda yakin ingin keluar?')) {
            return;
        }
    }

    isManage.value = true;
    editorMode.value = null;
    activeContent.value = null;
}
</script>

<template>
    <div class="flex flex-col h-full">
        <Head title="Manajemen Kelas" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6 overflow-hidden">
            <!-- Dashboard Greeting Header -->
            <PageHeader :title="`Manajemen Kelas: ${classroom.title}`" class="shrink-0">
                <template #description>
                    <p v-if="isManage" class="text-md text-muted-foreground">
                        Kelola modul pembelajaran, materi dan kuis yang tersedia di kelas ini.
                    </p>
                    <p v-else-if="editorMode === 'content'" class="text-md text-blue-500">
                        Anda sedang membuka/mengubah konten pembelajaran <strong>{{ activeContent?.object?.title || 'Materi' }}</strong>. Jangan lupa menyimpan!
                    </p>
                    <p v-else-if="editorMode === 'quiz'" class="text-md text-blue-500">
                        Anda sedang mengelola soal kuis untuk <strong>{{ activeContent?.object?.title || 'Kuis' }}</strong>.
                    </p>
                </template>
            </PageHeader>

            <div class="flex flex-1 gap-4 overflow-hidden">
                <!-- Content Editor -->
                <div v-if="editorMode === 'content'" class="flex-1 w-2/3 flex overflow-hidden">
                    <ContentEditor 
                        ref="contentEditorRef"
                        :learning-content="activeContent?.object"
                        :classroom-slug="classroom.slug"
                        :is-educator="true"
                    />
                </div>

                <!-- Quiz Editor -->
                <div v-else-if="editorMode === 'quiz'" class="flex-1 w-2/3 flex overflow-hidden">
                    <QuizEditor 
                        ref="quizEditorRef"
                        :quiz="activeContent?.object"
                        :classroom-slug="classroom.slug"
                        :is-educator="true"
                    />
                </div>

                <!-- Module Tree Management -->
                <div class="flex shrink-0 transition-all duration-300 overflow-hidden" :class="isManage ? 'w-full' : 'w-1/3'">
                    <ModuleTree 
                        :classroom="classroom" 
                        :is-manage="isManage"
                        @edit-content="handleEditContent"
                        @edit-quiz="handleEditQuiz"
                        @return-manage="handleReturnManage"
                        class="w-full"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
