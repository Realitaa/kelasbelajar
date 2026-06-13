<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import { Plus, CheckCheck } from '@lucide/vue';
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue';
import { toast } from 'vue-sonner';
import { show, updateQuestions } from '@/actions/App/Http/Controllers/ClassroomQuizController';
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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'

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
    question: any;
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

function showConfirmModal(title: string, description: string, onConfirm: () => void) {
    confirmModal.value = {
        isOpen: true,
        title,
        description,
        onConfirm: () => {
            onConfirm();
            confirmModal.value.isOpen = false;
        }
    };
}

const http = useHttp();

const isDirty = computed(() => {
    return JSON.stringify(originalQuestions.value) !== JSON.stringify(questions.value);
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

            if (!fetchedQuestions || !Array.isArray(fetchedQuestions) || fetchedQuestions.length === 0) {
                fetchedQuestions = [createEmptyQuestion()];
            }
            
            originalQuestions.value = JSON.parse(JSON.stringify(fetchedQuestions));
            questions.value = JSON.parse(JSON.stringify(fetchedQuestions));
            
            if (activeQuestionIndex.value >= questions.value.length) {
                activeQuestionIndex.value = Math.max(0, questions.value.length - 1);
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
        }
    });
}

watch(() => props.quiz?.id, () => {
    fetchQuestions();
}, { immediate: true });

function createEmptyQuestion(): Question {
    return {
        question: { type: 'doc', content: [] },
        options: [
            { option: { type: 'doc', content: [] }, is_correct: true },
            { option: { type: 'doc', content: [] }, is_correct: false },
        ]
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
        }
    );
}

function handleAddOption(qIndex: number) {
    questions.value[qIndex].options.push({
        option: { type: 'doc', content: [] },
        is_correct: false
    });
}

function handleDeleteOption(qIndex: number, optIndex: number) {
    if (questions.value[qIndex].options.length <= 2) {
        toast.error('Pertanyaan minimal harus memiliki 2 opsi jawaban.');

        return;
    }

    showConfirmModal(
        'Hapus Opsi',
        'Yakin ingin menghapus opsi ini?',
        () => {
            // If we're deleting the correct option, assign correctness to the first available remaining option
            if (questions.value[qIndex].options[optIndex].is_correct) {
                const nextCorrectIndex = optIndex === 0 ? 1 : 0;
                questions.value[qIndex].options[nextCorrectIndex].is_correct = true;
            }
            
            questions.value[qIndex].options.splice(optIndex, 1);
        }
    );
}

function setCorrectOption(qIndex: number, optIndex: number) {
    questions.value[qIndex].options.forEach((opt, idx) => {
        opt.is_correct = (idx === optIndex);
    });
}

function handleSave() {
    isSaving.value = true;
    
    // Check validation manually before sending
    for (let i = 0; i < questions.value.length; i++) {
        if (questions.value[i].options.length < 2) {
            toast.error(`Pertanyaan ke-${i+1} minimal harus memiliki 2 opsi jawaban.`);
            isSaving.value = false;

            return;
        }
    }
    
    if (!props.quiz?.id) {
        toast.error('Gagal menyimpan soal: Data kuis tidak ditemukan. Silakan muat ulang halaman.');
        isSaving.value = false;

        return;
    }

    router.put(updateQuestions([props.classroomSlug, props.quiz.id]).url, {
        questions: questions.value
    }, {
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
                toast.error('Gagal menyimpan soal kuis. Periksa kembali input Anda.');
            }
        }
    });
}

function handleCancel() {
    if (isDirty.value) {
        showConfirmModal(
            'Batalkan Perubahan',
            'Anda yakin ingin membatalkan perubahan?',
            () => {
                questions.value = JSON.parse(JSON.stringify(originalQuestions.value));
            }
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
            if (!confirm('Anda memiliki perubahan soal yang belum disimpan. Yakin ingin meninggalkan halaman ini?')) {
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
    <div class="flex flex-col h-full bg-card rounded-xl border border-border shadow-sm overflow-hidden w-full">
        <div v-if="isLoading" class="flex-1 flex items-center justify-center">
            <UIcon name="i-lucide-loader-2" class="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
        <div v-else class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-border px-4 py-3 bg-muted/20">
                <div class="flex items-center gap-4">
                    <h3 class="font-semibold">Pengelola Soal Kuis</h3>
                    <Tabs v-model="activeTab" class="w-50">
                        <TabsList class="grid w-full grid-cols-2">
                            <TabsTrigger value="preview">
                                <UIcon name="i-lucide-eye" class="size-4 mr-1" />
                                <span>Pratinjau</span>
                            </TabsTrigger>
                            <TabsTrigger value="editor">
                                <UIcon name="i-lucide-edit-3" class="size-4 mr-1" />
                                <span>Editor</span>
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="handleCancel" :disabled="!isDirty">
                        Batal
                    </Button>
                    <Button size="sm" @click="handleSave" :loading="isSaving" :disabled="!isDirty">
                        Simpan
                    </Button>
                </div>
            </div>
            
            <!-- Question List horizontal strip -->
            <div class="flex items-center gap-2 overflow-x-auto border-b border-border p-3 shrink-0 bg-muted/10">
                <Button 
                    v-for="(q, idx) in questions" 
                    :key="idx"
                    :variant="activeQuestionIndex === idx ? 'default' : 'outline'"
                    class="rounded-full w-10 h-10 p-0 flex items-center justify-center shrink-0"
                    @click="activeQuestionIndex = idx"
                >
                    {{ idx + 1 }}
                </Button>
                <Button variant="outline" size="sm" class="shrink-0 ml-2" @click="handleAddQuestion">
                    <Plus class="size-4 mr-1" /> Tambah Pertanyaan
                </Button>
            </div>

            <!-- Main Panel -->
            <div class="flex-1 min-h-0 overflow-y-auto p-4" v-if="questions.length > 0 && questions[activeQuestionIndex]">
                <!-- EDITOR TAB -->
                <div v-show="activeTab === 'editor'" class="space-y-6">
                    <!-- Question Field -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-foreground flex items-center gap-2">
                                Pertanyaan {{ activeQuestionIndex + 1 }}
                            </h4>
                            <Button variant="ghost" size="sm" class="text-destructive hover:bg-destructive/10" @click="handleDeleteQuestion(activeQuestionIndex)">
                                <UIcon name="i-lucide-trash-2" class="size-4 mr-2" /> Hapus Pertanyaan
                            </Button>
                        </div>
                        <div class="border rounded-md bg-card shadow-sm overflow-hidden">
                            <RichEditor v-model="questions[activeQuestionIndex].question" :is-educator="isEducator" class="min-h-50" />
                        </div>
                    </div>

                    <!-- Options List -->
                    <div class="space-y-3 pt-4 border-t border-border">
                        <div class="flex items-center justify-between">
                            <h5 class="text-sm font-semibold">Opsi Jawaban:</h5>
                            <Button variant="outline" size="sm" @click="handleAddOption(activeQuestionIndex)">
                                <Plus class="size-3 mr-1" /> Tambah Opsi
                            </Button>
                        </div>
                        
                        <div class="space-y-4">
                            <div 
                                v-for="(opt, optIdx) in questions[activeQuestionIndex].options" 
                                :key="optIdx"
                                class="flex items-start gap-4 p-4 border rounded-lg bg-card transition-all"
                                :class="opt.is_correct ? 'border-green-500 bg-green-50/5 dark:bg-green-950/10 shadow-sm' : 'border-border hover:border-muted-foreground/30'"
                            >
                                <!-- Option RichEditor -->
                                <div class="flex-1 min-w-0 border rounded-md overflow-hidden bg-background">
                                    <RichEditor v-model="opt.option" :is-educator="isEducator" class="min-h-50" />
                                </div>

                                <div class="w-35 flex flex-col">
                                    <!-- Correct status toggle / double check button -->
                                    <div class="pt-2 shrink-0">
                                        <TooltipProvider v-if="opt.is_correct" :delay-duration="200">
                                            <Tooltip>
                                                <TooltipTrigger class="flex items-center gap-1 px-2.5 py-2.5 bg-green-500 text-white rounded-md text-xs font-semibold shadow-sm select-none w-full">
                                                    <UIcon name="i-lucide-check-circle-2" class="size-3.5" />
                                                    <span>Benar</span>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    Opsi ini sudah ditandai sebagai jawaban yang benar
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>

                                        <Button 
                                            v-else 
                                            variant="outline" 
                                            class="text-muted-foreground border-border hover:text-green-600 hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-950/20 w-full"
                                            @click="setCorrectOption(activeQuestionIndex, optIdx)"
                                            title="Tandai sebagai jawaban benar"
                                        >
                                            <CheckCheck class="size-4 mr-1 text-green-500/80" />
                                            Tandai Benar
                                        </Button>
                                    </div>

                                    <!-- Trash Button -->
                                    <div class="pt-2 shrink-0">
                                        <Button 
                                            variant="ghost" 
                                            class="text-destructive hover:bg-destructive/10 hover:text-destructive w-full" 
                                            @click="handleDeleteOption(activeQuestionIndex, optIdx)" 
                                            title="Hapus Opsi"
                                        >
                                            <UIcon name="i-lucide-trash-2" class="size-4" />
                                            Hapus
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PREVIEW TAB -->
                <div v-show="activeTab === 'preview'" class="space-y-6 max-w-3xl mx-auto py-2">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-border pb-2">
                            <span class="text-sm font-semibold text-muted-foreground">Pratinjau Soal {{ activeQuestionIndex + 1 }}</span>
                        </div>
                        
                        <!-- Question Content -->
                        <div class="prose prose-sm dark:prose-invert max-w-none bg-muted/20 p-5 rounded-lg border border-border">
                            <PreviewRenderer :content="questions[activeQuestionIndex].question" />
                        </div>

                        <!-- Options List -->
                        <div class="space-y-3 pt-2">
                            <div 
                                v-for="(opt, optIdx) in questions[activeQuestionIndex].options" 
                                :key="optIdx"
                                class="flex items-start gap-3 p-4 border rounded-lg transition-colors bg-card"
                                :class="opt.is_correct ? 'border-green-500 bg-green-50/5 dark:bg-green-950/10' : 'border-border'"
                            >
                                <div class="pt-0.5 shrink-0">
                                    <div class="size-5 rounded-full border-2 flex items-center justify-center"
                                         :class="opt.is_correct ? 'border-green-500 text-green-500 bg-green-500/10' : 'border-muted-foreground/40'"
                                    >
                                        <div v-if="opt.is_correct" class="size-2.5 rounded-full bg-green-500"></div>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 prose prose-sm dark:prose-invert">
                                    <PreviewRenderer :content="opt.option" />
                                </div>
                                <Badge v-if="opt.is_correct" variant="outline" class="text-[10px] border-green-500 text-green-600 dark:text-green-400 py-0.5">
                                    Jawaban Benar
                                </Badge>
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
                    <Button variant="secondary" @click="confirmModal.isOpen = false">
                        Batal
                    </Button>
                    <Button variant="destructive" @click="confirmModal.onConfirm">
                        Ya, Lanjutkan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
