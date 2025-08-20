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

        // Create jobs for all products
        $jobs = collect($products)->map(function ($productData) {
            return new ProcessProductJob($productData);
        })->toArray();

        // Dispatch the main batch
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

        // Return initial stats (actual results will be available after batch completion)
        return [
            'total_products' => count($products),
            'total_batches' => $totalBatches,
            'batch_id' => $batch->id,
            'status' => 'dispatched',
            'message' => 'Products are being processed in the background'
        ];
    }

    // Keep the old method for backward compatibility and testing
    public function processProductsInBatches(array $products): array
    {
        $batches = array_chunk($products, $this->batchSize);
        $totalBatches = count($batches);

        $stats = [
            'total_products' => count($products),
            'total_batches' => $totalBatches,
            'created' => 0,
            'updated' => 0,
            'errors' => 0
        ];

        Log::info("Processing {$totalBatches} batches of {$this->batchSize} products each");

        foreach ($batches as $batchIndex => $batch) {
            $batchNumber = $batchIndex + 1;
            Log::info("Processing batch {$batchNumber}/{$totalBatches}");

            try {
                $batchStats = $this->processBatch($batch);

                $stats['created'] += $batchStats['created'];
                $stats['updated'] += $batchStats['updated'];
                $stats['errors'] += $batchStats['errors'];

                Log::info("Batch {$batchNumber} completed", $batchStats);

            } catch (\Exception $e) {
                Log::error("Batch {$batchNumber} failed: " . $e->getMessage());
                $stats['errors'] += count($batch);
            }
        }

        return $stats;
    }

    protected function processBatch(array $products): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];

        foreach ($products as $productData) {
            try {
                $result = $this->processSingleProduct($productData);

                if ($result === 'created') {
                    $stats['created']++;
                } elseif ($result === 'updated') {
                    $stats['updated']++;
                }

            } catch (\Exception $e) {
                Log::error("Failed to process product {$productData['id']}: " . $e->getMessage());
                $stats['errors']++;
            }
        }

        return $stats;
    }

    protected function processSingleProduct(array $productData): string
    {
        $category = $this->findOrCreateCategory($productData['category']);

        $productAttributes = [
            'title' => $productData['title'],
            'price' => $productData['price'],
            'description' => $productData['description'],
            'image' => $productData['image'],
            'category_id' => $category->id,
            'rating' => json_encode($productData['rating']),
        ];

        $existingProduct = Product::where('title', $productData['title'])->first();

        if ($existingProduct) {
            $existingProduct->update($productAttributes);
            return 'updated';
        } else {
            Product::create($productAttributes);
            return 'created';
        }
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

    public function setApiUrl(string $url): self
    {
        $this->apiUrl = $url;
        return $this;
    }

    /**
     * Get batch status by ID
     */
    public function getBatchStatus(string $batchId): ?array
    {
        try {
            $batch = Bus::findBatch($batchId);

            if (!$batch) {
                return null;
            }

            return [
                'id' => $batch->id,
                'name' => $batch->name,
                'total_jobs' => $batch->totalJobs,
                'pending_jobs' => $batch->pendingJobs,
                'failed_jobs' => $batch->failedJobs,
                'progress_percentage' => $batch->progress(),
                'finished' => $batch->finished(),
                'cancelled' => $batch->cancelled(),
                'created_at' => $batch->createdAt,
                'finished_at' => $batch->finishedAt
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get batch status", ['batch_id' => $batchId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch products from API (public method for command usage)
     */
    public function fetchProductsFromApi(): array
    {
        $response = Http::timeout(30)->get($this->apiUrl);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch products from API: ' . $response->status());
        }

        return $response->json();
    }
}
