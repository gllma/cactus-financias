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
  <div
    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-sm font-semibold text-white"
    :aria-label="`Avatar ${initials}`"
  >
    {{ initials }}
  </div>
</template>
