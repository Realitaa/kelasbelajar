<script setup lang="ts">
import 'mathlive';
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    modelValue: string;
    class?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const mathFieldRef = ref<any>(null);

function handleInput(e: any) {
    // Check if e.target.value has LaTeX
    if (mathFieldRef.value) {
        emit('update:modelValue', mathFieldRef.value.value);
    }
}

onMounted(() => {
    if (mathFieldRef.value) {
        mathFieldRef.value.value = props.modelValue;
        // Listen to native input event emitted by MathLive
        mathFieldRef.value.addEventListener('input', handleInput);
    }
});

onBeforeUnmount(() => {
    if (mathFieldRef.value) {
        mathFieldRef.value.removeEventListener('input', handleInput);
    }
});

watch(() => props.modelValue, (newVal) => {
    if (mathFieldRef.value && mathFieldRef.value.value !== newVal) {
        // Suppress triggering another input event if not needed, 
        // but mathlive handles this natively
        mathFieldRef.value.setValue(newVal, { suppressChangeNotifications: true });
    }
});
</script>

<template>
    <div :class="cn('w-full overflow-hidden rounded-md border border-input bg-background shadow-sm transition-colors focus-within:ring-1 focus-within:ring-ring focus-within:border-primary', props.class)">
        <component is="math-field"
            ref="mathFieldRef"
            class="w-full p-3 outline-none"
            style="font-size: 1.25rem;"
            math-virtual-keyboard-policy="manual"
        ></component>
    </div>
</template>

<style scoped>
/* Ensure the math-field doesn't outline when focused inside the wrapper */
math-field {
    --contains-highlight-back: transparent;
    --caret-color: var(--color-primary);
}
math-field:focus, math-field:focus-within {
    outline: none !important;
}
</style>
