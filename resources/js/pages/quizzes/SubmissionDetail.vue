<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Award,
    CheckCircle2,
    XCircle,
    ArrowLeft,
    Info,
    Circle,
    Square,
} from '@lucide/vue';
import PreviewRenderer from '@/components/PreviewRenderer.vue';
import QuestionTypeTooltip from '@/components/QuestionTypeTooltip.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { show as showClassroom } from '@/routes/classrooms';
import type { QuizSubmission, Quiz } from '@/types';

const props = defineProps<{
    submission: QuizSubmission & {
        answers: Record<string, any>;
        questions_order: number[];
        options_order: Record<string, number[]>;
    };
    quiz: Quiz & {
        questions: any[];
        passing_grade: number;
    };
    classroomSlug: string;
}>();

defineOptions({
    layout: (props: any) => ({
        breadcrumbs: [
            {
                title: 'Kelas',
                href: '/classrooms',
            },
            {
                title: 'Detail Kelas',
                href: showClassroom.url(props.classroomSlug),
            },
            {
                title: 'Detail Hasil Kuis',
                current: true,
            },
        ],
    }),
});

const getQuestion = (questionId: number) => {
    return props.quiz.questions.find((q: any) => q.id === questionId);
};

const getOption = (questionId: number, optionId: number) => {
    const question = getQuestion(questionId);

    if (!question) {
        return null;
    }

    return question.options.find((o: any) => o.id === optionId);
};

const getQuestionScore = (questionId: number) => {
    const question = getQuestion(questionId);

    if (!question) {
        return 0;
    }

    const studentAnswer = (props.submission.answers || {})[questionId];

    if (studentAnswer === undefined || studentAnswer === null) {
        return 0;
    }

    if (question.type === 'PG') {
        const correctOption = question.options.find((o: any) => o.is_correct);

        return correctOption && Number(studentAnswer) === correctOption.id
            ? 100
            : 0;
    }

    if (question.type === 'PG MCMA') {
        if (!Array.isArray(studentAnswer)) {
            return 0;
        }

        const correctOptionIds = question.options
            .filter((o: any) => o.is_correct)
            .map((o: any) => o.id);
        const incorrectOptionIds = question.options
            .filter((o: any) => !o.is_correct)
            .map((o: any) => o.id);

        const selectedCorrectCount = studentAnswer.filter((id) =>
            correctOptionIds.includes(id),
        ).length;
        const selectedIncorrectCount = studentAnswer.filter((id) =>
            incorrectOptionIds.includes(id),
        ).length;

        return selectedIncorrectCount === 0 &&
            selectedCorrectCount === correctOptionIds.length
            ? 100
            : 0;
    }

    if (question.type === 'PG K') {
        if (typeof studentAnswer !== 'object') {
            return 0;
        }

        let correctCount = 0;
        const total = question.options.length;

        if (total === 0) {
            return 0;
        }

        question.options.forEach((o: any) => {
            const expected = !!o.is_correct;
            const actual = studentAnswer[o.id];

            if (actual !== undefined && !!actual === expected) {
                correctCount++;
            }
        });

        return Math.round((correctCount / total) * 100);
    }

    return 0;
};

const getOptionStyling = (questionId: number, optionId: number) => {
    const question = getQuestion(questionId);
    const option = getOption(questionId, optionId);
    const studentAnswer = (props.submission.answers || {})[questionId];

    if (!question || !option) {
        return { class: '', icon: Circle };
    }

    const isCorrect = option.is_correct;

    let isSelected = false;

    if (question.type === 'PG') {
        isSelected = Number(studentAnswer) === optionId;
    } else if (question.type === 'PG MCMA') {
        isSelected =
            Array.isArray(studentAnswer) && studentAnswer.includes(optionId);
    }

    if (isCorrect) {
        return {
            class: 'border-emerald-500 bg-emerald-50/50 text-emerald-800 dark:border-emerald-500/80 dark:bg-emerald-950/30 dark:text-emerald-300',
            icon: CheckCircle2,
        };
    }

    if (isSelected && !isCorrect) {
        return {
            class: 'border-rose-500 bg-rose-50/50 text-rose-800 dark:border-rose-500/80 dark:bg-rose-950/30 dark:text-rose-300',
            icon: XCircle,
        };
    }

    return {
        class: 'border-slate-200 bg-white text-slate-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400',
        icon: question.type === 'PG MCMA' ? Square : Circle,
    };
};

const getPgkCellStyling = (
    questionId: number,
    optionId: number,
    isTrueColumn: boolean,
) => {
    const option = getOption(questionId, optionId);
    const studentAnswer = (props.submission.answers || {})[questionId];

    if (!option) {
        return '';
    }

    const expected = Boolean(option.is_correct);
    let selected = null;

    if (
        studentAnswer &&
        typeof studentAnswer === 'object' &&
        studentAnswer[optionId] !== undefined
    ) {
        selected = Boolean(studentAnswer[optionId]);
    }

    if (expected === isTrueColumn) {
        return 'border-2 border-emerald-500 bg-emerald-50/30 text-emerald-600 dark:border-emerald-500/80 dark:bg-emerald-950/20 dark:text-emerald-400';
    }

    if (selected === isTrueColumn && expected !== isTrueColumn) {
        return 'border-2 border-rose-500 bg-rose-50/30 text-rose-600 dark:border-rose-500/80 dark:bg-rose-950/20 dark:text-rose-400';
    }

    return 'border border-transparent border-x-slate-100 text-slate-300 dark:border-x-slate-800 dark:text-slate-700';
};

const getPgkCellIcon = (
    questionId: number,
    optionId: number,
    isTrueColumn: boolean,
) => {
    const option = getOption(questionId, optionId);
    const studentAnswer = (props.submission.answers || {})[questionId];

    if (!option) {
        return null;
    }

    const expected = Boolean(option.is_correct);
    let selected = null;

    if (
        studentAnswer &&
        typeof studentAnswer === 'object' &&
        studentAnswer[optionId] !== undefined
    ) {
        selected = Boolean(studentAnswer[optionId]);
    }

    if (expected === isTrueColumn) {
        return CheckCircle2;
    }

    if (selected === isTrueColumn && expected !== isTrueColumn) {
        return XCircle;
    }

    return Circle;
};
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50/50 dark:bg-slate-900/20">
        <Head title="Detail Hasil Kuis" />

        <div
            class="mx-auto flex w-full max-w-[1200px] flex-1 flex-col gap-6 px-4 py-6 md:px-6"
        >
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                <!-- Left Sidebar -->
                <aside class="space-y-4 lg:col-span-4 xl:col-span-3">
                    <Card
                        class="border border-slate-200 bg-white p-6 text-center shadow-sm dark:border-slate-800 dark:bg-slate-950"
                    >
                        <div class="mb-2">
                            <Award class="mx-auto size-12 text-yellow-500" />
                        </div>
                        <h2
                            class="text-sm font-semibold text-slate-500 dark:text-slate-400"
                        >
                            Nilai Anda
                        </h2>
                        <div
                            class="my-4 text-5xl font-black tracking-tighter text-slate-900 dark:text-slate-100"
                        >
                            {{ submission.score }}
                        </div>

                        <div
                            v-if="
                                submission.score >= (quiz.passing_grade ?? 70)
                            "
                            class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30"
                        >
                            <CheckCircle2
                                class="mx-auto mb-2 size-6 text-emerald-600 dark:text-emerald-400"
                            />
                            <h3
                                class="mb-1 text-sm font-bold text-emerald-800 dark:text-emerald-300"
                            >
                                Lulus Kuis
                            </h3>
                            <p
                                class="text-xs text-emerald-600 dark:text-emerald-400"
                            >
                                Selamat! Kamu lulus kuis ini dan dapat
                                melanjutkan ke materi berikutnya.
                            </p>
                        </div>
                        <div
                            v-else
                            class="rounded-xl border border-rose-100 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/30"
                        >
                            <XCircle
                                class="mx-auto mb-2 size-6 text-rose-600 dark:text-rose-400"
                            />
                            <h3
                                class="mb-1 text-sm font-bold text-rose-800 dark:text-rose-300"
                            >
                                Belum Lulus
                            </h3>
                            <p class="text-xs text-rose-600 dark:text-rose-400">
                                Maaf, kamu belum mencapai batas kelulusan
                                (Minimum: {{ quiz.passing_grade ?? 70 }}).
                                Silakan pelajari kembali materi sebelumnya.
                            </p>
                        </div>

                        <div class="mt-6">
                            <Link :href="showClassroom.url(classroomSlug)">
                                <Button variant="outline" class="w-full gap-2">
                                    <ArrowLeft class="size-4" />
                                    Kembali ke Kelas
                                </Button>
                            </Link>
                        </div>
                    </Card>
                </aside>

                <!-- Right Main Content -->
                <main class="space-y-6 lg:col-span-8 xl:col-span-9">
                    <Card
                        class="border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-950"
                    >
                        <div
                            class="mb-6 flex items-center gap-2 border-b border-slate-100 pb-4 dark:border-slate-800"
                        >
                            <h2
                                class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100"
                            >
                                Evaluasi Jawaban
                            </h2>
                        </div>

                        <div class="space-y-8">
                            <template
                                v-for="(
                                    questionId, index
                                ) in submission.questions_order || []"
                                :key="questionId"
                            >
                                <div
                                    v-if="getQuestion(questionId)"
                                    class="space-y-4 rounded-xl border border-slate-200 p-5 dark:border-slate-800"
                                >
                                    <!-- Question Header -->
                                    <div
                                        class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800"
                                    >
                                        <div class="flex items-center gap-3">
                                            <h3
                                                class="font-bold text-slate-900 dark:text-slate-100"
                                            >
                                                Soal No. {{ index + 1 }}
                                            </h3>
                                            <div
                                                class="flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                                            >
                                                {{
                                                    getQuestion(questionId).type
                                                }}
                                                <QuestionTypeTooltip
                                                    :type="
                                                        getQuestion(questionId)
                                                            .type
                                                    "
                                                />
                                            </div>
                                        </div>
                                        <div
                                            class="font-mono text-sm font-bold"
                                            :class="
                                                getQuestionScore(questionId) ===
                                                100
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-rose-600 dark:text-rose-400'
                                            "
                                        >
                                            {{ getQuestionScore(questionId) }}
                                        </div>
                                    </div>

                                    <!-- Question Text -->
                                    <div
                                        class="prose prose-slate dark:prose-invert max-w-none"
                                    >
                                        <PreviewRenderer
                                            :content="
                                                getQuestion(questionId).question
                                            "
                                            class="p-0!"
                                        />
                                    </div>

                                    <!-- Options PG / PG MCMA -->
                                    <div
                                        v-if="
                                            ['PG', 'PG MCMA'].includes(
                                                getQuestion(questionId).type,
                                            )
                                        "
                                        class="space-y-2.5 pt-2"
                                    >
                                        <template
                                            v-for="optionId in (submission.options_order ||
                                                {})[questionId] || []"
                                            :key="optionId"
                                        >
                                            <div
                                                v-if="
                                                    getOption(
                                                        questionId,
                                                        optionId,
                                                    )
                                                "
                                                class="relative flex items-start gap-3 rounded-xl border p-3.5 transition-colors"
                                                :class="
                                                    getOptionStyling(
                                                        questionId,
                                                        optionId,
                                                    ).class
                                                "
                                            >
                                                <div
                                                    class="mt-0.5 flex shrink-0 items-center justify-center"
                                                >
                                                    <component
                                                        :is="
                                                            getOptionStyling(
                                                                questionId,
                                                                optionId,
                                                            ).icon
                                                        "
                                                        class="size-5"
                                                    />
                                                </div>
                                                <div
                                                    class="prose prose-sm dark:prose-invert max-w-none flex-1"
                                                >
                                                    <PreviewRenderer
                                                        :content="
                                                            getOption(
                                                                questionId,
                                                                optionId,
                                                            ).option
                                                        "
                                                        class="p-0!"
                                                    />
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Options PG K -->
                                    <div
                                        v-else-if="
                                            getQuestion(questionId).type ===
                                            'PG K'
                                        "
                                        class="pt-2"
                                    >
                                        <div
                                            class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800"
                                        >
                                            <table class="w-full text-sm">
                                                <thead
                                                    class="bg-slate-50 dark:bg-slate-900/50"
                                                >
                                                    <tr>
                                                        <th
                                                            class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300"
                                                        >
                                                            Pernyataan
                                                        </th>
                                                        <th
                                                            class="w-24 px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-300"
                                                        >
                                                            Benar
                                                        </th>
                                                        <th
                                                            class="w-24 px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-300"
                                                        >
                                                            Salah
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody
                                                    class="divide-y divide-slate-200 dark:divide-slate-800"
                                                >
                                                    <template
                                                        v-for="optionId in (submission.options_order ||
                                                            {})[questionId] ||
                                                        []"
                                                        :key="optionId"
                                                    >
                                                        <tr
                                                            v-if="
                                                                getOption(
                                                                    questionId,
                                                                    optionId,
                                                                )
                                                            "
                                                        >
                                                            <td
                                                                class="px-4 py-3"
                                                            >
                                                                <PreviewRenderer
                                                                    :content="
                                                                        getOption(
                                                                            questionId,
                                                                            optionId,
                                                                        ).option
                                                                    "
                                                                    class="p-0!"
                                                                />
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-center align-middle"
                                                                :class="
                                                                    getPgkCellStyling(
                                                                        questionId,
                                                                        optionId,
                                                                        true,
                                                                    )
                                                                "
                                                            >
                                                                <div
                                                                    class="flex justify-center"
                                                                >
                                                                    <component
                                                                        :is="
                                                                            getPgkCellIcon(
                                                                                questionId,
                                                                                optionId,
                                                                                true,
                                                                            )
                                                                        "
                                                                        class="size-4"
                                                                    />
                                                                </div>
                                                            </td>
                                                            <td
                                                                class="px-4 py-3 text-center align-middle"
                                                                :class="
                                                                    getPgkCellStyling(
                                                                        questionId,
                                                                        optionId,
                                                                        false,
                                                                    )
                                                                "
                                                            >
                                                                <div
                                                                    class="flex justify-center"
                                                                >
                                                                    <component
                                                                        :is="
                                                                            getPgkCellIcon(
                                                                                questionId,
                                                                                optionId,
                                                                                false,
                                                                            )
                                                                        "
                                                                        class="size-4"
                                                                    />
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Pembahasan / Solution -->
                                    <div
                                        v-if="getQuestion(questionId).solution"
                                        class="mt-6 rounded-xl border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20"
                                    >
                                        <div
                                            class="mb-2 flex items-center gap-2 font-bold text-blue-800 dark:text-blue-300"
                                        >
                                            <Info class="size-4" />
                                            <span>Pembahasan</span>
                                        </div>
                                        <div
                                            class="prose prose-sm prose-slate dark:prose-invert max-w-none"
                                        >
                                            <PreviewRenderer
                                                :content="
                                                    getQuestion(questionId)
                                                        .solution
                                                "
                                                class="p-0!"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </Card>
                </main>
            </div>
        </div>
    </div>
</template>
