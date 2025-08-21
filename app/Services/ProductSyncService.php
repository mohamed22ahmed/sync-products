<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Jobs\ProcessProductJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;

class ProductSyncService
{
    protected $apiUrl = 'https://fakestoreapi.com/products';
    protected $batchSize = 100;

    public function syncAllProducts(): array
    {
        try {
            Log::info('Starting product sync from API');

            $response = Http::timeout(30)->get($this->apiUrl);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch products from API: ' . $response->status());
            }

            $products = $response->json();
            $totalProducts = count($products);

            Log::info("Fetched {$totalProducts} products from API");

            $results = $this->processProductsWithQueuedJobs($products);

            Log::info('Product sync completed', $results);

            return $results;

        } catch (\Exception $e) {
            Log::error('Product sync failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processProductsWithQueuedJobs(array $products): array
    {
        $batches = array_chunk($products, $this->batchSize);
        $totalBatches = count($batches);

        Log::info("Processing {$totalBatches} batches of {$this->batchSize} products each using queued jobs");

        $jobs = collect($products)->map(function ($productData) {
            return new ProcessProductJob($productData);
        })->toArray();

        $batch = Bus::batch($jobs)
            ->name('Product Sync - ' . now()->format('Y-m-d H:i:s'))
            ->onQueue('products')
            ->then(function ($batch) {
                Log::info('Product sync batch completed successfully', [
                    'batch_id' => $batch->id,
                    'total_jobs' => $batch->totalJobs,
                    'pending_jobs' => $batch->pendingJobs,
                    'failed_jobs' => $batch->failedJobs
                ]);
            })
            ->catch(function ($batch, $e) {
                Log::error('Product sync batch failed', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage()
                ]);
            })
            ->finally(function ($batch) {
                Log::info('Product sync batch finished', [
                    'batch_id' => $batch->id,
                    'progress_percentage' => $batch->progress()
                ]);
            })
            ->dispatch();

        Log::info("Main batch dispatched", [
            'batch_id' => $batch->id,
            'total_jobs' => $batch->totalJobs
        ]);

        return [
            'total_products' => count($products),
            'total_batches' => $totalBatches,
            'batch_id' => $batch->id,
            'status' => 'dispatched',
            'message' => 'Products are being processed in the background with image downloads'
        ];
    }

    protected function findOrCreateCategory(string $categoryName): Category
    {
        return Category::firstOrCreate(
            ['name' => $categoryName],
            ['name' => $categoryName]
        );
    }

    public function setBatchSize(int $size): self
    {
        $this->batchSize = $size;
        return $this;
    }
}
