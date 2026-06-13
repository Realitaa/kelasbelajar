<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { UEditor } from '@/components/ui/editor';
import { UTree } from '@/components/ui/tree';
import type { TreeItem } from '@/components/ui/tree';
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

const treeItems = ref<TreeItem[]>([
    {
        label: 'app/',
        defaultExpanded: true,
        children: [
            {
                label: 'Http/',
                defaultExpanded: true,
                children: [
                    {
                        label: 'Controllers/',
                        children: [
                            {
                                label: 'DashboardController.php',
                                icon: 'i-lucide-file-text',
                            },
                            {
                                label: 'AuthController.php',
                                icon: 'i-lucide-file-text',
                            },
                        ],
                    },
                    {
                        label: 'Middleware/',
                        children: [
                            {
                                label: 'HandleInertiaRequests.php',
                                icon: 'i-lucide-file-text',
                            },
                        ],
                    },
                ],
            },
            {
                label: 'Models/',
                defaultExpanded: true,
                children: [
                    { label: 'User.php', icon: 'i-lucide-database' },
                    { label: 'Classroom.php', icon: 'i-lucide-database' },
                ],
            },
        ],
    },
    {
        label: 'resources/',
        children: [
            {
                label: 'js/',
                children: [
                    { label: 'app.ts', icon: 'i-lucide-file-code' },
                    {
                        label: 'pages/',
                        children: [
                            {
                                label: 'Dashboard.vue',
                                icon: 'i-lucide-file-code',
                            },
                        ],
                    },
                ],
            },
            {
                label: 'css/',
                children: [{ label: 'app.css', icon: 'i-lucide-file-code' }],
            },
        ],
    },
    { label: 'package.json', icon: 'i-lucide-file' },
    { label: 'composer.json', icon: 'i-lucide-file' },
]);

const selectedFile = ref<string>('Welcome.vue');

const handleSelect = (event: any, item: TreeItem) => {
    if (!item.children) {
        selectedFile.value = item.label ?? '';
    }
};

const editorContent = ref<string>(
    '<h1>Welcome to the Nuxt UI Rich Editor</h1><p>You can edit this content directly. Try highlighting text to format it, or select formatting options above!</p><ul><li>Tiptap integration</li><li>Isolated scope in wrapper</li><li>Responsive and fully styled</li></ul>',
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Dashboard Greeting Header -->
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-bold tracking-tight">Project Dashboard</h1>
            <p class="text-sm text-muted-foreground">
                Manage your codebase files and document notes using Nuxt UI
                components.
            </p>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            <!-- Left Panel: Tree Component File Explorer -->
            <div class="flex flex-col gap-4 lg:col-span-1">
                <div
                    class="rounded-xl border border-border bg-card p-4 text-card-foreground shadow-sm"
                >
                    <div
                        class="mb-3 flex items-center justify-between border-b border-border pb-2"
                    >
                        <span
                            class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            File Explorer
                        </span>
                        <span
                            class="inline-flex h-5 items-center rounded-full bg-primary/10 px-2 text-[10px] font-medium text-primary"
                        >
                            Local
                        </span>
                    </div>
                    <div class="py-2">
                        <UTree
                            :items="treeItems"
                            @select="handleSelect"
                            class="text-sm"
                        />
                    </div>
                </div>

                <!-- Info Card -->
                <div
                    class="rounded-xl border border-border bg-muted/40 p-4 text-card-foreground"
                >
                    <h3
                        class="mb-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Selected File
                    </h3>
                    <p
                        class="rounded border border-border bg-background px-2 py-1.5 font-mono text-sm break-all"
                    >
                        {{ selectedFile }}
                    </p>
                </div>
            </div>

            <!-- Right Panel: Editor Component -->
            <div class="flex flex-col gap-4 lg:col-span-3">
                <div
                    class="flex h-full flex-col overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-border bg-muted/20 px-6 py-4"
                    >
                        <div class="flex flex-col gap-0.5">
                            <h2 class="text-md font-semibold tracking-tight">
                                Code Editor / Document Notes
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                Rich-text component based on TipTap. Changes
                                auto-saved.
                            </p>
                        </div>
                    </div>
                    <div class="flex-1 bg-card p-6">
                        <UEditor
                            v-model="editorContent"
                            content-type="html"
                            placeholder="Start writing note content..."
                            class="min-h-[350px] rounded-lg border border-border p-4 focus-within:ring-2 focus-within:ring-primary/20"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
