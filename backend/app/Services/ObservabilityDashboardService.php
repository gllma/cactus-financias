<?php

namespace App\Services;

use App\Repositories\ObservabilityMetricsRepository;

class ObservabilityDashboardService
{
    public function __construct(private readonly ObservabilityMetricsRepository $observabilityMetricsRepository)
    {
    }

    /**
     * @return array<string, int>
     */
    public function summary(int $periodMinutes = 60): array
    {
        return [
            'failed_jobs' => $this->observabilityMetricsRepository->countFailedJobs(),
            'pending_jobs' => $this->observabilityMetricsRepository->countPendingJobs(),
            'recent_exceptions' => $this->observabilityMetricsRepository->countRecentExceptions($periodMinutes),
        ];
    }
}
