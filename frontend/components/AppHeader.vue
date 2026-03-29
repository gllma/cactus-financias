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
  <header class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
    <h1 class="text-lg font-semibold">Cactus Financias</h1>
    <div class="flex items-center gap-3">
      <span class="text-sm">Tema: {{ currentTheme }}</span>
      <button type="button" @click="$emit('toggleTheme')" class="rounded border px-2 py-1 text-sm">
        Alternar tema
      </button>
      <AvatarInitials :name="userName" />
    </div>
  </header>
</template>
