export class ObservabilitySummaryModel {
  constructor(
    readonly failedJobs: number,
    readonly pendingJobs: number,
    readonly recentExceptions: number,
    readonly generatedAt: string,
  ) {}
}
