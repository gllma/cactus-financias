<script setup lang="ts">
import { onMounted, ref } from 'vue';
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
const periodMinutes = ref(60);

onMounted(async () => {
  await handler.loadSummary(periodMinutes.value);
});

async function refreshSummary() {
  await handler.loadSummary(periodMinutes.value);
}
</script>

<template>
  <section class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">Painel de Observabilidade</h1>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Acompanhe saúde técnica e indicadores do ambiente em tempo real.</p>
      </div>

      <div class="card p-5 w-full lg:w-auto">
        <div class="grid grid-cols-1 sm:grid-cols-[180px_auto] gap-3 items-end">
          <label class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Janela (minutos)</span>
            <input
              v-model.number="periodMinutes"
              type="number"
              min="5"
              max="1440"
              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500"
            />
          </label>
          <button
            type="button"
            class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900"
            @click="refreshSummary"
          >
            Atualizar resumo
          </button>
        </div>
      </div>
    </div>

    <p v-if="handler.loading" class="text-sm font-medium text-gray-500 dark:text-gray-400">Carregando métricas...</p>
    <p v-else-if="handler.errorMessage" class="text-sm font-medium text-red-600 dark:text-red-400">{{ handler.errorMessage }}</p>

    <div v-else-if="handler.summary" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <article class="card p-5">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Falhas em jobs</h3>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ handler.summary.failedJobs }}</p>
      </article>
      <article class="card p-5">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Jobs pendentes</h3>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ handler.summary.pendingJobs }}</p>
      </article>
      <article class="card p-5">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Exceções recentes</h3>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ handler.summary.recentExceptions }}</p>
      </article>
      <article class="card p-5">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuários totais</h3>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ handler.summary.totalUsers }}</p>
      </article>
      <article class="card p-5">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuários em tema escuro</h3>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ handler.summary.darkThemeUsers }}</p>
      </article>
      <article class="card p-5">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Uptime simulado</h3>
        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ handler.summary.simulatedUptimePercent }}%</p>
      </article>
      <article class="card p-5 sm:col-span-2 lg:col-span-4">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Última atualização</h3>
        <p class="text-base font-semibold text-gray-900 dark:text-white mt-2">Gerado em: {{ handler.summary.generatedAt }}</p>
      </article>
    </div>
  </section>
</template>
