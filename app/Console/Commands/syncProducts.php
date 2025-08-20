<?php

namespace App\Console\Commands;

use App\Services\ProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class syncProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync
                            {--batch-size=100 : Number of products to process in each batch}
                            {--api-url= : Custom API URL to fetch products from}
                            {--queue : Use queued jobs for processing}
                            {--sync : Use synchronous processing instead of queues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and sync products from the public API using queued jobs and batching';

    /**
     * Execute the console command.
     */
    public function handle(ProductSyncService $syncService): int
    {
        $this->info('Starting product synchronization...');

        try {
            // Configure the service with command options
            if ($this->option('batch-size')) {
                $syncService->setBatchSize((int) $this->option('batch-size'));
            }

            if ($this->option('api-url')) {
                $syncService->setApiUrl($this->option('api-url'));
            }

            $this->info("Batch size: " . $this->option('batch-size', 100));
            $this->info("API URL: " . ($this->option('api-url') ?: 'https://fakestoreapi.com/products'));

            if ($this->option('queue') || !$this->option('sync')) {
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
            } else {
                $this->info('Using synchronous processing...');
                $products = $syncService->fetchProductsFromApi();
                $results = $syncService->processProductsInBatches($products);

                $this->displayResults($results);
            }

            $this->info('Product synchronization completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Product synchronization failed: ' . $e->getMessage());
            Log::error('Sync command failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Display the sync results in a formatted table
     */
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
