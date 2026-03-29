export interface ObservabilitySummaryDTO {
  failed_jobs: number;
  pending_jobs: number;
  recent_exceptions: number;
  total_users: number;
  dark_theme_users: number;
  simulated_uptime_percent: number;
}

export interface ObservabilitySummaryResponseDTO {
  data: ObservabilitySummaryDTO;
  meta: {
    generated_at: string;
  };
}
