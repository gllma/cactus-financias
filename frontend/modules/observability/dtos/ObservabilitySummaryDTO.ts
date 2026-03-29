export interface ObservabilitySummaryDTO {
  failed_jobs: number;
  pending_jobs: number;
  recent_exceptions: number;
}

export interface ObservabilitySummaryResponseDTO {
  data: ObservabilitySummaryDTO;
  meta: {
    generated_at: string;
  };
}
