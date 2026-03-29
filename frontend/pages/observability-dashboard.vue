<script setup lang="ts">
import { onMounted } from 'vue';
import { ref } from 'vue';
import AppHeader from '../components/AppHeader.vue';
import { useObservabilityDashboardHandler } from '../modules/observability/handlers/useObservabilityDashboardHandler';
import { ObservabilityService } from '../modules/observability/services/observabilityService';

const httpClient = {
  get: async <T>(url: string): Promise<T> => {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}${url}`, { credentials: 'include' });
    return response.json();
  },
};

const handler = useObservabilityDashboardHandler(new ObservabilityService(httpClient));
const currentTheme = ref<'light' | 'dark'>('light');

onMounted(async () => {
  await handler.loadSummary(60);
});

function toggleTheme() {
  currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
}
</script>

<template>
  <AppHeader user-name="Maria Silva" :current-theme="currentTheme" @toggleTheme="toggleTheme" />
  <section>
    <h1>Painel de Observabilidade</h1>
    <p v-if="handler.loading">Carregando métricas...</p>
    <p v-else-if="handler.errorMessage">{{ handler.errorMessage }}</p>
    <div v-else-if="handler.summary">
      <p>Falhas em jobs: {{ handler.summary.failedJobs }}</p>
      <p>Jobs pendentes: {{ handler.summary.pendingJobs }}</p>
      <p>Exceções recentes: {{ handler.summary.recentExceptions }}</p>
      <p>Gerado em: {{ handler.summary.generatedAt }}</p>
    </div>
  </section>
</template>
