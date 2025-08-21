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
            
            $this->info('Fetching products from API...');
            $progressBar = $this->output->createProgressBar();
            $progressBar->start();
            
            $results = $syncService->syncAllProducts();
            
            $progressBar->finish();
            $this->newLine(2);
            
            if (isset($results['batch_id'])) {
                $this->info("Batch dispatched successfully!");
                $this->info("Batch ID: {$results['batch_id']}");
                $this->info("Total Products: {$results['total_products']}");
                $this->info("Total Batches: {$results['total_batches']}");
                $this->info("Status: {$results['message']}");
                
                $this->showProgressMonitoring($results['batch_id'], $syncService);
            }

            $this->info('Product synchronization completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Product synchronization failed: ' . $e->getMessage());
            Log::error('Sync command failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    protected function showProgressMonitoring(string $batchId, ProductSyncService $syncService): void
    {
        $this->info('Monitoring sync progress...');
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();
        
        $lastProgress = 0;
        $attempts = 0;
        $maxAttempts = 30;
        
        while ($attempts < $maxAttempts) {
            $batchStatus = $syncService->getBatchStatus($batchId);
            
            if (!$batchStatus) {
                $progressBar->advance(100 - $lastProgress);
                break;
            }
            
            if ($batchStatus['finished']) {
                $progressBar->advance(100 - $lastProgress);
                break;
            }
            
            $currentProgress = $batchStatus['progress_percentage'];
            $progressToAdvance = $currentProgress - $lastProgress;
            
            if ($progressToAdvance > 0) {
                $progressBar->advance($progressToAdvance);
                $lastProgress = $currentProgress;
            }
            
            $attempts++;
            sleep(2);
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $finalStatus = $syncService->getBatchStatus($batchId);
        if ($finalStatus) {
            $this->info("Final Status:");
            $this->info("Total Jobs: {$finalStatus['total_jobs']}");
            $this->info("Pending Jobs: {$finalStatus['pending_jobs']}");
            $this->info("Failed Jobs: {$finalStatus['failed_jobs']}");
            $this->info("Progress: {$finalStatus['progress_percentage']}%");
            
            if ($finalStatus['finished']) {
                $this->info("Status: Completed");
            } elseif ($finalStatus['cancelled']) {
                $this->warn("Status: Cancelled");
            } else {
                $this->info("Status: In Progress");
            }
        }
    }
}
