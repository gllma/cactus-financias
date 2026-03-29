<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ObservabilityMetricsRepository
{
    public function countFailedJobs(): int
    {
        if (!Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return DB::table('failed_jobs')->count();
    }

    public function countPendingJobs(): int
    {
        if (!Schema::hasTable('jobs')) {
            return 0;
        }

        return DB::table('jobs')->count();
    }

    public function countRecentExceptions(int $minutes = 60): int
    {
        if (!Schema::hasTable('exceptions')) {
            return 0;
        }

        return DB::table('exceptions')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }
}
