<script setup lang="ts">
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    MessageSquare,
    Trash2,
    Edit2,
    CornerDownRight,
    X,
    Clock,
    Reply,
    Send,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import PreviewRenderer from '@/components/PreviewRenderer.vue';
import RichEditor from '@/components/RichEditor.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { show } from '@/routes/classrooms';
import {
    store as storeComment,
    update as updateComment,
    destroy as destroyComment,
} from '@/routes/classrooms/discussion/comments';
import type { Classroom, ClassroomComment } from '@/types';

const props = defineProps<{
    classroom: Classroom;
    comments: ClassroomComment[];
}>();

defineOptions({
    layout: (props: any) => ({
        breadcrumbs: [
            {
                title: 'Kelas Saya',
                href: '/classrooms',
            },
            {
                title: props.classroom?.title || 'Detail Kelas',
                href: show(props.classroom?.slug || '').url,
            },
            {
                title: 'Forum Diskusi',
                current: true,
            },
        ],
    }),
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

// Form for new comment
const newCommentForm = useForm({
    content: {} as any,
    parent_id: null as number | null,
});

// Form for replies
const activeReplyId = ref<number | null>(null);
const replyForm = useForm({
    content: {} as any,
    parent_id: null as number | null,
});

// Form for edits
const activeEditId = ref<number | null>(null);
const editForm = useForm({
    content: {} as any,
});

function handleReplyToggle(comment: ClassroomComment) {
    if (activeReplyId.value === comment.id) {
        activeReplyId.value = null;
        replyForm.reset();
    } else {
        activeReplyId.value = comment.id;
        activeEditId.value = null; // Close edit form if open
        replyForm.parent_id = comment.id;
        replyForm.content = {};
    }
}

function handleEditToggle(comment: ClassroomComment) {
    if (activeEditId.value === comment.id) {
        activeEditId.value = null;
        editForm.reset();
    } else {
        activeEditId.value = comment.id;
        activeReplyId.value = null; // Close reply form if open
        // Deep copy the comment's content
        editForm.content = JSON.parse(JSON.stringify(comment.content || {}));
    }
}

function submitNewComment() {
    newCommentForm.post(storeComment.url(props.classroom.slug), {
        onSuccess: () => {
            newCommentForm.reset();
            newCommentForm.content = {};
        },
    });
}

function submitReply(parentId: number) {
    replyForm.post(storeComment.url(props.classroom.slug), {
        onSuccess: () => {
            activeReplyId.value = null;
            replyForm.reset();
            replyForm.content = {};
        },
        preserveScroll: true,
    });
}

function submitEdit(commentId: number) {
    editForm.put(
        updateComment.url({
            classroom: props.classroom.slug,
            comment: commentId,
        }),
        {
            onSuccess: () => {
                activeEditId.value = null;
                editForm.reset();
            },
        },
    );
}

function deleteComment(commentId: number) {
    if (confirm('Apakah Anda yakin ingin menghapus komentar ini?')) {
        router.delete(
            destroyComment.url({
                classroom: props.classroom.slug,
                comment: commentId,
            }),
            {
                preserveScroll: true,
            },
        );
    }
}

function formatDate(dateString: string) {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString);

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function isEdited(comment: ClassroomComment) {
    return (
        comment.created_at !== comment.updated_at && comment.deleted_at === null
    );
}

function canDelete(comment: ClassroomComment) {
    // Check if current user is author of comment OR owns the classroom OR is admin
    return (
        currentUser.value &&
        (currentUser.value.id === comment.user_id ||
            currentUser.value.id === props.classroom.educator_id ||
            currentUser.value.role === 'administrator')
    );
}

function canEdit(comment: ClassroomComment) {
    // Check if current user is author of comment
    return currentUser.value && currentUser.value.id === comment.user_id;
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50/50 dark:bg-slate-900/20">
        <Head :title="`Forum Diskusi | ${classroom.title}`" />

        <div
            class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 px-4 py-6 md:px-6"
        >
            <!-- Header Card -->
            <div class="flex items-center justify-between border-b pb-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="show(classroom.slug).url"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border bg-card text-muted-foreground shadow-xs transition-colors hover:bg-muted"
                    >
                        <ArrowLeft class="size-5" />
                    </Link>
                    <div>
                        <h1
                            class="text-2xl font-extrabold tracking-tight text-foreground md:text-3xl"
                        >
                            Forum Diskusi
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ classroom.title }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- New Comment Thread Input -->
            <Card
                class="border border-border/80 bg-card/60 shadow-sm backdrop-blur-xs"
            >
                <CardContent class="p-5">
                    <form @submit.prevent="submitNewComment" class="space-y-4">
                        <div class="flex items-start gap-3">
                            <Avatar class="mt-1 h-9 w-9 ring-1 ring-border">
                                <AvatarFallback
                                    class="bg-primary/10 font-bold text-primary"
                                >
                                    {{
                                        currentUser.name.charAt(0).toUpperCase()
                                    }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <h3
                                    class="text-sm font-semibold text-foreground"
                                >
                                    Mulai diskusi baru
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    Tulis topik baru untuk didiskusikan dengan
                                    teman sekelas
                                </p>
                            </div>
                        </div>

                        <div class="min-h-40 rounded-lg border border-border">
                            <RichEditor
                                v-model="newCommentForm.content"
                                :is-educator="false"
                                placeholder="Bagikan pemikiran, pertanyaan, atau materi di sini..."
                                class="min-h-36"
                            />
                        </div>
                        <div
                            v-if="newCommentForm.errors.content"
                            class="text-xs text-destructive"
                        >
                            {{ newCommentForm.errors.content }}
                        </div>

                        <div class="flex justify-end">
                            <Button
                                type="submit"
                                :disabled="newCommentForm.processing"
                                class="cursor-pointer gap-2 font-semibold shadow-xs"
                            >
                                <Send class="size-4" />
                                <span>Kirim Diskusi</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Comments List -->
            <div class="space-y-6">
                <h2
                    class="flex items-center gap-2 text-lg font-bold tracking-tight text-foreground"
                >
                    <MessageSquare class="size-5 text-primary" />
                    <span>Diskusi Terkini ({{ comments.length }})</span>
                </h2>

                <div
                    v-if="comments.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-border/60 bg-card/40 py-16 text-center"
                >
                    <div
                        class="mb-4 rounded-full bg-primary/10 p-4 text-primary ring-8 ring-primary/5"
                    >
                        <MessageSquare class="size-8" />
                    </div>
                    <h3 class="text-lg font-bold text-foreground">
                        Belum Ada Diskusi
                    </h3>
                    <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                        Belum ada diskusi yang dimulai di kelas ini. Jadilah
                        yang pertama untuk membagikan pertanyaan atau pemikiran
                        Anda!
                    </p>
                </div>

                <div v-else class="space-y-6">
                    <!-- Loop Level 1 Comments -->
                    <div
                        v-for="comment in comments"
                        :key="comment.id"
                        class="space-y-4 border-b border-border/40 pb-6 last:border-0"
                    >
                        <!-- Main Comment Card -->
                        <Card
                            class="border bg-card shadow-xs transition-shadow duration-200 hover:shadow-sm"
                            :class="[
                                comment.deleted_at
                                    ? 'border-dashed border-border bg-slate-50/50 opacity-80 dark:bg-slate-900/10'
                                    : '',
                            ]"
                        >
                            <CardContent class="p-5">
                                <div class="flex items-start gap-3">
                                    <Avatar
                                        class="h-10 w-10 ring-1 ring-border/50"
                                    >
                                        <AvatarFallback
                                            class="bg-primary/10 font-bold text-primary"
                                        >
                                            {{
                                                comment.deleted_at
                                                    ? '?'
                                                    : comment.user.name
                                                          .charAt(0)
                                                          .toUpperCase()
                                            }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <!-- Header: User info -->
                                        <div
                                            class="flex flex-wrap items-center gap-x-2 gap-y-1"
                                        >
                                            <span
                                                class="text-sm font-bold text-foreground"
                                            >
                                                {{
                                                    comment.deleted_at
                                                        ? 'Komentar Dihapus'
                                                        : comment.user.name
                                                }}
                                            </span>
                                            <!-- Educator / Classroom Owner Badge -->
                                            <Badge
                                                v-if="
                                                    !comment.deleted_at &&
                                                    comment.user_id ===
                                                        classroom.educator_id
                                                "
                                                variant="default"
                                                class="bg-blue-600 px-2 py-0 text-[10px] font-bold text-white hover:bg-blue-600"
                                            >
                                                Pemilik Kelas
                                            </Badge>
                                            <span
                                                class="flex items-center gap-1 text-xs text-muted-foreground"
                                            >
                                                <Clock class="size-3.5" />
                                                {{
                                                    formatDate(
                                                        comment.created_at,
                                                    )
                                                }}
                                            </span>
                                            <!-- Edited Badge -->
                                            <Badge
                                                v-if="isEdited(comment)"
                                                variant="secondary"
                                                class="px-1.5 py-0 text-[10px] font-medium"
                                            >
                                                Diubah
                                            </Badge>
                                        </div>

                                        <!-- Render content or Deleted placeholder -->
                                        <div
                                            class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-300"
                                        >
                                            <div
                                                v-if="comment.deleted_at"
                                                class="flex flex-col gap-2 rounded-lg border border-border/40 bg-muted/40 p-2.5 text-muted-foreground italic"
                                            >
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <X
                                                        class="size-4 text-muted-foreground/80"
                                                    />
                                                    <span>{{
                                                        currentUser.role ==
                                                            'educator' ||
                                                        currentUser.role ==
                                                            'administrator'
                                                            ? 'Komentar terhapus (hanya Anda dan Administrator yang dapat melihat):'
                                                            : 'Komentar ini dihapus.'
                                                    }}</span>
                                                </div>
                                                <PreviewRenderer
                                                    v-if="
                                                        currentUser.role ==
                                                            'educator' ||
                                                        currentUser.role ==
                                                            'administrator'
                                                    "
                                                    :content="comment.content"
                                                />
                                            </div>
                                            <div
                                                v-else-if="
                                                    activeEditId === comment.id
                                                "
                                                class="mt-2 space-y-3"
                                            >
                                                <div
                                                    class="min-h-32 rounded-lg border border-border bg-background"
                                                >
                                                    <RichEditor
                                                        v-model="
                                                            editForm.content
                                                        "
                                                        :is-educator="false"
                                                        placeholder="Ubah komentar Anda..."
                                                        class="min-h-28"
                                                    />
                                                </div>
                                                <div
                                                    class="flex justify-end gap-2"
                                                >
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        @click="
                                                            activeEditId = null
                                                        "
                                                        class="cursor-pointer"
                                                    >
                                                        Batal
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        @click="
                                                            submitEdit(
                                                                comment.id,
                                                            )
                                                        "
                                                        :disabled="
                                                            editForm.processing
                                                        "
                                                        class="cursor-pointer font-semibold"
                                                    >
                                                        Simpan
                                                    </Button>
                                                </div>
                                            </div>
                                            <PreviewRenderer
                                                v-else
                                                :content="comment.content"
                                            />
                                        </div>

                                        <!-- Actions (Reply, Edit, Delete) -->
                                        <div
                                            v-if="
                                                !comment.deleted_at &&
                                                activeEditId !== comment.id
                                            "
                                            class="mt-3 flex items-center gap-3 border-t pt-3"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    handleReplyToggle(comment)
                                                "
                                                class="h-8 cursor-pointer gap-1 text-xs font-semibold text-muted-foreground hover:text-primary"
                                            >
                                                <Reply class="size-3.5" />
                                                <span>Balas</span>
                                            </Button>

                                            <Button
                                                v-if="canEdit(comment)"
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    handleEditToggle(comment)
                                                "
                                                class="h-8 cursor-pointer gap-1 text-xs font-semibold text-muted-foreground hover:text-amber-600"
                                            >
                                                <Edit2 class="size-3.5" />
                                                <span>Edit</span>
                                            </Button>

                                            <Button
                                                v-if="canDelete(comment)"
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    deleteComment(comment.id)
                                                "
                                                class="ml-auto h-8 cursor-pointer gap-1 text-xs font-semibold text-destructive hover:bg-destructive/10"
                                            >
                                                <Trash2 class="size-3.5" />
                                                <span>Hapus</span>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Replies Area (Level 2) -->
                        <div class="space-y-4 pl-6 md:pl-10">
                            <!-- Input Box for Reply (Active for current Level 1 comment) -->
                            <div
                                v-if="activeReplyId === comment.id"
                                class="relative pl-2"
                            >
                                <div
                                    class="absolute top-6 -left-6 text-muted-foreground/35 select-none"
                                >
                                    <CornerDownRight class="size-4.5" />
                                </div>

                                <Card
                                    class="border border-border bg-card/40 shadow-2xs"
                                >
                                    <CardContent class="p-4">
                                        <form
                                            @submit.prevent="
                                                submitReply(comment.id)
                                            "
                                            class="space-y-3"
                                        >
                                            <div
                                                class="text-xs font-semibold text-muted-foreground"
                                            >
                                                Membalas komentar
                                                {{ comment.user.name }}
                                            </div>

                                            <div
                                                class="min-h-32 rounded-lg border border-border bg-background"
                                            >
                                                <RichEditor
                                                    v-model="replyForm.content"
                                                    :is-educator="false"
                                                    :placeholder="`Tulis balasan untuk komentar ${comment.user.name}...`"
                                                    class="min-h-28"
                                                />
                                            </div>
                                            <div
                                                v-if="replyForm.errors.content"
                                                class="text-xs text-destructive"
                                            >
                                                {{ replyForm.errors.content }}
                                            </div>

                                            <div class="flex justify-end gap-2">
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        handleReplyToggle(
                                                            comment,
                                                        )
                                                    "
                                                    class="cursor-pointer"
                                                >
                                                    Batal
                                                </Button>
                                                <Button
                                                    type="submit"
                                                    size="sm"
                                                    :disabled="
                                                        replyForm.processing
                                                    "
                                                    class="cursor-pointer gap-1.5 font-semibold shadow-xs"
                                                >
                                                    <Send class="size-3.5" />
                                                    <span>Kirim Balasan</span>
                                                </Button>
                                            </div>
                                        </form>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Show Replies -->
                            <div
                                v-for="reply in comment.replies"
                                :key="reply.id"
                                class="relative flex gap-3"
                            >
                                <!-- Indentation icon/connector -->
                                <div
                                    class="absolute top-4 -left-4 text-muted-foreground/35 select-none"
                                >
                                    <CornerDownRight class="size-4.5" />
                                </div>

                                <Card
                                    class="w-full border bg-card/80 shadow-2xs transition-shadow duration-200 hover:shadow-xs"
                                    :class="[
                                        reply.deleted_at
                                            ? 'border-dashed border-border bg-slate-50/50 opacity-80 dark:bg-slate-900/10'
                                            : '',
                                    ]"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-start gap-2.5">
                                            <Avatar
                                                class="h-8 w-8 ring-1 ring-border/40"
                                            >
                                                <AvatarFallback
                                                    class="bg-primary/5 text-xs font-semibold text-primary"
                                                >
                                                    {{
                                                        reply.deleted_at
                                                            ? '?'
                                                            : reply.user.name
                                                                  .charAt(0)
                                                                  .toUpperCase()
                                                    }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div
                                                class="min-w-0 flex-1 space-y-1"
                                            >
                                                <!-- Header -->
                                                <div
                                                    class="flex flex-wrap items-center gap-x-2 gap-y-1"
                                                >
                                                    <span
                                                        class="text-xs font-bold text-foreground"
                                                    >
                                                        {{
                                                            reply.deleted_at
                                                                ? 'Komentar Dihapus'
                                                                : reply.user
                                                                      .name
                                                        }}
                                                    </span>
                                                    <!-- Owner Badge -->
                                                    <Badge
                                                        v-if="
                                                            !reply.deleted_at &&
                                                            reply.user_id ===
                                                                classroom.educator_id
                                                        "
                                                        variant="default"
                                                        class="bg-blue-600 px-1.5 py-0 text-[9px] font-bold text-white hover:bg-blue-600"
                                                    >
                                                        Pemilik Kelas
                                                    </Badge>
                                                    <span
                                                        class="flex items-center gap-1 text-[10px] text-muted-foreground"
                                                    >
                                                        <Clock class="size-3" />
                                                        {{
                                                            formatDate(
                                                                reply.created_at,
                                                            )
                                                        }}
                                                    </span>
                                                    <!-- Edited Badge -->
                                                    <Badge
                                                        v-if="isEdited(reply)"
                                                        variant="secondary"
                                                        class="px-1 py-0 text-[9px] font-medium"
                                                    >
                                                        Diubah
                                                    </Badge>
                                                </div>

                                                <!-- Content -->
                                                <div
                                                    class="mt-1.5 text-sm leading-relaxed text-slate-700 dark:text-slate-300"
                                                >
                                                    <div
                                                        v-if="reply.deleted_at"
                                                        class="flex flex-col gap-2 rounded-lg border border-border/40 bg-muted/40 p-2 text-xs text-muted-foreground italic"
                                                    >
                                                        <div
                                                            class="flex items-center gap-2"
                                                        >
                                                            <X
                                                                class="size-3.5 text-muted-foreground/80"
                                                            />
                                                            <span>{{
                                                                currentUser.role ==
                                                                    'educator' ||
                                                                currentUser.role ==
                                                                    'administrator'
                                                                    ? 'Komentar terhapus (hanya Anda dan Administrator yang dapat melihat):'
                                                                    : 'Komentar ini dihapus.'
                                                            }}</span>
                                                        </div>
                                                        <PreviewRenderer
                                                            v-if="
                                                                currentUser.role ==
                                                                    'educator' ||
                                                                currentUser.role ==
                                                                    'administrator'
                                                            "
                                                            :content="
                                                                reply.content
                                                            "
                                                        />
                                                    </div>
                                                    <div
                                                        v-else-if="
                                                            activeEditId ===
                                                            reply.id
                                                        "
                                                        class="mt-2 space-y-3"
                                                    >
                                                        <div
                                                            class="min-h-32 rounded-lg border border-border bg-background"
                                                        >
                                                            <RichEditor
                                                                v-model="
                                                                    editForm.content
                                                                "
                                                                :is-educator="
                                                                    false
                                                                "
                                                                placeholder="Ubah komentar Anda..."
                                                                class="min-h-28"
                                                            />
                                                        </div>
                                                        <div
                                                            class="flex justify-end gap-2"
                                                        >
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                @click="
                                                                    activeEditId =
                                                                        null
                                                                "
                                                                class="cursor-pointer"
                                                            >
                                                                Batal
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                @click="
                                                                    submitEdit(
                                                                        reply.id,
                                                                    )
                                                                "
                                                                :disabled="
                                                                    editForm.processing
                                                                "
                                                                class="cursor-pointer font-semibold"
                                                            >
                                                                Simpan
                                                            </Button>
                                                        </div>
                                                    </div>
                                                    <PreviewRenderer
                                                        v-else
                                                        :content="reply.content"
                                                    />
                                                </div>

                                                <!-- Actions -->
                                                <div
                                                    v-if="
                                                        !reply.deleted_at &&
                                                        activeEditId !==
                                                            reply.id
                                                    "
                                                    class="mt-2 flex items-center gap-3 border-t pt-2"
                                                >
                                                    <Button
                                                        v-if="canEdit(reply)"
                                                        variant="ghost"
                                                        size="sm"
                                                        @click="
                                                            handleEditToggle(
                                                                reply,
                                                            )
                                                        "
                                                        class="h-7 cursor-pointer gap-1 px-2 text-[11px] font-semibold text-muted-foreground hover:text-amber-600"
                                                    >
                                                        <Edit2 class="size-3" />
                                                        <span>Edit</span>
                                                    </Button>

                                                    <Button
                                                        v-if="canDelete(reply)"
                                                        variant="ghost"
                                                        size="sm"
                                                        @click="
                                                            deleteComment(
                                                                reply.id,
                                                            )
                                                        "
                                                        class="ml-auto h-7 cursor-pointer gap-1 px-2 text-[11px] font-semibold text-destructive hover:bg-destructive/10"
                                                    >
                                                        <Trash2
                                                            class="size-3"
                                                        />
                                                        <span>Hapus</span>
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
