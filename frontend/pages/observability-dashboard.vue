<script setup lang="ts">
import { onMounted } from 'vue';
import { ref } from 'vue';
import AppHeader from '../components/AppHeader.vue';
import { useObservabilityDashboardHandler } from '../modules/observability/handlers/useObservabilityDashboardHandler';
import { ObservabilityService } from '../modules/observability/services/observabilityService';
import { useDemoSession } from '../src/useDemoSession';

const session = useDemoSession();
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

const httpClient = {
  get: async <T>(url: string): Promise<T> => {
    const response = await fetch(`${apiBaseUrl}${url}`, {
      credentials: 'include',
      headers: {
        'X-User-Email': session.userEmail.value,
        'X-User-Name': session.userName.value,
        Authorization: `Bearer ${session.authToken.value}`,
        ...(session.activeSpaceId.value ? { 'X-Space-Id': String(session.activeSpaceId.value) } : {}),
      },
    });
    const payload = await response.json();
    if (!response.ok) {
      throw new Error(payload?.message ?? 'Falha ao carregar observabilidade.');
    }

    return payload;
  },
};

const handler = useObservabilityDashboardHandler(new ObservabilityService(httpClient));
const themeStorageKey = 'cactus_theme_preference';
const currentTheme = ref<'light' | 'dark'>('light');
const periodMinutes = ref(60);

onMounted(async () => {
  const cachedTheme = localStorage.getItem(themeStorageKey);
  if (cachedTheme === 'light' || cachedTheme === 'dark') {
    currentTheme.value = cachedTheme;
    document.documentElement.setAttribute('data-theme', cachedTheme);
  }

  await handler.loadSummary(periodMinutes.value);
});

function toggleTheme() {
  currentTheme.value = currentTheme.value === 'light' ? 'dark' : 'light';
  localStorage.setItem(themeStorageKey, currentTheme.value);
  document.documentElement.setAttribute('data-theme', currentTheme.value);
}
async function refreshSummary() {
  await handler.loadSummary(periodMinutes.value);
}
</script>

<template>
  <AppHeader :user-name="session.userName" :current-theme="currentTheme" @toggleTheme="toggleTheme" />
  <section>
    <h1>Painel de Observabilidade</h1>
    <div class="toolbar">
      <label>
        Janela (minutos)
        <input v-model.number="periodMinutes" type="number" min="5" max="1440" />
      </label>
      <button type="button" class="btn btn-primary" @click="refreshSummary">Atualizar resumo</button>
    </div>
    <p v-if="handler.loading" class="muted">Carregando métricas...</p>
    <p v-else-if="handler.errorMessage" class="error">{{ handler.errorMessage }}</p>
    <div v-else-if="handler.summary" class="metrics-grid">
      <article class="metric-card"><span>Falhas em jobs</span><strong>{{ handler.summary.failedJobs }}</strong></article>
      <article class="metric-card"><span>Jobs pendentes</span><strong>{{ handler.summary.pendingJobs }}</strong></article>
      <article class="metric-card"><span>Exceções recentes</span><strong>{{ handler.summary.recentExceptions }}</strong></article>
      <article class="metric-card"><span>Usuários totais</span><strong>{{ handler.summary.totalUsers }}</strong></article>
      <article class="metric-card"><span>Usuários em tema escuro</span><strong>{{ handler.summary.darkThemeUsers }}</strong></article>
      <article class="metric-card"><span>Uptime simulado</span><strong>{{ handler.summary.simulatedUptimePercent }}%</strong></article>
      <p class="muted">Gerado em: {{ handler.summary.generatedAt }}</p>
    </div>
  </section>
</template>

<style scoped>
.toolbar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: end;
  margin-bottom: 12px;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
}

.metric-card {
  padding: 14px;
  border: 1px solid color-mix(in oklab, var(--primary-color), var(--border-color) 62%);
  border-radius: 12px;
  background: color-mix(in oklab, var(--card-color), white 5%);
  display: flex;
  flex-direction: column;
  gap: 6px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

.metric-card span {
  color: var(--muted-color);
  font-size: 0.9rem;
}

.metric-card strong {
  font-size: 1.5rem;
}

.muted {
  color: var(--muted-color);
}

.error {
  color: #dc2626;
  font-weight: 600;
}
</style>
