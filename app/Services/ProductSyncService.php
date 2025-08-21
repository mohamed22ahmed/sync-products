<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Jobs\ProcessProductJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use App\Models\SyncLog;

class ProductSyncService
{
    protected $apiUrl = 'https://fakestoreapi.com/products';
    protected $batchSize = 100;
    protected $syncLogService;

    public function __construct(SyncLogService $syncLogService)
    {
        $this->syncLogService = $syncLogService;
    }

    public function syncAllProducts(): array
    {
        try {
            $this->syncLogService->startSync('full_sync', [
                'batch_size' => $this->batchSize,
                'api_url' => $this->apiUrl
            ]);

            $products = $this->fetchProductsFromApi();
            
            $this->syncLogService->updateStats([
                'total_products_fetched' => count($products)
            ]);

            $results = $this->processProductsWithQueuedJobs($products);

            $this->syncLogService->updateStats([
                'total_batches' => $results['total_batches'],
                'batch_id' => $results['batch_id']
            ]);

            $this->syncLogService->completeSync([
                'total_products_fetched' => count($products),
                'total_batches' => $results['total_batches']
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error("Product synchronization failed: " . $e->getMessage());
            
            $this->syncLogService->failSync($e->getMessage(), [
                'total_products_fetched' => 0,
                'total_batches' => 0
            ]);

            throw $e;
        }
    }

    protected function processProductsWithQueuedJobs(array $products): array
    {
        $batches = array_chunk($products, $this->batchSize);
        $totalBatches = count($batches);

        $jobs = collect($products)->map(function ($productData) {
            return new ProcessProductJob($productData);
        })->toArray();

        $batch = Bus::batch($jobs)
            ->name('Product Sync - ' . now()->format('Y-m-d H:i:s'))
            ->onQueue('products')
            ->dispatch();

        return [
            'total_products' => count($products),
            'total_batches' => $totalBatches,
            'batch_id' => $batch->id,
            'status' => 'dispatched',
            'message' => 'Products are being processed in the background'
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

    public function setApiUrl(string $url): self
    {
        $this->apiUrl = $url;
        return $this;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function fetchProductsFromApi(): array
    {
        $response = Http::timeout(30)->get($this->apiUrl);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch products from API: ' . $response->status());
        }

        return $response->json();
    }

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

    protected function getCurrentSyncLog(): ?SyncLog
    {
        return $this->syncLogService->getCurrentLog();
    }
}
