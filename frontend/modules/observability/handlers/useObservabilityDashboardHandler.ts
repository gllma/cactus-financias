import { ref } from 'vue';
import { ObservabilityService } from '../services/observabilityService';
import { ObservabilitySummaryModel } from '../models/ObservabilitySummaryModel';

export function useObservabilityDashboardHandler(observabilityService: ObservabilityService) {
  const loading = ref(false);
  const errorMessage = ref<string | null>(null);
  const summary = ref<ObservabilitySummaryModel | null>(null);

  async function loadSummary(periodMinutes = 60): Promise<void> {
    loading.value = true;
    errorMessage.value = null;

    try {
      summary.value = await observabilityService.summary(periodMinutes);
    } catch {
      errorMessage.value = 'Não foi possível carregar o resumo de observabilidade.';
      throw errorMessage.value;
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    errorMessage,
    summary,
    loadSummary,
  };
}
