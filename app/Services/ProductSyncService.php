<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

            $results = $this->processProductsInBatches($products);

            Log::info('Product sync completed', $results);

            return $results;

        } catch (\Exception $e) {
            Log::error('Product sync failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processProductsInBatches(array $products): array
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

        DB::beginTransaction();

        try {
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

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
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
}
