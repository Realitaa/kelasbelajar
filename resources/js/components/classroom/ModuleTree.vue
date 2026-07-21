<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3';
import { ArrowUpDown, Check, Plus, Infinity as InfinityIcon } from '@lucide/vue';
import type { TreeItem } from '@nuxt/ui';
import { ref, watch, computed } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import {
    store as storeModule,
    update as updateModule,
    destroy as destroyModule,
    reorder as reorderModule,
    reorderObjects as reorderObjects,
} from '@/actions/App/Http/Controllers/ClassroomModuleController';
import {
    store as storeObject,
    update as updateObject,
    destroy as destroyObject,
} from '@/actions/App/Http/Controllers/ModuleObjectController';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import ColorPicker from '@/components/ui/color-picker/ColorPicker.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { Classroom } from '@/types';

const props = defineProps<{
    classroom: Classroom;
    isManage?: boolean;
}>();

const emit = defineEmits(['edit-content', 'edit-quiz', 'return-manage']);
const orderingMode = ref(false);

// Local state for dragging
const localModules = ref<any[]>([]);

watch(
    () => props.classroom,
    (newClassroom) => {
        if (newClassroom && newClassroom.modules) {
            // Deep copy to allow local mutations during drag
            localModules.value = JSON.parse(
                JSON.stringify(newClassroom.modules),
            );
        }
    },
    { immediate: true, deep: true },
);

const hasOrderingChanges = computed(() => {
    if (!props.classroom?.modules) {
        return false;
    }

    if (localModules.value.length !== props.classroom.modules.length) {
        return true;
    }

    for (let i = 0; i < localModules.value.length; i++) {
        if (localModules.value[i].id !== props.classroom.modules[i].id) {
            return true;
        }

        const localObjects = localModules.value[i].objects || [];
        const originalObjects = props.classroom.modules[i].objects || [];

        if (localObjects.length !== originalObjects.length) {
            return true;
        }

        for (let j = 0; j < localObjects.length; j++) {
            if (localObjects[j].id !== originalObjects[j].id) {
                return true;
            }
        }
    }

    return false;
});

// Modal States
const isModuleModalOpen = ref(false);
const isObjectModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const deleteTargetType = ref<'module' | 'object' | null>(null);
const itemToDelete = ref<any>(null);
const deleteMessage = ref('');

// Forms
const moduleForm = useForm({
    id: null as number | null,
    title: '',
    color: null as string | null,
});

const objectForm = useForm({
    id: null as number | null,
    module_id: null as number | null,
    type: 'learning_content',
    title: '',
    description: '',
    passing_grade: 70,
    time_limit: 30,
    max_attempts: null as number | null,
    min_attempts_for_solution: 1,
});

const isUnlimited = ref(true);

function setUnlimited(val: boolean) {
    isUnlimited.value = val;

    if (val) {
        objectForm.max_attempts = null;
    } else {
        objectForm.max_attempts = 1;
    }
}

const reorderForm = useForm({
    modules: [] as any[],
});

const deleteForm = useForm({});

// Modal Actions for Module
function openCreateModule() {
    moduleForm.reset();
    moduleForm.id = null;
    moduleForm.color = null;
    isModuleModalOpen.value = true;
}

function openEditModule(module: any) {
    moduleForm.reset();
    moduleForm.id = module.id;
    moduleForm.title = module.title;
    moduleForm.color = module.color || null;
    isModuleModalOpen.value = true;
}

function submitModule() {
    if (moduleForm.id) {
        moduleForm.put(
            updateModule([props.classroom.slug, moduleForm.id]).url,
            {
                onSuccess: () => {
                    isModuleModalOpen.value = false;
                    moduleForm.reset();
                },
            },
        );
    } else {
        moduleForm.post(storeModule(props.classroom.slug).url, {
            onSuccess: () => {
                isModuleModalOpen.value = false;
                moduleForm.reset();
            },
        });
    }
}

// Modal Actions for Object
function openCreateObject(moduleId: number, type: 'learning_content' | 'quiz') {
    objectForm.reset();
    objectForm.id = null;
    objectForm.module_id = moduleId;
    objectForm.type = type;

    if (type === 'quiz') {
        isUnlimited.value = true;
        objectForm.max_attempts = null;
        objectForm.min_attempts_for_solution = 1;
    }

    isObjectModalOpen.value = true;
}

function openEditObject(objectItem: any) {
    objectForm.reset();
    objectForm.id = objectItem.id;
    objectForm.type = objectItem.object_type.includes('Quiz')
        ? 'quiz'
        : 'learning_content';
    objectForm.title = objectItem.object.title;

    if (objectForm.type === 'quiz') {
        objectForm.description = objectItem.object.description;
        objectForm.passing_grade = objectItem.object.passing_grade;
        objectForm.time_limit = objectItem.object.time_limit || 30;

        if (objectItem.object.max_attempts === null || objectItem.object.max_attempts === undefined) {
            isUnlimited.value = true;
            objectForm.max_attempts = null;
        } else {
            isUnlimited.value = false;
            objectForm.max_attempts = objectItem.object.max_attempts;
        }

        objectForm.min_attempts_for_solution = objectItem.object.min_attempts_for_solution ?? 1;
    }

    isObjectModalOpen.value = true;
}

function submitObject() {
    if (objectForm.id) {
        objectForm.put(
            updateObject([props.classroom.slug, objectForm.id]).url,
            {
                onSuccess: () => {
                    isObjectModalOpen.value = false;
                    objectForm.reset();
                },
            },
        );
    } else {
        objectForm.post(
            storeObject([props.classroom.slug, objectForm.module_id!]).url,
            {
                onSuccess: () => {
                    isObjectModalOpen.value = false;
                    objectForm.reset();
                },
            },
        );
    }
}

// Modal Action for deleting module and object
function confirmDeleteModule(module: any) {
    itemToDelete.value = module;
    deleteTargetType.value = 'module';
    deleteMessage.value = `Apakah Anda yakin ingin menghapus modul "${module.title}" beserta seluruh isinya?`;
    isDeleteModalOpen.value = true;
}

function confirmDeleteObject(objectItem: any) {
    itemToDelete.value = objectItem;
    deleteTargetType.value = 'object';
    deleteMessage.value = `Apakah Anda yakin ingin menghapus "${objectItem.object.title}"?`;
    isDeleteModalOpen.value = true;
}

function submitDelete() {
    if (!itemToDelete.value || !deleteTargetType.value) {
        return;
    }

    if (deleteTargetType.value === 'module') {
        deleteForm.delete(
            destroyModule([props.classroom.slug, itemToDelete.value.id]).url,
            {
                onSuccess: () => {
                    isDeleteModalOpen.value = false;
                    itemToDelete.value = null;
                    deleteTargetType.value = null;
                },
            },
        );
    } else if (deleteTargetType.value === 'object') {
        deleteForm.delete(
            destroyObject([props.classroom.slug, itemToDelete.value.id]).url,
            {
                onSuccess: () => {
                    isDeleteModalOpen.value = false;
                    itemToDelete.value = null;
                    deleteTargetType.value = null;
                },
            },
        );
    }
}

// Function for saving order of module and object
function saveOrder() {
    const mappedModules = localModules.value.map((m, index) => ({
        id: m.id,
        position: index + 1,
    }));

    const mappedObjects: any[] = [];
    localModules.value.forEach((m) => {
        if (m.objects) {
            m.objects.forEach((o: any, oIdx: number) => {
                mappedObjects.push({
                    id: o.id,
                    module_id: m.id,
                    position: oIdx + 1,
                });
            });
        }
    });

    // We have two endpoints in backend, let's call them sequentially using router.post
    router.post(
        reorderModule(props.classroom.slug).url,
        { modules: mappedModules },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (mappedObjects.length > 0) {
                    router.post(
                        reorderObjects(props.classroom.slug).url,
                        { objects: mappedObjects },
                        {
                            preserveScroll: true,
                            onSuccess: () => {
                                orderingMode.value = false;
                            },
                        },
                    );
                } else {
                    orderingMode.value = false;
                }
            },
        },
    );
}

function cancelOrder() {
    orderingMode.value = false;

    if (props.classroom && props.classroom.modules) {
        localModules.value = JSON.parse(
            JSON.stringify(props.classroom.modules),
        );
    }
}

// UTree formatting
const treeItems = computed<TreeItem[]>(() => {
    if (!props.classroom?.modules) {
        return [];
    }

    return props.classroom.modules.map((m: any) => ({
        label: m.title,
        original: m,
        children: m.objects
            ? m.objects.map((o: any) => ({
                  label: o.object?.title || 'Unknown',
                  icon: o.object_type.includes('Quiz')
                      ? 'heroicons:question-mark-circle'
                      : 'heroicons:light-bulb',
                  original: o,
              }))
            : [],
    }));
});

function getIcon(iconName?: string) {
    if (!iconName) {
        return 'i-lucide-file-text';
    }

    if (iconName.startsWith('heroicons:')) {
        const name = iconName.replace('heroicons:', '');

        if (name === 'light-bulb') {
            return 'i-lucide-lightbulb';
        }

        if (name === 'question-mark-circle') {
            return 'i-lucide-help-circle';
        }

        return `i-lucide-${name}`;
    }

    return iconName;
}

function getContextMenuItems(item: any) {
    const isModule = 'children' in item;

    if (isModule) {
        return [
            [
                {
                    label: 'Tambah Materi',
                    icon: 'i-lucide-file-plus',
                    onSelect: () =>
                        openCreateObject(item.original.id, 'learning_content'),
                },
                {
                    label: 'Tambah Kuis',
                    icon: 'i-lucide-help-circle',
                    onSelect: () => openCreateObject(item.original.id, 'quiz'),
                },
            ],
            [
                {
                    label: 'Ubah Nama Modul',
                    icon: 'i-lucide-edit-2',
                    onSelect: () => openEditModule(item.original),
                },
                {
                    label: 'Hapus Modul',
                    icon: 'i-lucide-trash',
                    color: 'error' as const,
                    onSelect: () => confirmDeleteModule(item.original),
                },
            ],
        ];
    } else {
        const isQuiz = item.icon?.includes('question-mark');

        return [
            [
                {
                    label: isQuiz ? 'Edit Pengaturan Kuis' : 'Ubah Nama Materi',
                    icon: isQuiz ? 'i-lucide-settings' : 'i-lucide-file-text',
                    onSelect: () => openEditObject(item.original),
                },
                ...(isQuiz
                    ? [
                          {
                              label: 'Edit Soal Kuis',
                              icon: 'i-lucide-edit',
                              onSelect: () => emit('edit-quiz', item.original),
                          },
                      ]
                    : [
                          {
                              label: 'Edit Isi Materi',
                              icon: 'i-lucide-edit',
                              onSelect: () =>
                                  emit('edit-content', item.original),
                          },
                      ]),
            ],
            [
                {
                    label: 'Hapus Objek',
                    icon: 'i-lucide-trash',
                    color: 'error' as const,
                    onSelect: () => confirmDeleteObject(item.original),
                },
            ],
        ];
    }
}
</script>

<template>
    <Card
        class="flex h-full flex-col gap-0 px-4"
        :class="isManage ? 'w-full' : 'w-1/3'"
    >
        <!-- Header -->
        <div
            class="mb-4 flex shrink-0 items-center justify-between border-b border-border pb-3"
        >
            <div>
                <h1 class="text-xl font-semibold text-highlighted">
                    <span v-if="orderingMode">Urutkan </span>Objek Pembelajaran
                    di Kelas
                </h1>
                <p v-if="orderingMode" class="mt-0.5 text-xs text-muted">
                    Drag & drop untuk menyusun modul dan materi
                </p>
            </div>
            <template v-if="isManage">
                <div class="animate-fade-in flex items-center gap-2">
                    <Button
                        variant="outline"
                        @click="orderingMode = true"
                        v-if="!orderingMode && treeItems.length > 0"
                    >
                        <ArrowUpDown class="size-4" />
                        Urutkan Objek Kelas
                    </Button>
                    <Button @click="openCreateModule" v-if="!orderingMode">
                        <Plus class="size-4" />
                        Tambah Modul
                    </Button>
                </div>
                <div
                    v-if="orderingMode"
                    class="animate-fade-in flex items-center gap-2"
                >
                    <Button
                        variant="outline"
                        @click="cancelOrder"
                        :disabled="reorderForm.processing"
                    >
                        Batal
                    </Button>
                    <Button
                        @click="saveOrder"
                        :loading="reorderForm.processing"
                        :disabled="!hasOrderingChanges"
                    >
                        <Check class="size-4" />
                        Simpan
                    </Button>
                </div>
            </template>
            <template v-else>
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger>
                            <Button
                                variant="ghost"
                                size="icon"
                                @click="emit('return-manage')"
                            >
                                <UIcon name="i-lucide-x" class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent
                            >Kembali ke Manajemen Objek
                            Pembelajaran</TooltipContent
                        >
                    </Tooltip>
                </TooltipProvider>
            </template>
        </div>

        <!-- Content -->
        <div class="min-h-0 flex-1 overflow-y-auto pr-1">
            <template v-if="orderingMode">
                <VueDraggable
                    v-model="localModules"
                    :animation="150"
                    handle=".module-drag-handle"
                    class="space-y-4"
                >
                    <div
                        v-for="module in localModules"
                        :key="module.id"
                        class="rounded-xl border border-border bg-muted/20 p-4 shadow-sm transition-all duration-200 hover:shadow-md dark:bg-muted/5"
                    >
                        <!-- Module Header -->
                        <div
                            class="mb-3 flex items-center justify-between border-b border-border/55 pb-2"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="module-drag-handle cursor-grab rounded p-1 text-muted-foreground transition-colors hover:bg-muted/50 hover:text-foreground active:cursor-grabbing"
                                    title="Geser Modul"
                                >
                                    <UIcon
                                        name="i-lucide-grip-vertical"
                                        class="h-5 w-5"
                                    />
                                </span>
                                <UIcon
                                    name="i-lucide-book-open"
                                    class="h-5 w-5 text-primary"
                                />
                                <span
                                    class="text-sm font-bold text-highlighted"
                                    :style="
                                        module.color
                                            ? { color: module.color }
                                            : {}
                                    "
                                    >{{ module.title }}</span
                                >
                            </div>
                        </div>

                        <!-- Nested Materials/Quizzes List -->
                        <VueDraggable
                            v-model="module.objects"
                            :animation="150"
                            handle=".item-drag-handle"
                            group="module-items"
                            class="min-h-[50px] space-y-2 pl-6"
                        >
                            <div
                                v-for="item in module.objects"
                                :key="item.id"
                                class="flex items-center justify-between rounded-lg border border-border bg-card p-3 text-card-foreground shadow-sm transition-all duration-150 hover:bg-muted/10 hover:shadow"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="item-drag-handle cursor-grab rounded p-1 text-muted-foreground transition-colors hover:bg-muted/50 hover:text-foreground active:cursor-grabbing"
                                        title="Geser Materi"
                                    >
                                        <UIcon
                                            name="i-lucide-grip-vertical"
                                            class="h-4 w-4"
                                        />
                                    </span>
                                    <UIcon
                                        :name="
                                            item.object_type.includes('Quiz')
                                                ? 'i-lucide-help-circle'
                                                : 'i-lucide-lightbulb'
                                        "
                                        class="h-4 w-4 text-muted-foreground"
                                    />
                                    <span class="text-sm font-medium">{{
                                        item.object?.title || item.title
                                    }}</span>
                                </div>
                            </div>
                        </VueDraggable>
                    </div>
                </VueDraggable>
            </template>

            <!-- Empty State -->
            <template v-else-if="treeItems.length === 0">
                <div
                    class="flex h-full flex-col items-center justify-center gap-4 py-12 text-muted-foreground"
                >
                    <UIcon
                        name="i-heroicons-cursor-arrow-rays"
                        class="h-12 w-12 text-muted-foreground/50"
                    />
                    <p>
                        Belum ada modul yang dibuat. Mulai dengan membuat modul
                        baru.
                    </p>
                </div>
            </template>

            <!-- Read-only UTree view -->
            <template v-else>
                <UTree
                    expanded-icon="i-lucide-book-open"
                    collapsed-icon="i-lucide-book"
                    color="primary"
                    :items="treeItems"
                >
                    <template #item="{ item, expanded }">
                        <UContextMenu
                            :items="getContextMenuItems(item)"
                            class="w-full"
                        >
                            <div
                                class="flex w-full items-center justify-between gap-2 py-0.5 select-none"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <UIcon
                                        v-if="item.icon"
                                        :name="getIcon(item.icon)"
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                    <UIcon
                                        v-else-if="item.children?.length"
                                        :name="
                                            expanded
                                                ? 'i-lucide-book-open'
                                                : 'i-lucide-book'
                                        "
                                        class="h-4 w-4 shrink-0 text-primary"
                                    />
                                    <span
                                        class="truncate text-sm font-medium text-highlighted"
                                        :style="
                                            item.original?.color
                                                ? { color: item.original.color }
                                                : {}
                                        "
                                        >{{ item.label }}</span
                                    >
                                </div>
                                <UIcon
                                    v-if="item.children?.length"
                                    :name="
                                        expanded
                                            ? 'i-lucide-chevron-down'
                                            : 'i-lucide-chevron-right'
                                    "
                                    class="h-4 w-4 shrink-0 text-muted-foreground"
                                />
                            </div>
                        </UContextMenu>
                    </template>
                </UTree>
            </template>
        </div>

        <!-- Modal Modul -->
        <Dialog v-model:open="isModuleModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            moduleForm.id
                                ? 'Ubah Nama Modul'
                                : 'Buat Modul Baru'
                        }}
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitModule" class="space-y-4">
                    <div class="grid gap-2">
                        <Label required>Judul Modul</Label>
                        <Input
                            v-model="moduleForm.title"
                            placeholder="Contoh: Pengenalan Dasar"
                            autofocus
                        />
                        <p
                            v-if="moduleForm.errors.title"
                            class="text-sm text-red-500"
                        >
                            {{ moduleForm.errors.title }}
                        </p>
                    </div>

                    <!-- Color Picker Section -->
                    <div class="grid gap-2 border-t border-border pt-4">
                        <div class="flex items-center justify-between">
                            <Label>Warna Modul (Opsional)</Label>
                            <Button
                                v-if="moduleForm.color"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-6 px-2 text-xs text-red-500 hover:bg-transparent hover:text-red-600"
                                @click="moduleForm.color = null"
                            >
                                Hapus Warna
                            </Button>
                        </div>
                        <div class="rounded-md border border-input p-1">
                            <ColorPicker
                                v-model="moduleForm.color"
                                class="p-1"
                            />
                        </div>
                        <p
                            v-if="moduleForm.errors.color"
                            class="text-sm text-red-500"
                        >
                            {{ moduleForm.errors.color }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isModuleModalOpen = false"
                            :disabled="moduleForm.processing"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="moduleForm.processing"
                            >Simpan</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal Objek (Materi/Kuis) -->
        <Dialog v-model:open="isObjectModalOpen">
            <DialogContent class="sm:max-w-106.25">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            objectForm.id
                                ? 'Edit Objek'
                                : objectForm.type === 'quiz'
                                  ? 'Tambah Kuis'
                                  : 'Tambah Materi'
                        }}
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitObject" class="space-y-4">
                    <div class="grid gap-2">
                        <Label required>Judul</Label>
                        <Input
                            v-model="objectForm.title"
                            placeholder="Masukkan judul"
                            autofocus
                        />
                        <p
                            v-if="objectForm.errors.title"
                            class="text-sm text-red-500"
                        >
                            {{ objectForm.errors.title }}
                        </p>
                    </div>

                    <template v-if="objectForm.type === 'quiz'">
                        <div class="grid gap-2">
                            <Label>Deskripsi Kuis</Label>
                            <textarea
                                v-model="objectForm.description"
                                placeholder="Instruksi pengerjaan kuis..."
                                class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            ></textarea>
                            <p
                                v-if="objectForm.errors.description"
                                class="text-sm text-red-500"
                            >
                                {{ objectForm.errors.description }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label required>Nilai Kelulusan (Minimum)</Label>
                            <Input
                                v-model="objectForm.passing_grade"
                                type="number"
                                min="0"
                                max="100"
                            />
                            <p
                                v-if="objectForm.errors.passing_grade"
                                class="text-sm text-red-500"
                            >
                                {{ objectForm.errors.passing_grade }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label required>Waktu Kuis (Menit)</Label>
                            <Input
                                v-model="objectForm.time_limit"
                                type="number"
                                min="1"
                            />
                            <p
                                v-if="objectForm.errors.time_limit"
                                class="text-sm text-red-500"
                            >
                                {{ objectForm.errors.time_limit }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label :required="!isUnlimited">Batas Percobaan Kuis</Label>
                            <div class="flex items-center">
                                <div class="flex -space-x-px rounded-md shadow-xs">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="rounded-r-none px-3"
                                        :class="isUnlimited ? 'bg-accent text-accent-foreground font-bold' : ''"
                                        @click="setUnlimited(true)"
                                    >
                                        <InfinityIcon class="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="rounded-l-none px-3"
                                        :class="!isUnlimited ? 'bg-accent text-accent-foreground font-bold' : ''"
                                        @click="setUnlimited(false)"
                                    >
                                        <span class="text-xs font-semibold">123</span>
                                    </Button>
                                </div>
                                <div class="flex-1 ml-2">
                                    <Input
                                        v-if="isUnlimited"
                                        type="text"
                                        disabled
                                        model-value="Tak Terbatas"
                                        class="bg-muted text-muted-foreground cursor-not-allowed"
                                    />
                                    <Input
                                        v-else
                                        v-model="objectForm.max_attempts"
                                        type="number"
                                        min="1"
                                        required
                                        placeholder="Masukkan jumlah percobaan"
                                    />
                                </div>
                            </div>
                            <p
                                v-if="objectForm.errors.max_attempts"
                                class="text-sm text-red-500"
                            >
                                {{ objectForm.errors.max_attempts }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label required>Minimal Percobaan untuk Melihat Pembahasan</Label>
                            <Input
                                v-model="objectForm.min_attempts_for_solution"
                                type="number"
                                min="1"
                                required
                            />
                            <p
                                v-if="objectForm.errors.min_attempts_for_solution"
                                class="text-sm text-red-500"
                            >
                                {{ objectForm.errors.min_attempts_for_solution }}
                            </p>
                        </div>
                    </template>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isObjectModalOpen = false"
                            :disabled="objectForm.processing"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="objectForm.processing"
                            >Simpan</Button
                        >
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Modal Konfirmasi Hapus -->
        <Dialog v-model:open="isDeleteModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Konfirmasi Hapus</DialogTitle>
                </DialogHeader>

                <div class="space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ deleteMessage }}
                    </p>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isDeleteModalOpen = false"
                            :disabled="deleteForm.processing"
                            >Batal</Button
                        >
                        <Button
                            variant="destructive"
                            :disabled="deleteForm.processing"
                            @click="submitDelete"
                            >Hapus</Button
                        >
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    </Card>
</template>
