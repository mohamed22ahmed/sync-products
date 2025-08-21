<?php

namespace App\Console\Commands;

use App\Services\ProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class syncProducts extends Command
{
    protected $signature = 'products:sync {--batch-size=100}';

    protected $description = 'Fetch and sync products from the public API using queued jobs and batching';

    public function handle(ProductSyncService $syncService): int
    {
        $this->info('Starting product synchronization...');

        try {
            if ($this->option('batch-size')) {
                $syncService->setBatchSize((int) $this->option('batch-size'));
            }

            $this->info("Batch size: " . $this->option('batch-size', 100));
            $this->info('Using queued jobs with Bus::batch() for processing...');
            $results = $syncService->syncAllProducts();
            
            if (isset($results['batch_id'])) {
                $this->info("Batch dispatched successfully!");
                $this->info("Batch ID: {$results['batch_id']}");
                $this->info("Total Products: {$results['total_products']}");
                $this->info("Total Batches: {$results['total_batches']}");
                $this->info("Status: {$results['message']}");
                
                $this->newLine();
                $this->info('To monitor batch progress, use:');
                $this->info("php artisan queue:work --queue=products");
            }

            $this->info('Product synchronization completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Product synchronization failed: ' . $e->getMessage());
            Log::error('Sync command failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('Synchronization Results:');

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Products', $results['total_products']],
                ['Total Batches', $results['total_batches']],
                ['Products Created', $results['created']],
                ['Products Updated', $results['updated']],
                ['Errors', $results['errors']],
            ]
        );

        $this->newLine();
    }
}
