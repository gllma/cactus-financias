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
  <div class="avatar" :aria-label="`Avatar ${initials}`">
    {{ initials }}
  </div>
</template>

<style scoped>
.avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 999px;
  background: #047857;
  color: #ffffff;
  font-size: 0.875rem;
  font-weight: 700;
}
</style>
