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
    public function handle(): int
    {
        $this->info('Starting product synchronization...');

        try {

            $this->info("Batch size: " . $this->option('batch-size', 100));

            $this->info('Fetching products from API...');


            $this->info('Product synchronization completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Product synchronization failed: ' . $e->getMessage());
            Log::error('Sync command failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
