<script setup lang="ts">
import AvatarInitials from './AvatarInitials.vue';

defineProps<{
  userName: string;
  currentTheme: 'light' | 'dark';
}>();

defineEmits<{
  toggleTheme: [];
}>();
</script>

<template>
  <header class="card p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Cactus Financias</h1>
    <div class="flex items-center gap-3">
      <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Tema: {{ currentTheme }}</span>
      <button type="button" @click="$emit('toggleTheme')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition-colors dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
        Alternar tema
      </button>
      <AvatarInitials :name="userName" />
    </div>
  </header>
</template>
