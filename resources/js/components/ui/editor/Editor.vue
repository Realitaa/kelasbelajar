<script setup lang="ts">
import UEditor from '@nuxt/ui/components/Editor.vue';
import UApp from '@nuxt/ui/components/App.vue';
</script>

<template>
  <UApp>
    <UEditor v-bind="$attrs">
      <!-- Forward all slots to the nested UEditor component -->
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData || {}" />
      </template>
    </UEditor>
  </UApp>
</template>
