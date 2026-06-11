<script setup lang="ts">
import UContextMenu from '@nuxt/ui/components/ContextMenu.vue';
</script>

<template>
  <UContextMenu v-bind="$attrs">
    <!-- Forward all slots to the nested UContextMenu component -->
    <template v-for="(_, name) in $slots" #[name]="slotData">
      <slot :name="name" v-bind="slotData || {}" />
    </template>
  </UContextMenu>
</template>
