<script setup lang="ts">
import { CircleQuestionMark } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import type { TooltipContentProps } from 'reka-ui';
import { ref } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

const props = defineProps<{ side?: TooltipContentProps['side'] }>();

const isMobile = useMediaQuery('(max-width: 768px)');
const isOpen = ref(false);

function handleOpenChange(open: boolean) {
    if (!isMobile.value) {
        isOpen.value = open;
    } else if (!open) {
        isOpen.value = false;
    }
}

function toggleTooltip() {
    if (isMobile.value) {
        isOpen.value = !isOpen.value;
    }
}
</script>

<template>
    <TooltipProvider
        :delay-duration="250"
        :content="{ side: isMobile ? 'bottom' : props.side || 'left' }"
    >
        <Tooltip :open="isOpen" @update:open="handleOpenChange">
            <TooltipTrigger as-child>
                <button
                    type="button"
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                    @click="toggleTooltip"
                >
                    <CircleQuestionMark class="size-4" />
                </button>
            </TooltipTrigger>
            <TooltipContent class="max-w-100 p-4">
                <div class="flex flex-col gap-2">
                    <h3 class="text-base font-bold">Tipe Soal:</h3>
                    <ul class="space-y-2 text-xs">
                        <li>
                            <strong class="font-semibold"
                                >PG (Pilihan Ganda):</strong
                            >
                            Bentuk penilaian objektif di mana responden diminta
                            untuk memilih jawaban yang paling tepat dari
                            beberapa pilihan (opsi) yang telah disediakan.
                        </li>
                        <li>
                            <strong class="font-semibold"
                                >PG MCMA (Pilihan Ganda Multiple Choice Multiple
                                Answers):</strong
                            >
                            Jenis soal pilihan ganda di mana peserta harus
                            memilih lebih dari satu jawaban benar dari beberapa
                            pilihan yang disediakan.
                        </li>
                        <li>
                            <strong class="font-semibold"
                                >PG K (Pilihan Ganda Kompleks):</strong
                            >
                            Jenis soal pilihan ganda yang jawabannya disajikan
                            dalam bentuk tabel atau daftar pernyataan, di mana
                            peserta harus menentukan pilihan "Benar/Salah" untuk
                            setiap pernyataan yang diberikan.
                        </li>
                    </ul>
                </div>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
