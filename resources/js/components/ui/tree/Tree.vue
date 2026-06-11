<script setup lang="ts">
import UTree from '@nuxt/ui/components/Tree.vue';
import UApp from '@nuxt/ui/components/App.vue';
</script>

<template>
  <UApp>
    <UTree v-bind="$attrs">
      <!-- Forward all slots to the nested UTree component -->
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData || {}" />
      </template>
    </UTree>
  </UApp>
</template>
