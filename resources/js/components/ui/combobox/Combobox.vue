<script setup lang="ts">
import { ref, computed } from 'vue';
import { Check, ChevronsUpDown } from '@lucide/vue';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';

interface Option {
  value: string | number | null;
  label: string;
}

const props = defineProps<{
  modelValue: string | number | null;
  options: Option[];
  placeholder?: string;
  emptyText?: string;
  searchPlaceholder?: string;
  class?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const open = ref(false);

const selectedLabel = computed(() => {
  const option = props.options.find(opt => opt.value === props.modelValue);
  return option ? option.label : '';
});
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        :class="cn('w-full justify-between font-normal text-left truncate bg-background border-input', props.class)"
      >
        <span class="truncate">{{ selectedLabel || placeholder || "Pilih opsi..." }}</span>
        <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[300px] p-0" align="start">
      <Command>
        <CommandInput :placeholder="searchPlaceholder || 'Cari...'" />
        <CommandEmpty>{{ emptyText || 'Tidak ada hasil.' }}</CommandEmpty>
        <CommandList>
          <CommandGroup>
            <CommandItem
              v-for="option in options"
              :key="String(option.value)"
              :value="String(option.label)"
              @select="() => {
                emit('update:modelValue', option.value);
                open = false;
              }"
              class="cursor-pointer"
            >
              <Check
                :class="cn(
                  'mr-2 h-4 w-4',
                  modelValue === option.value ? 'opacity-100' : 'opacity-0'
                )"
              />
              {{ option.label }}
            </CommandItem>
          </CommandGroup>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>
