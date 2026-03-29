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
  <header class="app-header">
    <h1 class="app-title">Cactus Financias</h1>
    <div class="app-actions">
      <span class="theme-label">Tema: {{ currentTheme }}</span>
      <button type="button" @click="$emit('toggleTheme')" class="theme-button btn btn-secondary">
        Alternar tema
      </button>
      <AvatarInitials :name="userName" />
    </div>
  </header>
</template>

<style scoped>
.app-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
  background: var(--card-color);
}

.app-title {
  margin: 0;
  font-size: 1.125rem;
}

.app-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.theme-label {
  color: var(--muted-color);
}

.theme-button {
  cursor: pointer;
  width: auto;
}

@media (max-width: 760px) {
  .app-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .app-actions {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
