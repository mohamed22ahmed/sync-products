<?php

namespace App\Console\Commands;

use App\Services\SyncLogService;
use Illuminate\Console\Command;

class ViewSyncLogsCommand extends Command
{
    protected $signature = 'logs:sync {--recent=7 : Show logs from last N days} {--stats : Show sync statistics}';

    protected $description = 'View sync logs and statistics';

    public function handle(SyncLogService $syncLogService): int
    {
        if ($this->option('stats')) {
            return $this->showStats($syncLogService);
        }

        return $this->showLogs($syncLogService);
    }

    protected function showLogs(SyncLogService $syncLogService): int
    {
        $days = (int) $this->option('recent');
        $logs = $syncLogService->getRecentSyncs($days);

        if ($logs->isEmpty()) {
            $this->info("No sync logs found for the last {$days} days.");
            return Command::SUCCESS;
        }

        $this->info("Sync Logs (Last {$days} days):");

        $this->table(
            ['ID', 'Type', 'Status', 'Products', 'Created', 'Updated', 'Failed', 'Duration', 'Started'],
            $logs->map(function ($log) {
                $products = $log->total_products_fetched;
                $created = $log->products_created;
                $updated = $log->products_updated;
                $failed = $log->products_failed;
                
                return [
                    $log->id,
                    $log->sync_type,
                    $log->status,
                    $products,
                    $created,
                    $updated,
                    $failed,
                    $log->duration_formatted,
                    $log->started_at->format('Y-m-d H:i:s')
                ];
            })->toArray()
        );

        return Command::SUCCESS;
    }

    protected function showStats(SyncLogService $syncLogService): int
    {
        $days = (int) $this->option('recent');
        $stats = $syncLogService->getSyncStats($days);

        $this->info("Sync Statistics (Last {$days} days):");

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Syncs', $stats['total_syncs']],
                ['Successful Syncs', $stats['successful_syncs']],
                ['Failed Syncs', $stats['failed_syncs']],
                ['Success Rate', $stats['success_rate'] . '%'],
                ['Total Products', $stats['total_products']],
                ['Products Created', $stats['total_created']],
                ['Products Updated', $stats['total_updated']],
                ['Products Failed', $stats['total_failed']],
                ['Average Duration', $this->formatSeconds($stats['avg_duration_seconds'])]
            ]
        );

        return Command::SUCCESS;
    }

    protected function formatSeconds(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds, 1) . 's';
        }

        $minutes = intval($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}m " . round($remainingSeconds, 1) . 's';
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m " . round($remainingSeconds, 1) . 's';
    }
}
