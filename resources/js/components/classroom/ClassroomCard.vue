<script setup lang="ts">
import { Link, usePage, useHttp } from '@inertiajs/vue3';
import { Edit, Trash2, Copy, Globe, EyeOff, Users, Check, UserPlus } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import ClassroomController from '@/actions/App/Http/Controllers/ClassroomController';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { useAppearance } from '@/composables/useAppearance';
import { manage } from '@/routes/classrooms';
import type { Classroom } from '@/types';

const props = withDefaults(
    defineProps<{
        classroom: Classroom;
        showEnrollAction?: boolean;
    }>(),
    {
        showEnrollAction: false,
    }
);

defineEmits<{
    (e: 'edit', classroom: Classroom): void;
    (e: 'delete', id: number): void;
    (e: 'publish', classroom: Classroom): void;
    (e: 'unpublish', classroom: Classroom): void;
    (e: 'enroll', classroom: Classroom): void;
}>();

const isEnrolled = computed(() => {
    return !!(props.classroom.enrollments && props.classroom.enrollments.length > 0);
});

const page = usePage();
const userRole = page.props.auth.user.role;
const { copy } = useClipboard();
const { resolvedAppearance } = useAppearance();

function copyClassroomCode(code: string) {
    copy(code);
    toast.success(`Kode kelas ${code} berhasil disalin`);
}

const isStudentsModalOpen = ref(false);
const students = ref<any[]>([]);
const http = useHttp();

function showStudents() {
    isStudentsModalOpen.value = true;
    http.get(ClassroomController.students.url(props.classroom.slug), {
        onSuccess: (response: any) => {
            students.value = response.data || [];
        },
        onError: (err: any) => {
            toast.error('Gagal mengambil daftar siswa');
            console.error(err);
        }
    });
}

function formatDate(dateString: string) {
    if (!dateString) {
return '';
}

    const date = new Date(dateString);

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}
</script>

<template>
    <div
        class="group relative flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition-all hover:shadow-md"
    >
        <!-- Header Image Area -->
        <div class="relative h-28 w-full overflow-hidden">
            <img
                :src="`images/default_classroom_${resolvedAppearance}.png`"
                alt="Classroom cover"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
            />
            <!-- Faded effect on lower part of the image -->
            <div
                class="absolute inset-0 bg-linear-to-t from-card via-transparent to-transparent opacity-90"
            ></div>
            <!-- Status Badge -->
            <div class="absolute top-3 left-3 z-10">
                <Badge
                    :variant="classroom.is_published ? 'default' : 'secondary'"
                >
                    {{ classroom.is_published ? 'Terpublikasi' : 'Draf' }}
                </Badge>
            </div>
        </div>

        <!-- Avatar Overlapping -->
        <div class="absolute top-16 right-4 z-10">
            <Avatar
                class="h-20 w-20 border-4 border-card shadow-sm ring-1 ring-border/50"
            >
                <AvatarFallback
                    class="bg-slate-500 text-2xl font-bold text-white"
                >
                    {{ classroom.title.charAt(0).toUpperCase() }}
                </AvatarFallback>
            </Avatar>
        </div>

        <!-- Content Area -->
        <div class="flex flex-1 flex-col p-6 pt-2">
            <div class="mb-4">
                <Link v-if="userRole === 'educator'" :href="manage(classroom.slug)">
                    <h3
                        class="line-clamp-1 text-xl font-bold tracking-tight transition-colors group-hover:text-primary hover:underline"
                    >
                        {{ classroom.title }}
                    </h3>
                </Link>
                <Link v-else :href="`/classrooms/${classroom.slug}`">
                    <h3
                        class="line-clamp-1 text-xl font-bold tracking-tight transition-colors group-hover:text-primary hover:underline max-w-[85%]"
                    >
                        {{ classroom.title }}
                    </h3>
                </Link>
                <p
                    @click="copyClassroomCode(classroom.unique_code)"
                    title="Salin kode kelas"
                    class="line-clamp-1 flex cursor-pointer items-center gap-2 font-mono text-xs font-medium tracking-wider text-muted-foreground"
                >
                    <span> Kode: {{ classroom.unique_code }} </span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6 cursor-pointer"
                    >
                        <Copy class="h-4 w-4" />
                    </Button>
                </p>
                <p
                    v-if="userRole === 'student' && classroom.educator"
                    class="mt-1 text-xs text-muted-foreground"
                >
                    Pengajar: <span class="font-medium text-foreground">{{ classroom.educator.name }}</span>
                </p>
            </div>

            <p class="line-clamp-2 min-h-10 text-sm text-muted-foreground">
                {{
                    classroom.description ||
                    'Tidak ada deskripsi untuk kelas ini.'
                }}
            </p>

            <!-- Actions Area (Footer) -->
            <div
                v-if="userRole === 'educator'"
                class="mt-6 flex items-center justify-between border-t pt-4"
            >
                <div class="flex gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        @click="showStudents"
                        class="h-10 w-10 rounded-full transition-colors hover:bg-primary/10 hover:text-primary"
                        title="Lihat Siswa Terdaftar"
                    >
                        <Users class="h-5 w-5" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        @click="$emit('edit', classroom)"
                        class="h-10 w-10 rounded-full transition-colors hover:bg-primary/10 hover:text-primary"
                        title="Edit Kelas"
                    >
                        <Edit class="h-5 w-5" />
                    </Button>
                    <Button
                        v-if="classroom.is_published"
                        variant="ghost"
                        size="icon"
                        @click="$emit('unpublish', classroom)"
                        class="h-10 w-10 rounded-full transition-colors hover:bg-amber-100 hover:text-amber-600 dark:hover:bg-amber-900/30 dark:hover:text-amber-500"
                        title="Batalkan Publikasi"
                    >
                        <EyeOff class="h-5 w-5" />
                    </Button>
                    <Button
                        v-else
                        variant="ghost"
                        size="icon"
                        @click="$emit('publish', classroom)"
                        class="h-10 w-10 rounded-full transition-colors hover:bg-emerald-100 hover:text-emerald-600 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-500"
                        title="Publikasikan Kelas"
                    >
                        <Globe class="h-5 w-5" />
                    </Button>
                </div>

                <Button
                    variant="ghost"
                    size="icon"
                    @click="$emit('delete', classroom.id)"
                    class="h-10 w-10 rounded-full text-destructive transition-colors hover:bg-destructive/10 hover:text-destructive"
                    title="Hapus Kelas"
                >
                    <Trash2 class="h-5 w-5" />
                </Button>
            </div>

            <!-- Student Discovery Enroll Footer -->
            <div
                v-if="userRole === 'student' && showEnrollAction"
                class="mt-6 border-t pt-4"
            >
                <div
                    v-if="isEnrolled"
                    class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 py-2.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400 select-none animate-in fade-in zoom-in duration-300"
                >
                    <Check class="h-4 w-4" />
                    <span>Sudah Bergabung</span>
                </div>
                <Button
                    v-else
                    @click="$emit('enroll', classroom)"
                    class="w-full gap-1.5 py-2.5 font-bold transition-all hover:scale-[1.01] hover:shadow-xs active:scale-[0.99] cursor-pointer"
                >
                    <UserPlus class="h-4 w-4" />
                    <span>Gabung Kelas</span>
                </Button>
            </div>
        </div>

        <Dialog v-model:open="isStudentsModalOpen">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-xl font-bold tracking-tight">
                        <Users class="h-5 w-5 text-primary" />
                        <span>Siswa Terdaftar</span>
                    </DialogTitle>
                    <DialogDescription class="text-sm text-muted-foreground mt-1">
                        Daftar siswa yang telah bergabung ke kelas
                        <span class="font-semibold text-foreground bg-primary/10 px-1.5 py-0.5 rounded-sm">{{ classroom.title }}</span>.
                    </DialogDescription>
                </DialogHeader>

                <!-- Skeleton Loader -->
                <div v-if="http.processing" class="space-y-4 py-4">
                    <div v-for="i in 3" :key="i" class="flex items-center space-x-4 animate-pulse">
                        <div class="h-10 w-10 rounded-full bg-muted"></div>
                        <div class="space-y-2 flex-1">
                            <div class="h-4 bg-muted rounded w-1/3"></div>
                            <div class="h-3 bg-muted rounded w-1/2"></div>
                        </div>
                    </div>
                </div>

                <!-- Student List / Empty State -->
                <div v-else class="py-4">
                    <div v-if="students.length === 0" class="flex flex-col items-center justify-center py-8 text-center animate-in fade-in duration-300">
                        <div class="rounded-full bg-primary/10 p-4 text-primary mb-3 ring-8 ring-primary/5">
                            <Users class="h-8 w-8" />
                        </div>
                        <p class="text-sm font-semibold text-foreground">Belum Ada Siswa</p>
                        <p class="text-xs text-muted-foreground mt-1.5 max-w-[280px]">
                            Bagikan kode kelas
                            <span
                                @click="copyClassroomCode(classroom.unique_code)"
                                title="Klik untuk menyalin kode"
                                class="font-mono bg-muted hover:bg-muted/80 cursor-pointer px-1.5 py-0.5 rounded font-semibold text-foreground border border-border/60 transition-colors"
                            >
                                {{ classroom.unique_code }}
                            </span>
                            kepada siswa untuk mulai belajar bersama.
                        </p>
                    </div>

                    <div v-else class="max-h-[300px] overflow-y-auto space-y-3 pr-1 animate-in fade-in duration-300">
                        <div
                            v-for="student in students"
                            :key="student.id"
                            class="flex items-center justify-between p-2.5 rounded-xl hover:bg-muted/60 border border-transparent hover:border-border/40 transition-all duration-200"
                        >
                            <div class="flex items-center gap-3">
                                <Avatar class="h-10 w-10 shadow-xs ring-1 ring-border/30">
                                    <AvatarFallback class="bg-primary/10 text-primary font-bold text-sm">
                                        {{ student.name.charAt(0).toUpperCase() }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p class="text-sm font-semibold leading-none text-foreground">{{ student.name }}</p>
                                    <p class="text-xs text-muted-foreground mt-1.5 select-all">{{ student.email }}</p>
                                </div>
                            </div>
                            <span class="text-[10px] text-muted-foreground bg-muted/80 border px-2.5 py-1 rounded-full font-medium">
                                {{ formatDate(student.enrolled_at) }}
                            </span>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
