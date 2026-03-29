<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  name: string;
}>();

const initials = computed(() =>
  props.name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((chunk) => chunk.charAt(0).toUpperCase())
    .join(''),
);
</script>

<template>
  <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold" :aria-label="`Avatar ${initials}`">
    {{ initials }}
  </div>
</template>
