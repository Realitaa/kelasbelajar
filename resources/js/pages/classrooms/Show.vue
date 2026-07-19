<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import {
    Play,
    Lock,
    CheckCircle2,
    Award,
    BookOpen,
    MessageSquare,
} from '@lucide/vue';
import { computed } from 'vue';
import PreviewRenderer from '@/components/PreviewRenderer.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { show } from '@/routes/classrooms';
import { index as discussionIndex } from '@/routes/classrooms/discussion';
import { start } from '@/routes/classrooms/quizzes';
import { show as showSubmission } from '@/routes/quizzes/submissions';
import type { Classroom, QuizSubmission, Module, LessonObject } from '@/types';

const props = defineProps<{
    classroom: Classroom & { modules: Module[] };
    activeObject: LessonObject | null;
    activeQuizSubmissions: QuizSubmission[];
}>();

const activeModule = computed(() => {
    const obj = props.activeObject;

    if (!obj) {
        return null;
    }

    return props.classroom.modules.find((m) => m.id === obj.module_id) || null;
});

defineOptions({
    layout: (props: any) => ({
        breadcrumbs: [
            {
                title: 'Kelas Saya',
                href: '/classrooms',
            },
            {
                title: props.classroom?.title || 'Detail Kelas',
                current: true,
            },
        ],
    }),
});

function selectObject(item: LessonObject) {
    if (!item.can_access) {
        return;
    }

    router.get(
        show.url(props.classroom.slug),
        { object_id: item.id },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

function formatDate(dateString: string) {
    const date = new Date(dateString);

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function countTotalLessons() {
    let count = 0;
    props.classroom.modules.forEach((m) => {
        count += m.objects?.length || 0;
    });

    return count;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50/50 dark:bg-slate-900/20">
        <Head :title="classroom.title" />

        <div
            class="mx-auto flex w-full max-w-[1200px] flex-1 flex-col gap-6 px-4 py-6 md:px-6"
        >
            <!-- Top Hero Header Card -->
            <div
                class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg md:p-8 dark:bg-slate-900"
            >
                <div
                    class="relative z-10 flex flex-col justify-between gap-6 md:flex-row md:items-center"
                >
                    <div class="max-w-3xl space-y-3">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold backdrop-blur-xs select-none"
                        >
                            <BookOpen class="size-3.5" />
                            {{ countTotalLessons() }} Objek Pembelajaran
                        </span>

                        <h1
                            class="text-2xl font-extrabold tracking-tight md:text-3xl"
                        >
                            {{ classroom.title }}
                        </h1>

                        <p
                            class="max-w-2xl text-sm leading-relaxed md:text-base"
                        >
                            {{
                                classroom.description ||
                                'Selamat datang di kelas! Ikuti setiap modul secara bertahap dan selesaikan kuis untuk melanjutkan ke materi berikutnya.'
                            }}
                        </p>
                    </div>

                    <div class="shrink-0">
                        <Link
                            :href="discussionIndex(classroom.slug).url"
                            class="inline-flex"
                        >
                            <Button
                                variant="secondary"
                                class="cursor-pointer gap-2 font-semibold shadow-xs backdrop-blur-xs transition-all"
                            >
                                <MessageSquare class="size-4" />
                                <span>Forum Diskusi</span>
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Content and Sidebar Grid -->
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                <!-- LEFT: Main Learning Content or Quiz Panel -->
                <main class="space-y-6 lg:col-span-7 xl:col-span-8">
                    <!-- If no object in the classroom -->
                    <Card
                        v-if="!activeObject"
                        class="flex flex-col items-center justify-center border-dashed p-12 text-center"
                    >
                        <div
                            class="mb-4 rounded-full bg-slate-100 p-4 text-slate-400 dark:bg-slate-800"
                        >
                            <BookOpen class="size-10" />
                        </div>
                        <h3
                            class="text-lg font-bold text-slate-800 dark:text-slate-200"
                        >
                            Belum Ada Materi
                        </h3>
                        <p class="mt-1 max-w-xs text-sm text-slate-500">
                            Kelas ini baru saja dibuat dan belum memiliki modul
                            atau materi belajar.
                        </p>
                    </Card>

                    <!-- Learning Content View -->
                    <Card
                        v-else-if="
                            activeObject.object_type.includes('LearningContent')
                        "
                        class="overflow-hidden border border-slate-200 bg-white shadow-sm transition-all duration-300 dark:border-slate-800 dark:bg-slate-950"
                    >
                        <!-- Content Header -->
                        <div
                            class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 p-5 md:p-6 dark:border-slate-900 dark:bg-slate-900/30"
                        >
                            <div class="space-y-1">
                                <span
                                    class="text-xs font-bold tracking-wider text-blue-600 uppercase dark:text-blue-400"
                                    >Materi Belajar</span
                                >
                                <h2
                                    class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl dark:text-slate-100"
                                    :style="
                                        activeModule?.color
                                            ? { color: activeModule.color }
                                            : {}
                                    "
                                >
                                    {{ activeObject.object.title }}
                                </h2>
                            </div>
                        </div>

                        <!-- Content Render Area -->
                        <div
                            class="prose prose-slate dark:prose-invert max-w-none p-6 md:p-8"
                        >
                            <PreviewRenderer
                                :content="activeObject.object.content"
                            />
                        </div>
                    </Card>

                    <!-- Quiz Overview Page -->
                    <Card
                        v-else-if="activeObject.object_type.includes('Quiz')"
                        class="overflow-hidden border border-slate-200 bg-white shadow-sm transition-all dark:border-slate-800 dark:bg-slate-950"
                    >
                        <!-- Quiz Header -->
                        <div
                            class="border-b border-slate-100 bg-slate-50/50 p-5 md:p-6 dark:border-slate-900 dark:bg-slate-900/30"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <div class="space-y-1">
                                    <h2
                                        class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl dark:text-slate-100"
                                        :style="
                                            activeModule?.color
                                                ? { color: activeModule.color }
                                                : {}
                                        "
                                    >
                                        {{ activeObject.object.title }}
                                    </h2>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                    >
                                        Nilai Minimal:
                                        {{
                                            activeObject.object.passing_grade ??
                                            70
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Quiz Body -->
                        <div class="space-y-8 p-6 md:p-8">
                            <div
                                class="flex flex-col items-center justify-between gap-6 rounded-xl border border-slate-100 bg-slate-50/20 p-6 md:flex-row dark:border-slate-900 dark:bg-slate-900/10"
                            >
                                <div class="space-y-2 text-center md:text-left">
                                    <p class="max-w-md text-sm text-slate-500">
                                        {{
                                            activeObject.object.description ||
                                            'Selesaikan kuis kelulusan ini dan raih hasil terbaikmu untuk membuka materi selanjutnya!'
                                        }}
                                    </p>
                                </div>
                                <div class="shrink-0">
                                    <form
                                        @submit.prevent="
                                            router.post(
                                                start.url({
                                                    classroom: classroom.slug,
                                                    quiz: activeObject.object
                                                        .id,
                                                }),
                                            )
                                        "
                                    >
                                        <Button
                                            type="submit"
                                            size="lg"
                                            class="cursor-pointer bg-blue-600 font-semibold text-white shadow-sm hover:bg-blue-700"
                                        >
                                            <Play
                                                class="mr-2 size-4 fill-white"
                                            />
                                            Mulai Kuis
                                        </Button>
                                    </form>
                                </div>
                            </div>

                            <!-- Student Quiz Attempts / Submission History -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <Award class="size-5 text-slate-400" />
                                    <h3
                                        class="text-md font-bold text-slate-800 dark:text-slate-200"
                                    >
                                        Riwayat Kuis Anda
                                    </h3>
                                </div>

                                <div
                                    class="overflow-x-auto rounded-xl border border-slate-200/80 bg-white dark:border-slate-800/80 dark:bg-slate-950"
                                >
                                    <table
                                        class="w-full border-collapse text-left"
                                    >
                                        <thead>
                                            <tr
                                                class="border-b border-slate-200/80 bg-slate-50 text-xs font-semibold text-slate-500 dark:border-slate-800/80 dark:bg-slate-900/50 dark:text-slate-400"
                                            >
                                                <th class="px-4 py-3 font-bold">
                                                    Percobaan
                                                </th>
                                                <th class="px-4 py-3 font-bold">
                                                    Waktu Pengerjaan
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-center font-bold"
                                                >
                                                    Nilai
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-center font-bold"
                                                >
                                                    Status
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-right font-bold"
                                                >
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-slate-100 text-sm dark:divide-slate-900"
                                        >
                                            <tr
                                                v-if="
                                                    activeQuizSubmissions.length ===
                                                    0
                                                "
                                            >
                                                <td
                                                    colspan="5"
                                                    class="py-8 text-center text-slate-400 italic"
                                                >
                                                    Belum ada riwayat pengerjaan
                                                    kuis ini.
                                                </td>
                                            </tr>
                                            <tr
                                                v-else
                                                v-for="(
                                                    sub, idx
                                                ) in activeQuizSubmissions"
                                                :key="sub.id"
                                                class="transition-colors hover:bg-slate-50/50 dark:hover:bg-slate-900/20"
                                            >
                                                <td
                                                    class="px-4 py-3.5 font-medium text-slate-700 dark:text-slate-300"
                                                >
                                                    #{{
                                                        activeQuizSubmissions.length -
                                                        idx
                                                    }}
                                                </td>
                                                <td
                                                    class="px-4 py-3.5 text-slate-500 dark:text-slate-400"
                                                >
                                                    {{
                                                        formatDate(
                                                            sub.submitted_at,
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="px-4 py-3.5 text-center font-mono font-bold text-slate-800 dark:text-slate-200"
                                                >
                                                    {{ sub.score }}
                                                </td>
                                                <td
                                                    class="px-4 py-3.5 text-center"
                                                >
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold select-none"
                                                        :class="
                                                            sub.is_passing
                                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400'
                                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400'
                                                        "
                                                    >
                                                        <span
                                                            class="h-1.5 w-1.5 rounded-full"
                                                            :class="
                                                                sub.is_passing
                                                                    ? 'bg-emerald-500'
                                                                    : 'bg-rose-500'
                                                            "
                                                        ></span>
                                                        {{
                                                            sub.is_passing
                                                                ? 'Lulus'
                                                                : 'Tidak Lulus'
                                                        }}
                                                    </span>
                                                </td>
                                                <td
                                                    class="px-4 py-3.5 text-right"
                                                >
                                                    <Link
                                                        :href="
                                                            showSubmission.url(
                                                                sub.id,
                                                            )
                                                        "
                                                        class="text-sm font-semibold text-blue-600 transition-colors hover:text-blue-700 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                                    >
                                                        Lihat Detail
                                                    </Link>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </Card>
                </main>

                <!-- RIGHT: Module sidebar -->
                <aside class="space-y-4 lg:col-span-5 xl:col-span-4">
                    <Card
                        class="border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950"
                    >
                        <h3
                            class="mb-4 flex items-center gap-2 border-b pb-3 text-base font-bold text-slate-950 dark:text-slate-50"
                        >
                            <BookOpen class="size-4.5 text-blue-600" />
                            <span>Materi Belajar</span>
                        </h3>

                        <div class="space-y-6">
                            <!-- Module Group -->
                            <div
                                v-for="mod in classroom.modules"
                                :key="mod.id"
                                class="space-y-2.5"
                            >
                                <h4
                                    class="text-xs font-bold tracking-wider text-slate-400 uppercase dark:text-slate-500"
                                    :style="
                                        mod.color ? { color: mod.color } : {}
                                    "
                                >
                                    {{ mod.title }}
                                </h4>

                                <!-- Object List inside Module -->
                                <div class="space-y-2">
                                    <div
                                        v-for="obj in mod.objects"
                                        :key="obj.id"
                                        class="flex items-center gap-3 rounded-xl border p-3 transition-all"
                                        :class="[
                                            !obj.can_access
                                                ? 'cursor-not-allowed border-slate-100 bg-slate-100/40 opacity-60 select-none dark:border-slate-900 dark:bg-slate-900/10'
                                                : activeObject?.id === obj.id
                                                  ? 'cursor-pointer border-blue-200 bg-blue-50/50 shadow-xs dark:border-blue-900/60 dark:bg-blue-950/10'
                                                  : 'cursor-pointer border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800/80 dark:bg-slate-950 dark:hover:border-slate-700',
                                        ]"
                                        @click="selectObject(obj)"
                                    >
                                        <!-- Left Icon depending on status -->
                                        <div
                                            class="flex shrink-0 items-center justify-center"
                                        >
                                            <!-- Locked -->
                                            <div
                                                v-if="!obj.can_access"
                                                class="flex size-7 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-900"
                                            >
                                                <Lock class="size-3.5" />
                                            </div>
                                            <!-- Completed -->
                                            <div
                                                v-else-if="obj.is_completed"
                                                class="flex size-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
                                            >
                                                <CheckCircle2
                                                    class="size-4.5"
                                                />
                                            </div>
                                            <!-- Accessible but not completed (active/todo) -->
                                            <div
                                                v-else
                                                class="flex size-7 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-950 dark:text-blue-400"
                                            >
                                                <Play
                                                    class="size-3.5 fill-blue-600 dark:fill-blue-400"
                                                />
                                            </div>
                                        </div>

                                        <!-- Item Title & Metadata -->
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm leading-tight font-semibold"
                                                :class="[
                                                    !obj.can_access
                                                        ? 'text-slate-400 dark:text-slate-500'
                                                        : activeObject?.id ===
                                                            obj.id
                                                          ? 'font-bold text-blue-700 dark:text-blue-400'
                                                          : 'text-slate-700 dark:text-slate-300',
                                                ]"
                                            >
                                                {{ obj.object.title }}
                                            </p>

                                            <!-- Type Badge / Status Info -->
                                            <div
                                                class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-400"
                                            >
                                                <span
                                                    v-if="
                                                        obj.object_type.includes(
                                                            'Quiz',
                                                        )
                                                    "
                                                    class="font-medium text-violet-600 dark:text-violet-400"
                                                    >Kuis</span
                                                >
                                                <span
                                                    v-else
                                                    class="font-medium text-blue-600 dark:text-blue-400"
                                                    >Materi</span
                                                >

                                                <template
                                                    v-if="
                                                        obj.object_type.includes(
                                                            'Quiz',
                                                        ) &&
                                                        obj.object
                                                            .highest_score !==
                                                            undefined &&
                                                        obj.object
                                                            .highest_score !==
                                                            null
                                                    "
                                                >
                                                    <span>•</span>
                                                    <span class="font-medium"
                                                        >Nilai Terbaik:
                                                        {{
                                                            obj.object
                                                                .highest_score
                                                        }}</span
                                                    >
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </aside>
            </div>
        </div>
    </div>
</template>
