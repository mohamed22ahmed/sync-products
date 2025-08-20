<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Category;
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

    public function handle(): void
    {
        try {
            if ($this->batch() && $this->batch()->cancelled()) {
                return;
            }

            $result = $this->processProduct();

            Log::info("Product processed successfully", [
                'product_id' => $this->productData['id'] ?? 'unknown',
                'title' => $this->productData['title'],
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process product", [
                'product_id' => $this->productData['id'] ?? 'unknown',
                'title' => $this->productData['title'],
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    protected function processProduct(): string
    {
        $category = $this->findOrCreateCategory($this->productData['category']);

        $productAttributes = [
            'title' => $this->productData['title'],
            'price' => $this->productData['price'],
            'description' => $this->productData['description'],
            'image' => $this->productData['image'],
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
}
