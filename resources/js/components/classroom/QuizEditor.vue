<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import { Plus, CheckCheck, CircleQuestionMark } from '@lucide/vue';
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue';
import { toast } from 'vue-sonner';
import {
    show,
    updateQuestions,
} from '@/actions/App/Http/Controllers/ClassroomQuizController';
import PreviewRenderer from '@/components/PreviewRenderer.vue';
import RichEditor from '@/components/RichEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

const props = defineProps<{
    quiz: any;
    classroomSlug: string;
    isEducator?: boolean;
}>();

const isLoading = ref(false);
const isSaving = ref(false);

interface Option {
    id?: number;
    option: any;
    is_correct: boolean;
}

interface Question {
    id?: number;
    type: 'PG' | 'PG MCMA' | 'PG K';
    question: any;
    solution: any;
    options: Option[];
}

const originalQuestions = ref<Question[]>([]);
const questions = ref<Question[]>([]);
const activeQuestionIndex = ref<number>(0);
const activeTab = ref('preview');

const confirmModal = ref({
    isOpen: false,
    title: '',
    description: '',
    onConfirm: () => {},
});

function showConfirmModal(
    title: string,
    description: string,
    onConfirm: () => void,
) {
    confirmModal.value = {
        isOpen: true,
        title,
        description,
        onConfirm: () => {
            onConfirm();
            confirmModal.value.isOpen = false;
        },
    };
}

const http = useHttp();

const isDirty = computed(() => {
    return (
        JSON.stringify(originalQuestions.value) !==
        JSON.stringify(questions.value)
    );
});

defineExpose({ isDirty });

async function fetchQuestions(silent = false) {
    if (!props.quiz?.id) {
        return;
    }

    if (!silent) {
        isLoading.value = true;
    }

    http.get(show([props.classroomSlug, props.quiz.id]).url, {
        onSuccess: (response: any) => {
            let fetchedQuestions = response?.data;

            if (
                !fetchedQuestions ||
                !Array.isArray(fetchedQuestions) ||
                fetchedQuestions.length === 0
            ) {
                fetchedQuestions = [createEmptyQuestion()];
            } else {
                fetchedQuestions = fetchedQuestions.map(q => ({
                    ...q,
                    type: q.type || 'PG',
                    solution: q.solution || { type: 'doc', content: [] }
                }));
            }

            originalQuestions.value = JSON.parse(
                JSON.stringify(fetchedQuestions),
            );
            questions.value = JSON.parse(JSON.stringify(fetchedQuestions));

            if (activeQuestionIndex.value >= questions.value.length) {
                activeQuestionIndex.value = Math.max(
                    0,
                    questions.value.length - 1,
                );
            }

            if (!silent) {
                isLoading.value = false;
            }
        },
        onError: (error: any) => {
            console.error('Failed to fetch quiz questions', error);

            if (!silent) {
                isLoading.value = false;
            }
        },
    });
}

watch(
    () => props.quiz?.id,
    () => {
        fetchQuestions();
    },
    { immediate: true },
);

function createEmptyQuestion(): Question {
    return {
        type: 'PG',
        question: { type: 'doc', content: [] },
        solution: { type: 'doc', content: [] },
        options: [
            { option: { type: 'doc', content: [] }, is_correct: true },
            { option: { type: 'doc', content: [] }, is_correct: false },
        ],
    };
}

function handleAddQuestion() {
    questions.value.push(createEmptyQuestion());
    activeQuestionIndex.value = questions.value.length - 1;
}

function handleDeleteQuestion(index: number) {
    if (questions.value.length <= 1) {
        toast.error('Kuis minimal harus memiliki 1 pertanyaan.');

        return;
    }

    showConfirmModal(
        'Hapus Pertanyaan',
        'Yakin ingin menghapus pertanyaan ini?',
        () => {
            questions.value.splice(index, 1);

            if (activeQuestionIndex.value >= questions.value.length) {
                activeQuestionIndex.value = questions.value.length - 1;
            }
        },
    );
}

function handleAddOption(qIndex: number) {
    questions.value[qIndex].options.push({
        option: { type: 'doc', content: [] },
        is_correct: false,
    });
}

function handleDeleteOption(qIndex: number, optIndex: number) {
    if (questions.value[qIndex].options.length <= 2) {
        toast.error('Pertanyaan minimal harus memiliki 2 opsi jawaban.');

        return;
    }

    showConfirmModal('Hapus Opsi', 'Yakin ingin menghapus opsi ini?', () => {
        // If we're deleting the correct option, assign correctness to the first available remaining option
        const q = questions.value[qIndex];

        if (q.type === 'PG' && q.options[optIndex].is_correct) {
            const nextCorrectIndex = optIndex === 0 ? 1 : 0;
            q.options[nextCorrectIndex].is_correct = true;
        }

        q.options.splice(optIndex, 1);
    });
}

function setCorrectOption(qIndex: number, optIndex: number) {
    const q = questions.value[qIndex];

    if (q.type === 'PG') {
        q.options.forEach((opt, idx) => {
            opt.is_correct = idx === optIndex;
        });
    } else {
        q.options[optIndex].is_correct = !q.options[optIndex].is_correct;
    }
}

function handleSave() {
    isSaving.value = true;

    // Check validation manually before sending
    for (let i = 0; i < questions.value.length; i++) {
        if (questions.value[i].options.length < 2) {
            toast.error(
                `Pertanyaan ke-${i + 1} minimal harus memiliki 2 opsi jawaban.`,
            );
            isSaving.value = false;

            return;
        }
    }

    if (!props.quiz?.id) {
        toast.error(
            'Gagal menyimpan soal: Data kuis tidak ditemukan. Silakan muat ulang halaman.',
        );
        isSaving.value = false;

        return;
    }

    router.put(
        updateQuestions([props.classroomSlug, props.quiz.id]).url,
        {
            questions: questions.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                fetchQuestions(true); // Fetch updated questions with their DB IDs
                isSaving.value = false;
            },
            onError: (errors) => {
                isSaving.value = false;

                if (errors && Object.keys(errors).length > 0) {
                    const firstError = Object.values(errors)[0] as string;
                    toast.error(firstError);
                } else {
                    toast.error(
                        'Gagal menyimpan soal kuis. Periksa kembali input Anda.',
                    );
                }
            },
        },
    );
}

function handleCancel() {
    if (isDirty.value) {
        showConfirmModal(
            'Batalkan Perubahan',
            'Anda yakin ingin membatalkan perubahan?',
            () => {
                questions.value = JSON.parse(
                    JSON.stringify(originalQuestions.value),
                );
            },
        );
    }
}

// Prevent tab close/refresh
function handleBeforeUnload(event: BeforeUnloadEvent) {
    if (isDirty.value) {
        event.preventDefault();
        event.returnValue = '';
    }
}

let removeRouterListener: (() => void) | null = null;

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);

    removeRouterListener = router.on('before', (event) => {
        if (isDirty.value && !isSaving.value) {
            if (
                !confirm(
                    'Anda memiliki perubahan soal yang belum disimpan. Yakin ingin meninggalkan halaman ini?',
                )
            ) {
                event.preventDefault();
            }
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);

    if (removeRouterListener) {
        removeRouterListener();
    }
});
</script>

<template>
    <div
        class="flex h-full w-full flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm"
    >
        <div v-if="isLoading" class="flex flex-1 items-center justify-center">
            <UIcon
                name="i-lucide-loader-2"
                class="h-8 w-8 animate-spin text-muted-foreground"
            />
        </div>
        <div v-else class="flex h-full flex-col">
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b border-border bg-muted/20 px-4 py-3"
            >
                <div class="flex items-center gap-4">
                    <h3 class="font-semibold">Pengelola Soal Kuis</h3>
                    <Tabs v-model="activeTab" class="w-50">
                        <TabsList class="grid w-full grid-cols-2">
                            <TabsTrigger value="preview">
                                <UIcon
                                    name="i-lucide-eye"
                                    class="mr-1 size-4"
                                />
                                <span>Pratinjau</span>
                            </TabsTrigger>
                            <TabsTrigger value="editor">
                                <UIcon
                                    name="i-lucide-edit-3"
                                    class="mr-1 size-4"
                                />
                                <span>Editor</span>
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="handleCancel"
                        :disabled="!isDirty"
                    >
                        Batal
                    </Button>
                    <Button
                        size="sm"
                        @click="handleSave"
                        :loading="isSaving"
                        :disabled="!isDirty"
                    >
                        Simpan
                    </Button>
                </div>
            </div>

            <!-- Question List horizontal strip -->
            <div
                class="flex shrink-0 items-center gap-2 overflow-x-auto border-b border-border bg-muted/10 p-3"
            >
                <Button
                    v-for="(q, idx) in questions"
                    :key="idx"
                    :variant="
                        activeQuestionIndex === idx ? 'default' : 'outline'
                    "
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full p-0"
                    @click="activeQuestionIndex = idx"
                >
                    {{ idx + 1 }}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    class="ml-2 shrink-0"
                    @click="handleAddQuestion"
                >
                    <Plus class="mr-1 size-4" /> Tambah Pertanyaan
                </Button>
            </div>

            <!-- Main Panel -->
            <div
                class="min-h-0 flex-1 overflow-y-auto p-4"
                v-if="questions.length > 0 && questions[activeQuestionIndex]"
            >
                <!-- EDITOR TAB -->
                <div v-show="activeTab === 'editor'" class="space-y-6">
                    <!-- Question Field -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h4
                                class="flex items-center gap-2 font-medium text-foreground"
                            >
                                Pertanyaan {{ activeQuestionIndex + 1 }}
                            </h4>
                            <div class="flex gap-2">
                                <TooltipProvider
                                    :delay-duration="250"
                                    :content="{ side: 'left' }"
                                >
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <CircleQuestionMark class="size-4" />
                                        </TooltipTrigger>
                                        <TooltipContent class="max-w-100">
                                            <div class="flex flex-col items-center gap-2">
                                                <h3 class="text-base font-medium">
                                                    Tipe Soal:
                                                </h3>
                                                <ul class="text-sm">
                                                    <li><span class="font-semibold">PG (Pilihan Ganda):</span> bentuk penilaian objektif di mana responden diminta untuk memilih jawaban yang paling tepat dari beberapa pilihan (opsi) yang telah disediakan.</li>
                                                    <li><span class="font-semibold">PG MCMA (Pilihan Ganda Multiple Choice Multiple Answers):</span> jenis soal pilihan ganda di mana peserta harus memilih lebih dari satu jawaban benar dari beberapa pilihan yang disediakan.</li>
                                                    <li><span class="font-semibold">PG K (Pilihan Ganda Kompleks):</span> jenis soal pilihan ganda yang jawabannya disajikan dalam bentuk tabel atau daftar pernyataan, di mana peserta harus menentukan pilihan "Benar/Salah" untuk setiap pernyataan yang diberikan.</li>
                                                </ul>
                                            </div>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <Select
                                    v-model="questions[activeQuestionIndex].type"
                                >
                                    <SelectTrigger class="h-8 w-30">
                                        <SelectValue placeholder="Tipe Soal" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="PG">PG</SelectItem>
                                        <SelectItem value="PG MCMA">PG MCMA</SelectItem>
                                        <SelectItem value="PG K">PG K</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:bg-destructive/10"
                                    @click="
                                        handleDeleteQuestion(activeQuestionIndex)
                                    "
                                >
                                    <UIcon
                                        name="i-lucide-trash-2"
                                        class="mr-2 size-4"
                                    />
                                    Hapus Pertanyaan
                                </Button>
                            </div>
                        </div>
                        <div
                            class="overflow-hidden rounded-md border bg-card shadow-sm"
                        >
                            <RichEditor
                                v-model="
                                    questions[activeQuestionIndex].question
                                "
                                :is-educator="isEducator"
                                class="min-h-50"
                            />
                        </div>
                    </div>

                    <!-- Options List -->
                    <div class="space-y-3 border-t border-border pt-4">
                        <div class="flex items-center justify-between">
                            <h5 class="text-sm font-semibold">Opsi Jawaban:</h5>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="handleAddOption(activeQuestionIndex)"
                            >
                                <Plus class="mr-1 size-3" /> Tambah Opsi
                            </Button>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="(opt, optIdx) in questions[
                                    activeQuestionIndex
                                ].options"
                                :key="optIdx"
                                class="flex items-start gap-4 rounded-lg border bg-card p-4 transition-all"
                                :class="
                                    opt.is_correct
                                        ? 'border-green-500 bg-green-50/5 shadow-sm dark:bg-green-950/10'
                                        : 'border-border hover:border-muted-foreground/30'
                                "
                            >
                                <!-- Option RichEditor -->
                                <div
                                    class="min-w-0 flex-1 overflow-hidden rounded-md border bg-background"
                                >
                                    <RichEditor
                                        v-model="opt.option"
                                        :is-educator="isEducator"
                                        class="min-h-50"
                                    />
                                </div>

                                <div class="flex w-35 flex-col">
                                    <!-- Correct status toggle / double check button -->
                                    <div class="shrink-0 pt-2">
                                        <TooltipProvider
                                            v-if="opt.is_correct"
                                            :delay-duration="200"
                                        >
                                            <Tooltip>
                                                <TooltipTrigger
                                                    class="flex w-full items-center gap-1 rounded-md bg-green-500 px-2.5 py-2.5 text-xs font-semibold text-white shadow-sm select-none"
                                                    :class="questions[activeQuestionIndex].type !== 'PG' ? 'cursor-pointer hover:bg-green-600' : ''"
                                                    @click="questions[activeQuestionIndex].type !== 'PG' ? setCorrectOption(activeQuestionIndex, optIdx) : null"
                                                >
                                                    <UIcon
                                                        name="i-lucide-check-circle-2"
                                                        class="size-3.5"
                                                    />
                                                    <span>Benar</span>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Opsi ini sudah ditandai
                                                    sebagai jawaban yang benar
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>

                                        <Button
                                            v-else
                                            variant="outline"
                                            class="w-full border-border text-muted-foreground hover:border-green-500 hover:bg-green-50 hover:text-green-600 dark:hover:bg-green-950/20"
                                            @click="
                                                setCorrectOption(
                                                    activeQuestionIndex,
                                                    optIdx,
                                                )
                                            "
                                            title="Tandai sebagai jawaban benar"
                                        >
                                            <CheckCheck
                                                class="mr-1 size-4 text-green-500/80"
                                            />
                                            Tandai Benar
                                        </Button>
                                    </div>

                                    <!-- Trash Button -->
                                    <div class="shrink-0 pt-2">
                                        <Button
                                            variant="ghost"
                                            class="w-full text-destructive hover:bg-destructive/10 hover:text-destructive"
                                            @click="
                                                handleDeleteOption(
                                                    activeQuestionIndex,
                                                    optIdx,
                                                )
                                            "
                                            title="Hapus Opsi"
                                        >
                                            <UIcon
                                                name="i-lucide-trash-2"
                                                class="size-4"
                                            />
                                            Hapus
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Solution Field -->
                    <div class="space-y-2 border-t border-border pt-4">
                        <h4 class="font-medium text-foreground">
                            Penyelesaian (Opsional)
                        </h4>
                        <div
                            class="overflow-hidden rounded-md border bg-card shadow-sm"
                        >
                            <RichEditor
                                v-model="
                                    questions[activeQuestionIndex].solution
                                "
                                :is-educator="isEducator"
                                class="min-h-40"
                            />
                        </div>
                    </div>
                </div>

                <!-- PREVIEW TAB -->
                <div
                    v-show="activeTab === 'preview'"
                    class="mx-auto max-w-3xl space-y-6 py-2"
                >
                    <div class="space-y-4">
                        <div
                            class="flex items-center justify-between border-b border-border pb-2"
                        >
                            <span
                                class="text-sm font-semibold text-muted-foreground"
                                >Pratinjau Soal
                                {{ activeQuestionIndex + 1 }}</span
                            >
                        </div>

                        <!-- Question Content -->
                        <div
                            class="prose prose-sm dark:prose-invert max-w-none rounded-lg border border-border bg-muted/20 p-5"
                        >
                            <PreviewRenderer
                                :content="
                                    questions[activeQuestionIndex].question
                                "
                            />
                        </div>

                        <!-- Options List (PG & PG MCMA) -->
                        <div v-if="questions[activeQuestionIndex].type !== 'PG K'" class="space-y-3 pt-2">
                            <div
                                v-for="(opt, optIdx) in questions[
                                    activeQuestionIndex
                                ].options"
                                :key="optIdx"
                                class="flex items-start gap-3 rounded-lg border bg-card p-4 transition-colors"
                                :class="
                                    opt.is_correct
                                        ? 'border-green-500 bg-green-50/5 dark:bg-green-950/10'
                                        : 'border-border'
                                "
                            >
                                <div class="shrink-0 pt-0.5">
                                    <div
                                        class="flex size-5 items-center justify-center rounded-full border-2"
                                        :class="
                                            opt.is_correct
                                                ? 'border-green-500 bg-green-500/10 text-green-500'
                                                : 'border-muted-foreground/40'
                                        "
                                    >
                                        <div
                                            v-if="opt.is_correct"
                                            class="size-2.5 rounded-full bg-green-500"
                                        ></div>
                                    </div>
                                </div>
                                <div
                                    class="prose prose-sm dark:prose-invert min-w-0 flex-1"
                                >
                                    <PreviewRenderer :content="opt.option" />
                                </div>
                                <Badge
                                    v-if="opt.is_correct"
                                    variant="outline"
                                    class="border-green-500 py-0.5 text-[10px] text-green-600 dark:text-green-400"
                                >
                                    Jawaban Benar
                                </Badge>
                            </div>
                        </div>

                        <!-- Options Table (PG K) -->
                        <div v-else class="pt-2">
                            <div class="overflow-x-auto rounded-lg border border-border">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-muted/50 text-muted-foreground">
                                        <tr>
                                            <th class="px-4 py-3 font-medium w-12 text-center">#</th>
                                            <th class="px-4 py-3 font-medium">Pernyataan</th>
                                            <th class="px-4 py-3 font-medium w-24 text-center">Benar</th>
                                            <th class="px-4 py-3 font-medium w-24 text-center">Salah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border">
                                        <tr v-for="(opt, optIdx) in questions[activeQuestionIndex].options" :key="optIdx" class="bg-card">
                                            <td class="px-4 py-3 text-center font-medium">{{ String.fromCharCode(65 + optIdx) }}</td>
                                            <td class="px-4 py-3">
                                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                                    <PreviewRenderer :content="opt.option" />
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex justify-center">
                                                    <div class="flex size-5 items-center justify-center rounded-full border-2" :class="opt.is_correct ? 'border-green-500 bg-green-500/10 text-green-500' : 'border-muted-foreground/40'">
                                                        <div v-if="opt.is_correct" class="size-2.5 rounded-full bg-green-500"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex justify-center">
                                                    <div class="flex size-5 items-center justify-center rounded-full border-2" :class="!opt.is_correct ? 'border-green-500 bg-green-500/10 text-green-500' : 'border-muted-foreground/40'">
                                                        <div v-if="!opt.is_correct" class="size-2.5 rounded-full bg-green-500"></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Solution Preview -->
                        <div v-if="questions[activeQuestionIndex].solution && questions[activeQuestionIndex].solution.content && questions[activeQuestionIndex].solution.content.length > 0" class="mt-6 space-y-2 border-t border-border pt-4">
                            <span class="text-sm font-semibold text-muted-foreground">Penyelesaian:</span>
                            <div class="prose prose-sm dark:prose-invert max-w-none rounded-lg border border-border bg-green-50/30 p-5 dark:bg-green-950/10">
                                <PreviewRenderer :content="questions[activeQuestionIndex].solution" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Dialog v-model:open="confirmModal.isOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ confirmModal.title }}</DialogTitle>
                    <DialogDescription>
                        {{ confirmModal.description }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button
                        variant="secondary"
                        @click="confirmModal.isOpen = false"
                    >
                        Batal
                    </Button>
                    <Button
                        variant="destructive"
                        @click="confirmModal.onConfirm"
                    >
                        Ya, Lanjutkan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
