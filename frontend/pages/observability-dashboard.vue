<script setup lang="ts">
import { onMounted } from 'vue';
import { ref } from 'vue';
import AppHeader from '../components/AppHeader.vue';
import { useObservabilityDashboardHandler } from '../modules/observability/handlers/useObservabilityDashboardHandler';
import { ObservabilityService } from '../modules/observability/services/observabilityService';
import { useDemoSession } from '../src/useDemoSession';

const session = useDemoSession();

const httpClient = {
  get: async <T>(url: string): Promise<T> => {
    const response = await fetch(`${import.meta.env.VITE_API_BASE_URL}${url}`, {
      credentials: 'include',
      headers: {
        'X-User-Email': session.userEmail.value,
        'X-User-Name': session.userName.value,
      },
    });
    return response.json();
  },
};

const handler = useObservabilityDashboardHandler(new ObservabilityService(httpClient));
const currentTheme = ref<'light' | 'dark'>('light');
const periodMinutes = ref(60);

onMounted(async () => {
  await handler.loadSummary(periodMinutes.value);
});

function toggleTheme() {
  currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
}
async function refreshSummary() {
  await handler.loadSummary(periodMinutes.value);
}
</script>

<template>
  <AppHeader :user-name="session.userName" :current-theme="currentTheme" @toggleTheme="toggleTheme" />
  <section>
    <h1>Painel de Observabilidade</h1>
    <label>
      Janela (minutos)
      <input v-model.number="periodMinutes" type="number" min="5" max="1440" />
    </label>
    <button type="button" @click="refreshSummary">Atualizar resumo</button>
    <p v-if="handler.loading">Carregando métricas...</p>
    <p v-else-if="handler.errorMessage">{{ handler.errorMessage }}</p>
    <div v-else-if="handler.summary">
      <p>Falhas em jobs: {{ handler.summary.failedJobs }}</p>
      <p>Jobs pendentes: {{ handler.summary.pendingJobs }}</p>
      <p>Exceções recentes: {{ handler.summary.recentExceptions }}</p>
      <p>Usuários totais: {{ handler.summary.totalUsers }}</p>
      <p>Usuários em tema escuro: {{ handler.summary.darkThemeUsers }}</p>
      <p>Uptime simulado: {{ handler.summary.simulatedUptimePercent }}%</p>
      <p>Gerado em: {{ handler.summary.generatedAt }}</p>
    </div>
  </section>
</template>
