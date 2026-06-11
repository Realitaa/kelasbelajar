<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Edit, Eye, Trash2, Copy, Globe, EyeOff } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import { toast } from 'vue-sonner';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { manage } from '@/routes/classrooms';
import type { Classroom } from '@/types';

defineProps<{
    classroom: Classroom;
}>();

defineEmits<{
    (e: 'edit', classroom: Classroom): void;
    (e: 'delete', id: number): void;
    (e: 'publish', classroom: Classroom): void;
    (e: 'unpublish', classroom: Classroom): void;
}>();

const page = usePage();
const userRole = page.props.auth.user.role;
const { copy } = useClipboard();
const { resolvedAppearance } = useAppearance();

function copyClassroomCode(code: string) {
    copy(code);
    toast.success(`Kode kelas ${code} berhasil disalin`);
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
                <Link :href="manage(classroom.slug)">
                    <h3
                        class="line-clamp-1 text-xl font-bold tracking-tight transition-colors group-hover:text-primary hover:underline"
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
                        as-child
                        class="h-10 w-10 rounded-full transition-colors hover:bg-primary/10 hover:text-primary"
                    >
                        <Link href="#">
                            <Eye class="h-5 w-5" />
                        </Link>
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
        </div>
    </div>
</template>
