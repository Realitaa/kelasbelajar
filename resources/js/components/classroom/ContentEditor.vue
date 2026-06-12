<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue';
import { show, updateContent } from '@/actions/App/Http/Controllers/ClassroomLearningContentController';
import PreviewRenderer from '@/components/PreviewRenderer.vue';
import RichEditor from '@/components/RichEditor.vue';
import { Button } from '@/components/ui/button';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

const props = defineProps<{
    learningContent: any;
    classroomSlug: string;
}>();

const isLoading = ref(false);
const isSaving = ref(false);
const originalContent = ref<any>({});
const content = ref<any>({});

const http = useHttp();

const isDirty = computed(() => {
    return JSON.stringify(originalContent.value) !== JSON.stringify(content.value);
});

defineExpose({ isDirty });

const activeTab = ref('preview');

async function fetchContent() {
    if (!props.learningContent?.id) {
        return;
    }
    
    isLoading.value = true;

    try {
        const response = await http.get(show([props.classroomSlug, props.learningContent.id]).url) as any;
        const data = response.data;
        originalContent.value = data.content || {};
        content.value = JSON.parse(JSON.stringify(originalContent.value));
    } catch (error) {
        console.error('Failed to fetch learning content', error);
    } finally {
        isLoading.value = false;
    }
}

watch(() => props.learningContent?.id, () => {
    fetchContent();
}, { immediate: true });

function handleSave() {
    isSaving.value = true;
    
    router.put(updateContent([props.classroomSlug, props.learningContent.id]).url, {
        content: content.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            originalContent.value = JSON.parse(JSON.stringify(content.value));
            isSaving.value = false;
        },
        onError: () => {
            isSaving.value = false;
        }
    });
}

function handleCancel() {
    if (isDirty.value) {
        if (confirm('Anda yakin ingin membatalkan perubahan?')) {
            content.value = JSON.parse(JSON.stringify(originalContent.value));
        }
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
            if (!confirm('Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman ini?')) {
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
            <div class="flex items-center justify-between border-b border-border px-4 py-2 bg-muted/20">
                <Tabs v-model="activeTab" class="w-50">
                    <TabsList class="grid w-full grid-cols-2">
                        <TabsTrigger value="preview">
                            <UIcon name="i-lucide-eye" class="size-4" />
                            <span>Pratinjau</span>
                        </TabsTrigger>
                        <TabsTrigger value="editor">
                            <UIcon name="i-lucide-edit-3" class="size-4" />
                            <span>Editor</span>
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="handleCancel" :disabled="!isDirty">
                        Batal
                    </Button>
                    <Button size="sm" @click="handleSave" :loading="isSaving" :disabled="!isDirty">
                        Simpan
                    </Button>
                </div>
            </div>
            
            <div class="flex-1 min-h-0 overflow-hidden relative p-4">
                <div v-show="activeTab === 'editor'" class="h-full">
                    <RichEditor v-model="content" class="h-full" />
                </div>
                
                <div v-show="activeTab === 'preview'" class="h-full overflow-y-auto bg-background border rounded-md p-6">
                    <PreviewRenderer :content="content" />
                </div>
            </div>
        </div>
    </div>
</template>