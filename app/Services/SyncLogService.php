<?php

namespace App\Services;

use App\Models\SyncLog;
use Illuminate\Support\Facades\Log;

class SyncLogService
{
    protected $currentLog = null;

    public function startSync(string $syncType, array $options = []): SyncLog
    {
        $this->currentLog = SyncLog::create([
            'sync_type' => $syncType,
            'status' => 'started',
            'started_at' => now(),
            'sync_options' => $options
        ]);

        Log::info("Sync started", [
            'sync_id' => $this->currentLog->id,
            'sync_type' => $syncType,
            'options' => $options
        ]);

        return $this->currentLog;
    }

    public function updateStats(array $stats): void
    {
        if (!$this->currentLog) {
            return;
        }

        $this->currentLog->update($stats);

        Log::info("Sync stats updated", [
            'sync_id' => $this->currentLog->id,
            'stats' => $stats
        ]);
    }

    public function completeSync(array $finalStats = []): void
    {
        if (!$this->currentLog) {
            return;
        }

        $duration = now()->diffInSeconds($this->currentLog->started_at);

        $this->currentLog->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration_seconds' => $duration,
            ...$finalStats
        ]);

        Log::info("Sync completed successfully", [
            'sync_id' => $this->currentLog->id,
            'duration' => $duration,
            'final_stats' => $finalStats
        ]);
    }

    public function failSync(string $errorMessage, array $currentStats = []): void
    {
        if (!$this->currentLog) {
            return;
        }

        $duration = now()->diffInSeconds($this->currentLog->started_at);

        $this->currentLog->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
            'duration_seconds' => $duration,
            ...$currentStats
        ]);

        Log::error("Sync failed", [
            'sync_id' => $this->currentLog->id,
            'error' => $errorMessage,
            'duration' => $duration,
            'stats' => $currentStats
        ]);
    }

    public function getCurrentLog(): ?SyncLog
    {
        return $this->currentLog;
    }

    public function getRecentSyncs(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return SyncLog::recent($days)->orderBy('started_at', 'desc')->get();
    }

    public function getSyncStats(int $days = 30): array
    {
        $recentLogs = SyncLog::recent($days)->get();

        $totalSyncs = $recentLogs->count();
        $successfulSyncs = $recentLogs->where('status', 'completed')->count();
        $failedSyncs = $recentLogs->where('status', 'failed')->count();

        $totalProducts = $recentLogs->sum('total_products_fetched');
        $totalCreated = $recentLogs->sum('products_created');
        $totalUpdated = $recentLogs->sum('products_updated');
        $totalFailed = $recentLogs->sum('products_failed');

        $avgDuration = $recentLogs->where('duration_seconds', '>', 0)->avg('duration_seconds');

        return [
            'total_syncs' => $totalSyncs,
            'successful_syncs' => $successfulSyncs,
            'failed_syncs' => $failedSyncs,
            'success_rate' => $totalSyncs > 0 ? round(($successfulSyncs / $totalSyncs) * 100, 2) : 0,
            'total_products' => $totalProducts,
            'total_created' => $totalCreated,
            'total_updated' => $totalUpdated,
            'total_failed' => $totalFailed,
            'avg_duration_seconds' => round($avgDuration ?? 0, 2)
        ];
    }
}
