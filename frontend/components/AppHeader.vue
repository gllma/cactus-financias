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
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  margin: 16px;
  background: linear-gradient(150deg, rgba(5, 150, 105, 0.92), rgba(6, 78, 59, 0.95));
  color: #f8fafc;
  box-shadow: 0 16px 28px -22px rgba(4, 120, 87, 0.9);
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
  color: rgba(240, 253, 250, 0.82);
}

.theme-button {
  cursor: pointer;
  width: auto;
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.25);
  color: #ffffff;
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
