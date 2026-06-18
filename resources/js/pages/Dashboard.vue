<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Users,
    BookOpen,
    FileText,
    MessageSquare,
    ArrowUpRight,
    Circle,
} from '@lucide/vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

defineProps<{
    stats: {
        users: {
            total: number;
            student: number;
            educator: number;
            administrator: number;
        };
        classrooms: {
            total: number;
            published: number;
            draft: number;
        };
        enrollments: {
            total: number;
        };
        quizzes: {
            total: number;
            submissions: number;
        };
        comments: {
            total: number;
        };
        content: {
            modules: number;
            learning_contents: number;
        };
    };
    recentUsers: Array<{
        id: number;
        name: string;
        email: string;
        role: string;
        created_at: string;
    }>;
    recentClassrooms: Array<{
        id: number;
        title: string;
        slug: string;
        educator: {
            id: number;
            name: string;
        } | null;
        enrollments_count: number;
        created_at: string;
    }>;
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const getRoleBadge = (role: string) => {
    switch (role) {
        case 'administrator':
            return { text: 'Admin', variant: 'destructive' as const };
        case 'educator':
            return { text: 'Pengajar', variant: 'default' as const };
        case 'student':
        default:
            return { text: 'Siswa', variant: 'secondary' as const };
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Dashboard Greeting Header -->
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-bold tracking-tight">Statistik Sistem</h1>
            <p class="text-sm text-muted-foreground">
                Ringkasan performa dan data aktivitas sistem KelasBelajar.
            </p>
        </div>

        <!-- Stats Grid (Solid Modern Styling, No Gradients) -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <!-- Card 1: Users -->
            <Card
                class="transition-all duration-200 hover:border-muted-foreground/30 hover:shadow-md"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Total Pengguna</CardTitle
                    >
                    <div
                        class="rounded-lg bg-blue-50 p-2 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400"
                    >
                        <Users class="size-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.users.total }}
                    </div>
                    <div
                        class="mt-4 flex flex-col gap-1.5 text-xs text-muted-foreground"
                    >
                        <div class="flex items-center justify-between">
                            <span>Siswa:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.users.student
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Pengajar:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.users.educator
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Administrator:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.users.administrator
                            }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Card 2: Classrooms -->
            <Card
                class="transition-all duration-200 hover:border-muted-foreground/30 hover:shadow-md"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Total Kelas & Pendaftaran</CardTitle
                    >
                    <div
                        class="rounded-lg bg-indigo-50 p-2 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400"
                    >
                        <BookOpen class="size-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.classrooms.total }}
                    </div>
                    <div
                        class="mt-4 flex flex-col gap-1.5 text-xs text-muted-foreground"
                    >
                        <div class="flex items-center justify-between">
                            <span>Diterbitkan:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.classrooms.published
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Draf:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.classrooms.draft
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Siswa Terdaftar:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.enrollments.total
                            }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Card 3: Content & Learning -->
            <Card
                class="transition-all duration-200 hover:border-muted-foreground/30 hover:shadow-md"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Materi Pembelajaran</CardTitle
                    >
                    <div
                        class="rounded-lg bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400"
                    >
                        <FileText class="size-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{
                            stats.content.modules +
                            stats.content.learning_contents
                        }}
                    </div>
                    <div
                        class="mt-4 flex flex-col gap-1.5 text-xs text-muted-foreground"
                    >
                        <div class="flex items-center justify-between">
                            <span>Modul Belajar:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.content.modules
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Konten Pembelajaran:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.content.learning_contents
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Total Kuis:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.quizzes.total
                            }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Card 4: Activity -->
            <Card
                class="transition-all duration-200 hover:border-muted-foreground/30 hover:shadow-md"
            >
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Aktivitas Sistem</CardTitle
                    >
                    <div
                        class="rounded-lg bg-amber-50 p-2 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400"
                    >
                        <MessageSquare class="size-4" />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ stats.quizzes.submissions + stats.comments.total }}
                    </div>
                    <div
                        class="mt-4 flex flex-col gap-1.5 text-xs text-muted-foreground"
                    >
                        <div class="flex items-center justify-between">
                            <span>Percobaan Kuis:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.quizzes.submissions
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Komentar Diskusi:</span>
                            <span class="font-semibold text-foreground">{{
                                stats.comments.total
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Status Server:</span>
                            <span
                                class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400"
                            >
                                <Circle class="size-2 fill-current" /> Aktif
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Details Grid (Recent Users & Recent Classrooms) -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Left Column: Recent Registered Users -->
            <Card class="flex flex-col">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Pengguna Terbaru</CardTitle>
                            <CardDescription
                                >5 pengguna terakhir yang terdaftar dalam
                                sistem.</CardDescription
                            >
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="flex-1">
                    <div class="space-y-4">
                        <div
                            v-for="user in recentUsers"
                            :key="user.id"
                            class="flex items-center justify-between rounded-lg p-2 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex items-center gap-3">
                                <Avatar class="size-9 border border-border">
                                    <AvatarFallback
                                        class="bg-muted text-xs font-semibold"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex flex-col gap-0.5">
                                    <div
                                        class="text-sm leading-none font-medium"
                                    >
                                        {{ user.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ user.email }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge
                                    :variant="getRoleBadge(user.role).variant"
                                >
                                    {{ getRoleBadge(user.role).text }}
                                </Badge>
                                <span
                                    class="text-[10px] text-muted-foreground"
                                    >{{ formatDate(user.created_at) }}</span
                                >
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Right Column: Recent Classrooms -->
            <Card class="flex flex-col">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Kelas Terbaru</CardTitle>
                            <CardDescription
                                >5 kelas terakhir yang dibuat oleh
                                pengajar.</CardDescription
                            >
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="flex-1">
                    <div class="space-y-4">
                        <div
                            v-for="classroom in recentClassrooms"
                            :key="classroom.id"
                            class="flex items-center justify-between rounded-lg p-2 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex flex-col gap-1">
                                <div class="text-sm leading-none font-medium">
                                    {{ classroom.title }}
                                </div>
                                <div
                                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <span
                                        >Oleh:
                                        {{
                                            classroom.educator
                                                ? classroom.educator.name
                                                : 'Sistem'
                                        }}</span
                                    >
                                    <span>&bull;</span>
                                    <span>{{
                                        formatDate(classroom.created_at)
                                    }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <div class="text-xs font-semibold">
                                        {{ classroom.enrollments_count }} Siswa
                                    </div>
                                    <div
                                        class="text-[10px] text-muted-foreground"
                                    >
                                        Terdaftar
                                    </div>
                                </div>
                                <ArrowUpRight
                                    class="size-4 text-muted-foreground"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
