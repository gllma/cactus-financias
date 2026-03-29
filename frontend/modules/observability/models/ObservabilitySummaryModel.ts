export class ObservabilitySummaryModel {
  constructor(
    readonly failedJobs: number,
    readonly pendingJobs: number,
    readonly recentExceptions: number,
    readonly totalUsers: number,
    readonly darkThemeUsers: number,
    readonly simulatedUptimePercent: number,
    readonly generatedAt: string,
  ) {}
}
