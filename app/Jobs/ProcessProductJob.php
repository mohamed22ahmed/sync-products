<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Category;
use App\Services\ImageDownloadService;
use App\Services\SyncLogService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessProductJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $productData;

    public function __construct(array $productData)
    {
        $this->productData = $productData;
    }

    public function handle(ImageDownloadService $imageService, SyncLogService $syncLogService): void
    {
        try {
            if ($this->batch() && $this->batch()->cancelled()) {
                return;
            }

            // Log job start for progress tracking
            if ($this->batch()) {
                Log::info("Processing job in batch", [
                    'batch_id' => $this->batch()->id,
                    'job_number' => $this->batch()->processedJobs() + 1,
                    'total_jobs' => $this->batch()->totalJobs,
                    'product_title' => $this->productData['title']
                ]);
            }

            $result = $this->processProduct($imageService);

            // Update sync log with product result
            $this->updateSyncLogStats($syncLogService, $result);

            Log::info("Product processed successfully", [
                'product_id' => $this->productData['id'] ?? 'unknown',
                'title' => $this->productData['title'],
                'result' => $result,
                'batch_progress' => $this->batch() ? $this->batch()->progress() : 'N/A'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process product", [
                'product_id' => $this->productData['id'] ?? 'unknown',
                'title' => $this->productData['title'],
                'error' => $e->getMessage(),
                'batch_progress' => $this->batch() ? $this->batch()->progress() : 'N/A'
            ]);

            // Update sync log with failure
            $this->updateSyncLogStats($syncLogService, 'failed');

            throw $e;
        }
    }

    protected function processProduct(ImageDownloadService $imageService): string
    {
        $category = $this->findOrCreateCategory($this->productData['category']);

        $localImagePath = $this->productData['image'] ?? '';
        if (!empty($this->productData['image'])) {
            try {
                $localImagePath = $imageService->downloadAndStore(
                    $this->productData['image'],
                    $this->productData['title']
                );
            } catch (\Exception $e) {
                Log::error("Error downloading image", [
                    'product_title' => $this->productData['title'],
                    'image_url' => $this->productData['image'],
                    'error' => $e->getMessage()
                ]);
                $localImagePath = $this->productData['image'];
            }
        }

        $productAttributes = [
            'title' => $this->productData['title'],
            'price' => $this->productData['price'],
            'description' => $this->productData['description'],
            'image' => $localImagePath,
            'category_id' => $category->id,
            'rating' => json_encode($this->productData['rating']),
        ];

        $existingProduct = Product::where('title', $this->productData['title'])->first();

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

    protected function updateSyncLogStats(SyncLogService $syncLogService, string $result): void
    {
        try {
            $stats = [];
            
            switch ($result) {
                case 'created':
                    $stats['products_created'] = 1;
                    break;
                case 'updated':
                    $stats['products_updated'] = 1;
                    break;
                case 'failed':
                    $stats['products_failed'] = 1;
                    break;
                default:
                    $stats['products_skipped'] = 1;
                    break;
            }

            $syncLogService->updateStats($stats);
        } catch (\Exception $e) {
            Log::warning("Failed to update sync log stats", [
                'result' => $result,
                'error' => $e->getMessage()
            ]);
        }
    }
}
