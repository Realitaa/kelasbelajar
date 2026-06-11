<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Plus, BookOpen, AlertTriangle } from '@lucide/vue';
import { ref } from 'vue';
import ClassroomController from '@/actions/App/Http/Controllers/ClassroomController';
import ClassroomCard from '@/components/classroom/ClassroomCard.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogClose,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Classroom } from '@/types';

defineProps<{
    classrooms: Classroom[];
}>();

const isDialogOpen = ref(false);
const isEditing = ref(false);
const selectedClassroomId = ref<number | null>(null);

const form = useForm({
    title: '',
    description: '',
});

const openCreateDialog = () => {
    isEditing.value = false;
    selectedClassroomId.value = null;
    form.reset();
    form.clearErrors();
    isDialogOpen.value = true;
};

const openEditDialog = (classroom: Classroom) => {
    isEditing.value = true;
    selectedClassroomId.value = classroom.id;
    form.title = classroom.title;
    form.description = classroom.description || '';
    form.clearErrors();
    isDialogOpen.value = true;
};

const submitForm = () => {
    if (isEditing.value && selectedClassroomId.value) {
        form.put(ClassroomController.update.url(selectedClassroomId.value), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(ClassroomController.store.url(), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    }
};

const isDeleteDialogOpen = ref(false);
const classroomToDelete = ref<number | null>(null);

const confirmDelete = (id: number) => {
    classroomToDelete.value = id;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (classroomToDelete.value) {
        router.delete(ClassroomController.destroy.url(classroomToDelete.value), {
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                classroomToDelete.value = null;
            },
        });
    }
};

const publishClassroom = (classroom: Classroom) => {
    router.post(ClassroomController.publish.url(classroom.id));
};

const unpublishClassroom = (classroom: Classroom) => {
    router.post(ClassroomController.unpublish.url(classroom.id));
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Daftar Kelas',
                href: '#',
            },
        ],
    },
});
</script>

<template>
    <div>
        <Head title="Manajemen Kelas" />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Dashboard Greeting Header -->
            <PageHeader
                title="Manajemen Kelas"
                description="Kelola kelas-kelas Anda, publikasikan materi, dan pantau siswa."
                :links="{
                    title: 'Buat Kelas Baru',
                    onClick: openCreateDialog,
                    icon: Plus
                }"
            />

            <!-- Main Content Grid / Empty State -->
            <div v-if="classrooms.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed p-16 text-center shadow-xs bg-card/50 backdrop-blur-xs animate-in fade-in duration-500">
                <div class="rounded-full bg-primary/15 p-5 text-primary">
                    <BookOpen class="h-12 w-12" />
                </div>
                <h3 class="mt-6 text-xl font-bold tracking-tight">Belum Ada Kelas</h3>
                <p class="mt-2 text-sm text-muted-foreground max-w-sm">
                    Anda belum membuat kelas apapun. Mulai buat kelas pertama Anda dan bagikan kodenya kepada murid.
                </p>
                <Button @click="openCreateDialog" class="mt-6 gap-2">
                    <Plus class="h-4 w-4" />
                    Buat Kelas Pertama
                </Button>
            </div>

            <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <ClassroomCard
                    v-for="classroom in classrooms"
                    :key="classroom.id"
                    :classroom="classroom"
                    @edit="openEditDialog"
                    @delete="confirmDelete"
                    @publish="publishClassroom"
                    @unpublish="unpublishClassroom"
                />
            </div>
        </div>

        <!-- Dialog: Create / Edit Classroom -->
        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Kelas' : 'Buat Kelas Baru' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Perbarui informasi kelas Anda di bawah ini.' : 'Isi formulir berikut untuk membuat kelas baru.' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4 py-4">
                    <div class="grid gap-2">
                        <Label for="title" important>Nama Kelas</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            required
                            placeholder="Contoh: Matematika Dasar"
                            class="col-span-3"
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Deskripsi</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Deskripsi singkat mengenai kelas ini..."
                            class="placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive"
                        ></textarea>
                        <InputError :message="form.errors.description" />
                    </div>

                    <DialogFooter class="pt-4">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                Batal
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="form.processing">
                            {{ isEditing ? 'Simpan Perubahan' : 'Buat Kelas' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog: Delete Confirmation -->
        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle class="text-destructive flex items-center gap-2">
                        <AlertTriangle class="h-5 w-5" />
                        Hapus Kelas
                    </DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus kelas ini? Tindakan ini tidak dapat dibatalkan dan semua data terkait akan dihapus.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="mt-4 gap-2 sm:justify-end">
                    <DialogClose as-child>
                        <Button type="button" variant="outline">
                            Batal
                        </Button>
                    </DialogClose>
                    <Button type="button" variant="destructive" @click="executeDelete">
                        Ya, Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
