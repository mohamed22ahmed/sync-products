<?php

namespace App\Console\Commands;

use App\Services\ProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class syncProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync {--batch-size=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and sync products from fakestoreapi.com';

    /**
     * Execute the console command.
     */
    public function handle(ProductSyncService $syncService): int
    {
        $this->info('Starting product synchronization...');

        try {
            $this->info("Batch size: " . $this->option('batch-size', 100));

            $this->info('Fetching products from API...');
            $results = $syncService->syncAllProducts();

            $this->displayResults($results);

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
                ['Total Products', count($results)],
            ]
        );

        $this->newLine();
    }
}
