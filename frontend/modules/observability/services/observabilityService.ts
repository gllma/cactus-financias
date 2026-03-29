import { observabilityRoutes } from '../routes/observabilityRoutes';
import type { ObservabilitySummaryResponseDTO } from '../dtos/ObservabilitySummaryDTO';
import { ObservabilitySummaryModel } from '../models/ObservabilitySummaryModel';

export interface ObservabilityHttpClient {
  get<T>(url: string): Promise<T>;
}

export class ObservabilityService {
  constructor(private readonly httpClient: ObservabilityHttpClient) {}

  async summary(periodMinutes = 60): Promise<ObservabilitySummaryModel> {
    const response = await this.httpClient.get<ObservabilitySummaryResponseDTO>(
      `${observabilityRoutes.summary}?period_minutes=${periodMinutes}`,
    );

    return new ObservabilitySummaryModel(
      response.data.failed_jobs,
      response.data.pending_jobs,
      response.data.recent_exceptions,
      response.meta.generated_at,
    );
  }
}
