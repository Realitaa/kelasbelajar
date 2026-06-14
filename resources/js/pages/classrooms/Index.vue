<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { Plus, BookOpen, AlertTriangle } from '@lucide/vue';
import { ref, computed } from 'vue';
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

const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

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
        router.delete(
            ClassroomController.destroy.url(classroomToDelete.value),
            {
                onSuccess: () => {
                    isDeleteDialogOpen.value = false;
                    classroomToDelete.value = null;
                },
            },
        );
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
        <Head
            :title="userRole === 'educator' ? 'Manajemen Kelas' : 'Kelas Saya'"
        />

        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Dashboard Greeting Header -->
            <PageHeader
                :title="
                    userRole === 'educator' ? 'Manajemen Kelas' : 'Kelas Saya'
                "
                :description="
                    userRole === 'educator'
                        ? 'Kelola kelas-kelas Anda, publikasikan materi, dan pantau siswa.'
                        : 'Lihat kelas yang Anda ikuti dan mulai belajar.'
                "
                :links="
                    userRole === 'educator'
                        ? {
                              title: 'Buat Kelas Baru',
                              onClick: openCreateDialog,
                              icon: Plus,
                          }
                        : undefined
                "
            />

            <!-- Main Content Grid / Empty State -->
            <div
                v-if="classrooms.length === 0"
                class="flex animate-in flex-col items-center justify-center rounded-xl border border-dashed bg-card/50 p-16 text-center shadow-xs backdrop-blur-xs duration-500 fade-in"
            >
                <div class="rounded-full bg-primary/15 p-5 text-primary">
                    <BookOpen class="h-12 w-12" />
                </div>
                <h3 class="mt-6 text-xl font-bold tracking-tight">
                    {{
                        userRole === 'educator'
                            ? 'Belum Ada Kelas'
                            : 'Belum Bergabung dengan Kelas'
                    }}
                </h3>
                <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                    {{
                        userRole === 'educator'
                            ? 'Anda belum membuat kelas apapun. Mulai buat kelas pertama Anda dan bagikan kodenya kepada murid.'
                            : 'Anda belum bergabung ke kelas manapun. Silakan pergi ke halaman discovery untuk mendaftar ke kelas.'
                    }}
                </p>
                <Button
                    v-if="userRole === 'educator'"
                    @click="openCreateDialog"
                    class="mt-6 gap-2"
                >
                    <Plus class="h-4 w-4" />
                    Buat Kelas Pertama
                </Button>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
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
                    <DialogTitle>{{
                        isEditing ? 'Edit Kelas' : 'Buat Kelas Baru'
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            isEditing
                                ? 'Perbarui informasi kelas Anda di bawah ini.'
                                : 'Isi formulir berikut untuk membuat kelas baru.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitForm" class="space-y-4 py-4">
                    <div class="grid gap-2">
                        <Label for="title" required>Nama Kelas</Label>
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
                            class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 md:text-sm dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
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
                    <DialogTitle
                        class="flex items-center gap-2 text-destructive"
                    >
                        <AlertTriangle class="h-5 w-5" />
                        Hapus Kelas
                    </DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus kelas ini? Tindakan
                        ini tidak dapat dibatalkan dan semua data terkait akan
                        dihapus.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="mt-4 gap-2 sm:justify-end">
                    <DialogClose as-child>
                        <Button type="button" variant="outline"> Batal </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant="destructive"
                        @click="executeDelete"
                    >
                        Ya, Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
