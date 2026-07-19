<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, CheckCircle2, Clock } from '@lucide/vue';
import { useLocalStorage } from '@vueuse/core';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import PreviewRenderer from '@/components/PreviewRenderer.vue';
import QuestionTypeTooltip from '@/components/QuestionTypeTooltip.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import QuizLayout from '@/layouts/QuizLayout.vue';
import { question, answer, submit } from '@/routes/quizzes';

defineOptions({ layout: QuizLayout });

const props = defineProps<{
    session: any;
    quiz: any;
    totalQuestions: number;
    classroomSlug: string;
}>();

const currentQuestionIndex = ref(0);
const currentQuestion = ref<any>(null);
const isLoading = ref(true);
const selectedAnswer = ref<any>(null);
const submitting = ref(false);

const doubtfulQuestions = useLocalStorage<Record<number, boolean>>(
    `quiz_doubtful_${props.session.id}`,
    {},
);

const localAnswers = ref<Record<number, any>>({ ...props.session.answers });

const isQuestionAnswered = (questionId: number) => {
    const ans = localAnswers.value[questionId];

    if (ans === undefined || ans === null) {
        return false;
    }

    if (Array.isArray(ans) && ans.length === 0) {
        return false;
    }

    if (typeof ans === 'object' && Object.keys(ans).length === 0) {
        return false;
    }

    return true;
};

const isCurrentQuestionDoubtful = computed(() => {
    return !!doubtfulQuestions.value[currentQuestionIndex.value];
});

const toggleDoubtful = () => {
    doubtfulQuestions.value[currentQuestionIndex.value] =
        !doubtfulQuestions.value[currentQuestionIndex.value];
};

const changeQuestion = async (index: number) => {
    await saveAnswer();
    loadQuestion(index);
};

const timeLeft = ref<number>(0);
let timerInterval: any = null;

// Calculate remaining time in seconds
const calculateTimeLeft = () => {
    const expiresAt = new Date(props.session.expires_at).getTime();
    const now = new Date().getTime();
    const diff = Math.floor((expiresAt - now) / 1000);

    return diff > 0 ? diff : 0;
};

const formattedTime = computed(() => {
    if (timeLeft.value <= 0) {
        return '00:00';
    }

    const hours = Math.floor(timeLeft.value / 3600);
    const minutes = Math.floor((timeLeft.value % 3600) / 60);
    const seconds = timeLeft.value % 60;

    if (hours > 0) {
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

const http = useHttp();

const loadQuestion = (index: number) => {
    if (index < 0 || index >= props.totalQuestions) {
        return;
    }

    isLoading.value = true;
    currentQuestionIndex.value = index;

    http.get(question.url({ session: props.session.id, index }), {
        onSuccess: (response: any) => {
            const data = response.data ? response.data : response;
            currentQuestion.value = data;

            if (data.answer !== null && data.answer !== undefined) {
                selectedAnswer.value = data.answer;
                localAnswers.value[data.id] = data.answer;
            } else {
                if (data.type === 'PG MCMA') {
                    selectedAnswer.value = [];
                } else if (data.type === 'PG K') {
                    selectedAnswer.value = {};
                } else {
                    selectedAnswer.value = null;
                }
            }
        },
        onError: (errors: any) => {
            if (errors.error === 'Waktu habis') {
                submitQuiz();
            } else {
                console.error('Failed to load question', errors);
            }
        },
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const saveAnswer = async () => {
    if (!currentQuestion.value) {
        return;
    }

    const questionId = currentQuestion.value.id;
    const answerValue = selectedAnswer.value;

    return new Promise((resolve) => {
        const payload = {
            question_id: questionId,
            answer: answerValue,
        };

        const postHttp = useHttp(payload);

        postHttp.post(answer.url(props.session.id), {
            onSuccess: () => {
                localAnswers.value[questionId] = answerValue;
                resolve(true);
            },
            onError: (errors: any) => {
                if (errors.error === 'Waktu habis') {
                    submitQuiz();
                } else {
                    console.error('Failed to save answer', errors);
                }

                resolve(false);
            },
        });
    });
};

const nextQuestion = async () => {
    await saveAnswer();

    if (currentQuestionIndex.value < props.totalQuestions - 1) {
        loadQuestion(currentQuestionIndex.value + 1);
    }
};

const prevQuestion = async () => {
    await saveAnswer();

    if (currentQuestionIndex.value > 0) {
        loadQuestion(currentQuestionIndex.value - 1);
    }
};

const submitQuiz = () => {
    if (submitting.value) {
        return;
    }

    submitting.value = true;
    doubtfulQuestions.value = {}; // Clear doubtful storage

    router.post(
        submit.url(props.session.id),
        {},
        {
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
};

const confirmSubmit = async () => {
    if (confirm('Apakah Anda yakin ingin menyelesaikan kuis ini?')) {
        await saveAnswer();
        submitQuiz();
    }
};

const selectSingleOption = (optionId: number) => {
    selectedAnswer.value = optionId;
    saveAnswer();
};

const toggleMcmaOption = (optionId: number) => {
    if (!Array.isArray(selectedAnswer.value)) {
        selectedAnswer.value = [];
    }

    const index = selectedAnswer.value.indexOf(optionId);

    if (index === -1) {
        selectedAnswer.value.push(optionId);
    } else {
        selectedAnswer.value.splice(index, 1);
    }

    saveAnswer();
};

const setPgKOption = (optionId: number, value: boolean) => {
    if (!selectedAnswer.value || typeof selectedAnswer.value !== 'object') {
        selectedAnswer.value = {};
    }

    selectedAnswer.value[optionId] = value;
    saveAnswer();
};

onMounted(() => {
    timeLeft.value = calculateTimeLeft();

    if (timeLeft.value <= 0) {
        submitQuiz();

        return;
    }

    timerInterval = setInterval(() => {
        timeLeft.value = calculateTimeLeft();

        if (timeLeft.value <= 0) {
            clearInterval(timerInterval);
            submitQuiz();
        }
    }, 1000);

    loadQuestion(0);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>

<template>
    <Head :title="quiz.title" />

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 md:px-6">
            <!-- Sticky Header -->
            <header
                class="sticky top-0 z-10 border-b border-slate-200 bg-white/80 px-4 py-3 shadow-xs backdrop-blur-md md:px-6 dark:border-slate-800 dark:bg-slate-950/80"
            >
                <div
                    class="mx-auto flex max-w-7xl items-center justify-between"
                >
                    <div class="flex items-center gap-4">
                        <h1
                            class="hidden text-lg font-bold text-slate-800 md:block dark:text-slate-100"
                        >
                            {{ quiz.title }}
                        </h1>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Timer -->
                        <div
                            class="flex items-center gap-2 rounded-full px-3 py-1.5 font-mono text-sm font-bold"
                            :class="
                                timeLeft < 300
                                    ? 'animate-pulse bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400'
                                    : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                            "
                        >
                            <Clock class="size-4" />
                            {{ formattedTime }}
                        </div>

                        <Button
                            @click="confirmSubmit"
                            variant="default"
                            class="bg-blue-600 font-semibold text-white hover:bg-blue-700"
                            :disabled="submitting"
                        >
                            <CheckCircle2 class="mr-2 size-4" />
                            Selesai Kuis
                        </Button>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main
                class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:flex-row md:p-6 lg:p-8"
            >
                <!-- Sidebar Navigation -->
                <aside class="w-full shrink-0 md:w-64">
                    <Card class="sticky top-24 p-4">
                        <h3
                            class="mb-4 text-sm font-bold text-slate-700 dark:text-slate-300"
                        >
                            Navigasi Soal
                        </h3>
                        <div
                            class="mx-auto grid w-fit grid-cols-5 justify-center gap-2"
                        >
                            <button
                                v-for="(_, index) in totalQuestions"
                                :key="index"
                                :disabled="currentQuestionIndex === index"
                                @click="changeQuestion(index)"
                                class="flex size-10 items-center justify-center rounded-lg border text-sm font-semibold transition-colors disabled:cursor-default"
                                :class="[
                                    currentQuestionIndex === index
                                        ? 'border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500'
                                        : doubtfulQuestions[index]
                                          ? 'border-amber-500 bg-amber-500 text-white hover:bg-amber-600'
                                          : isQuestionAnswered(
                                                  session.questions_order[
                                                      index
                                                  ],
                                              )
                                            ? 'border-emerald-600 bg-emerald-600 text-white hover:bg-emerald-700 dark:border-emerald-500 dark:bg-emerald-500'
                                            : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 dark:hover:border-slate-700',
                                ]"
                            >
                                {{ index + 1 }}
                            </button>
                        </div>
                    </Card>
                </aside>

                <!-- Question Area -->
                <div class="flex-1 space-y-6">
                    <Card
                        v-if="isLoading"
                        class="flex min-h-[300px] flex-col items-center justify-center p-8 text-slate-400"
                    >
                        <div
                            class="h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-blue-600"
                        ></div>
                        <p class="mt-4 text-sm">Memuat soal...</p>
                    </Card>

                    <Card v-else-if="currentQuestion" class="p-6 md:p-8">
                        <div class="space-y-6">
                            <!-- Question Header -->
                            <div
                                class="mb-6 flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800"
                            >
                                <div class="flex items-center gap-2">
                                    <h2
                                        class="text-xl font-bold text-slate-800 dark:text-slate-100"
                                    >
                                        Soal No. {{ currentQuestionIndex + 1 }}
                                    </h2>
                                    <Badge
                                        :variant="
                                            currentQuestion.type === 'PG'
                                                ? 'pg'
                                                : currentQuestion.type ===
                                                    'PG MCMA'
                                                  ? 'mcma'
                                                  : 'complex'
                                        "
                                    >
                                        {{ currentQuestion.type }}
                                    </Badge>
                                    <QuestionTypeTooltip side="right" />
                                </div>
                            </div>

                            <!-- Question Text -->
                            <div
                                class="prose prose-slate dark:prose-invert mb-8 max-w-none text-base"
                            >
                                <PreviewRenderer
                                    :content="currentQuestion.question"
                                    class="p-0!"
                                />
                            </div>

                            <!-- Options -->
                            <div
                                class="space-y-3"
                                v-if="currentQuestion.type !== 'PG K'"
                            >
                                <label
                                    v-for="(
                                        option, idx
                                    ) in currentQuestion.options"
                                    :key="option.id"
                                    class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition-all hover:bg-slate-50 dark:hover:bg-slate-900/50"
                                    :class="
                                        (currentQuestion.type === 'PG' &&
                                            selectedAnswer === option.id) ||
                                        (currentQuestion.type === 'PG MCMA' &&
                                            Array.isArray(selectedAnswer) &&
                                            selectedAnswer.includes(option.id))
                                            ? 'border-blue-600 bg-blue-50/50 dark:border-blue-500 dark:bg-blue-900/20'
                                            : 'border-slate-200 dark:border-slate-800'
                                    "
                                >
                                    <div class="mt-1 flex h-5 items-center">
                                        <input
                                            v-if="currentQuestion.type === 'PG'"
                                            type="radio"
                                            :name="
                                                'question_' + currentQuestion.id
                                            "
                                            :value="option.id"
                                            :checked="
                                                selectedAnswer === option.id
                                            "
                                            @change="
                                                selectSingleOption(option.id)
                                            "
                                            class="h-4 w-4 border-slate-300 text-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:ring-offset-slate-950"
                                        />
                                        <input
                                            v-else
                                            type="checkbox"
                                            :name="
                                                'question_' +
                                                currentQuestion.id +
                                                '_' +
                                                option.id
                                            "
                                            :value="option.id"
                                            :checked="
                                                Array.isArray(selectedAnswer) &&
                                                selectedAnswer.includes(
                                                    option.id,
                                                )
                                            "
                                            @change="
                                                toggleMcmaOption(option.id)
                                            "
                                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:ring-offset-slate-950"
                                        />
                                    </div>
                                    <div
                                        class="flex-1 text-sm text-slate-700 dark:text-slate-300"
                                    >
                                        <div class="flex items-start">
                                            <span
                                                class="mt-1 mr-3 font-bold text-slate-400"
                                                >{{
                                                    String.fromCharCode(
                                                        65 + Number(idx),
                                                    )
                                                }}.</span
                                            >
                                            <div
                                                class="prose prose-sm prose-slate dark:prose-invert flex-1"
                                            >
                                                <PreviewRenderer
                                                    :content="option.option"
                                                    class="p-0!"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- PG K Table -->
                            <div v-else class="pt-2">
                                <div
                                    class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-800"
                                >
                                    <table class="w-full text-left text-sm">
                                        <thead
                                            class="bg-slate-50 text-slate-500 dark:bg-slate-900/50 dark:text-slate-400"
                                        >
                                            <tr>
                                                <th
                                                    class="w-12 px-4 py-3 text-center font-medium"
                                                >
                                                    #
                                                </th>
                                                <th
                                                    class="px-4 py-3 font-medium"
                                                >
                                                    Pernyataan
                                                </th>
                                                <th
                                                    class="w-24 px-4 py-3 text-center font-medium"
                                                >
                                                    Benar
                                                </th>
                                                <th
                                                    class="w-24 px-4 py-3 text-center font-medium"
                                                >
                                                    Salah
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="divide-y divide-slate-200 dark:divide-slate-800"
                                        >
                                            <tr
                                                v-for="(
                                                    option, idx
                                                ) in currentQuestion.options"
                                                :key="option.id"
                                                class="bg-white dark:bg-slate-950"
                                            >
                                                <td
                                                    class="px-4 py-3 text-center font-bold text-slate-400"
                                                >
                                                    {{
                                                        String.fromCharCode(
                                                            65 + idx,
                                                        )
                                                    }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div
                                                        class="prose prose-sm prose-slate dark:prose-invert max-w-none flex-1"
                                                    >
                                                        <PreviewRenderer
                                                            :content="
                                                                option.option
                                                            "
                                                            class="p-0!"
                                                        />
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-center"
                                                    @click="
                                                        setPgKOption(
                                                            option.id,
                                                            true,
                                                        )
                                                    "
                                                >
                                                    <input
                                                        type="radio"
                                                        :name="
                                                            'pgk_' +
                                                            currentQuestion.id +
                                                            '_' +
                                                            option.id
                                                        "
                                                        :checked="
                                                            selectedAnswer &&
                                                            typeof selectedAnswer ===
                                                                'object' &&
                                                            selectedAnswer[
                                                                option.id
                                                            ] === true
                                                        "
                                                        class="h-4 w-4 cursor-pointer border-slate-300 text-blue-600 focus:ring-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:ring-offset-slate-950"
                                                    />
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-center"
                                                    @click="
                                                        setPgKOption(
                                                            option.id,
                                                            false,
                                                        )
                                                    "
                                                >
                                                    <input
                                                        type="radio"
                                                        :name="
                                                            'pgk_' +
                                                            currentQuestion.id +
                                                            '_' +
                                                            option.id
                                                        "
                                                        :checked="
                                                            selectedAnswer &&
                                                            typeof selectedAnswer ===
                                                                'object' &&
                                                            selectedAnswer[
                                                                option.id
                                                            ] === false
                                                        "
                                                        class="h-4 w-4 cursor-pointer border-slate-300 text-rose-600 focus:ring-rose-600 dark:border-slate-700 dark:bg-slate-900 dark:ring-offset-slate-950"
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Navigation Footer -->
                            <div
                                class="mt-8 flex flex-col items-center justify-between gap-3 border-t border-slate-100 pt-6 md:flex-row dark:border-slate-800"
                            >
                                <Button
                                    variant="outline"
                                    @click="prevQuestion"
                                    class="w-full md:w-fit"
                                    :disabled="
                                        currentQuestionIndex === 0 || submitting
                                    "
                                >
                                    <ArrowLeft class="mr-2 size-4" />
                                    Soal Sebelumnya
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="toggleDoubtful"
                                    class="w-full md:w-fit"
                                    :class="
                                        isCurrentQuestionDoubtful
                                            ? 'border-amber-600 bg-amber-500 text-white hover:bg-amber-600 dark:border-amber-700 dark:bg-amber-600'
                                            : 'border-amber-200 bg-white text-amber-600 hover:bg-amber-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800/50'
                                    "
                                    :disabled="submitting"
                                >
                                    <span class="font-semibold">Ragu-Ragu</span>
                                </Button>

                                <Button
                                    v-if="
                                        currentQuestionIndex <
                                        totalQuestions - 1
                                    "
                                    @click="nextQuestion"
                                    class="w-full bg-slate-900 text-white hover:bg-slate-800 md:w-fit dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                                    :disabled="submitting"
                                >
                                    Soal Selanjutnya
                                    <ArrowRight class="ml-2 size-4" />
                                </Button>

                                <Button
                                    v-else
                                    @click="confirmSubmit"
                                    class="w-full bg-blue-600 text-white hover:bg-blue-700 md:w-fit"
                                    :disabled="submitting"
                                >
                                    <CheckCircle2 class="mr-2 size-4" />
                                    Selesai & Kumpulkan
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>
            </main>
        </div>
    </div>
</template>
