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
    protected $batchSize = 10;

    /**
     * Fetch and sync all products from the API
     */
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


            return $products;

        } catch (\Exception $e) {
            Log::error('Product sync failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
