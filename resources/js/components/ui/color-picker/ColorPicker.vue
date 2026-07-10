<script setup lang="ts">
import UColorPicker from '@nuxt/ui/components/ColorPicker.vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    modelValue: string;
    class?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const colors = [
    '#000000', // Black
    '#1F2937', // Gray 800
    '#6B7280', // Gray 500
    '#FFFFFF', // White
    '#EF4444', // Red 500
    '#F97316', // Orange 500
    '#F59E0B', // Amber 500
    '#10B981', // Emerald 500
    '#3B82F6', // Blue 500
    '#8B5CF6', // Violet 500
    '#EC4899', // Pink 500
    '#6366F1', // Indigo 500
];

const selectedColor = computed({
    get: () => props.modelValue || '',
    set: (val) => emit('update:modelValue', val),
});

function setColor(color: string) {
    selectedColor.value = color;
}
</script>

<template>
    <div :class="cn('flex flex-col gap-3 p-3', props.class)">
        <div class="text-xs font-medium text-muted-foreground uppercase tracking-wider">
            Warna Tersimpan
        </div>
        <div class="grid grid-cols-6 gap-2">
            <button
                v-for="color in colors"
                :key="color"
                type="button"
                :class="
                    cn(
                        'size-6 rounded-full border shadow-sm transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-primary/50',
                        selectedColor === color ? 'ring-2 ring-primary border-primary' : 'border-border'
                    )
                "
                :style="{ backgroundColor: color }"
                :aria-label="color"
                @click="setColor(color)"
            ></button>
        </div>
        
        <div class="border-t border-border pt-3">
            <div class="mb-2 text-xs font-medium text-muted-foreground uppercase tracking-wider">
                Custom (NuxtUI)
            </div>
            <UColorPicker v-model="selectedColor" format="hex" />
        </div>
        
        <div class="mt-1 flex items-center gap-2">
            <div class="size-6 shrink-0 rounded-full border border-border shadow-inner" :style="{ backgroundColor: selectedColor || 'transparent' }"></div>
            <input 
                type="text" 
                v-model="selectedColor" 
                class="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-sm shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                placeholder="Hex (#000000) atau nama warna"
            />
        </div>
    </div>
</template>
