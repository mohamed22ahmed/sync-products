<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Services\ProductSyncService;
use App\Jobs\ProcessProductJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Bus;

class ProductSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_sync_products_from_api()
    {
        // Mock the HTTP response
        Http::fake([
            'https://fakestoreapi.com/products' => Http::response([
                [
                    'id' => 1,
                    'title' => 'Test Product 1',
                    'price' => 99.99,
                    'description' => 'Test description 1',
                    'image' => 'https://example.com/image1.jpg',
                    'category' => 'Test Category 1',
                    'rating' => ['rate' => 4.5, 'count' => 100]
                ],
                [
                    'id' => 2,
                    'title' => 'Test Product 2',
                    'price' => 149.99,
                    'description' => 'Test description 2',
                    'image' => 'https://example.com/image2.jpg',
                    'category' => 'Test Category 2',
                    'rating' => ['rate' => 4.0, 'count' => 50]
                ]
            ], 200)
        ]);

        $syncService = new ProductSyncService();
        $results = $syncService->syncAllProducts();

        // Assert results
        $this->assertEquals(2, $results['total_products']);
        $this->assertEquals(1, $results['total_batches']);
        $this->assertArrayHasKey('batch_id', $results);
        $this->assertEquals('dispatched', $results['status']);
    }

    public function test_can_process_products_synchronously()
    {
        // Mock the HTTP response
        Http::fake([
            'https://fakestoreapi.com/products' => Http::response([
                [
                    'id' => 1,
                    'title' => 'Test Product 1',
                    'price' => 99.99,
                    'description' => 'Test description 1',
                    'image' => 'https://example.com/image1.jpg',
                    'category' => 'Test Category 1',
                    'rating' => ['rate' => 4.5, 'count' => 100]
                ]
            ], 200)
        ]);

        $syncService = new ProductSyncService();
        $products = $syncService->fetchProductsFromApi();
        $results = $syncService->processProductsInBatches($products);

        // Assert results
        $this->assertEquals(1, $results['total_products']);
        $this->assertEquals(1, $results['total_batches']);
        $this->assertEquals(1, $results['created']);
        $this->assertEquals(0, $results['updated']);
        $this->assertEquals(0, $results['errors']);

        // Assert products were created
        $this->assertEquals(1, Product::count());
        $this->assertEquals(1, Category::count());
    }

    public function test_can_update_existing_products()
    {
        // Create existing product
        $category = Category::create(['name' => 'Test Category']);
        $existingProduct = Product::create([
            'title' => 'Test Product 1',
            'price' => 99.99,
            'description' => 'Old description',
            'image' => 'https://example.com/old-image.jpg',
            'category_id' => $category->id,
            'rating' => json_encode(['rate' => 4.0, 'count' => 50])
        ]);

        // Mock API response with updated data
        Http::fake([
            'https://fakestoreapi.com/products' => Http::response([
                [
                    'id' => 1,
                    'title' => 'Test Product 1',
                    'price' => 129.99,
                    'description' => 'Updated description',
                    'image' => 'https://example.com/new-image.jpg',
                    'category' => 'Test Category',
                    'rating' => ['rate' => 4.8, 'count' => 150]
                ]
            ], 200)
        ]);

        $syncService = new ProductSyncService();
        $products = $syncService->fetchProductsFromApi();
        $results = $syncService->processProductsInBatches($products);

        // Assert results
        $this->assertEquals(1, $results['total_products']);
        $this->assertEquals(0, $results['created']);
        $this->assertEquals(1, $results['updated']);

        // Assert product was updated
        $existingProduct->refresh();
        $this->assertEquals(129.99, $existingProduct->price);
        $this->assertEquals('Updated description', $existingProduct->description);
    }

    public function test_handles_api_errors_gracefully()
    {
        // Mock API error response
        Http::fake([
            'https://fakestoreapi.com/products' => Http::response('Server Error', 500)
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch products from API: 500');

        $syncService = new ProductSyncService();
        $syncService->syncAllProducts();
    }

    public function test_can_set_custom_batch_size()
    {
        $syncService = new ProductSyncService();
        $syncService->setBatchSize(5);

        // Use reflection to access protected property for testing
        $reflection = new \ReflectionClass($syncService);
        $property = $reflection->getProperty('batchSize');
        $property->setAccessible(true);

        $this->assertEquals(5, $property->getValue($syncService));
    }

    public function test_can_set_custom_api_url()
    {
        $customUrl = 'https://custom-api.com/products';
        $syncService = new ProductSyncService();
        $syncService->setApiUrl($customUrl);

        // Use reflection to access protected property for testing
        $reflection = new \ReflectionClass($syncService);
        $property = $reflection->getProperty('apiUrl');
        $property->setAccessible(true);

        $this->assertEquals($customUrl, $property->getValue($syncService));
    }

    public function test_process_product_job_creates_product()
    {
        $productData = [
            'id' => 1,
            'title' => 'Test Product',
            'price' => 99.99,
            'description' => 'Test description',
            'image' => 'https://example.com/image.jpg',
            'category' => 'Test Category',
            'rating' => ['rate' => 4.5, 'count' => 100]
        ];

        $job = new ProcessProductJob($productData);
        $job->handle();

        // Assert product was created
        $this->assertEquals(1, Product::count());
        $this->assertEquals(1, Category::count());

        $product = Product::first();
        $this->assertEquals('Test Product', $product->title);
        $this->assertEquals(99.99, $product->price);
    }

    public function test_process_product_job_updates_existing_product()
    {
        // Create existing product
        $category = Category::create(['name' => 'Test Category']);
        $existingProduct = Product::create([
            'title' => 'Test Product',
            'price' => 99.99,
            'description' => 'Old description',
            'image' => 'https://example.com/old-image.jpg',
            'category_id' => $category->id,
            'rating' => json_encode(['rate' => 4.0, 'count' => 50])
        ]);

        $productData = [
            'id' => 1,
            'title' => 'Test Product',
            'price' => 129.99,
            'description' => 'Updated description',
            'image' => 'https://example.com/new-image.jpg',
            'category' => 'Test Category',
            'rating' => ['rate' => 4.8, 'count' => 150]
        ];

        $job = new ProcessProductJob($productData);
        $job->handle();

        // Assert product was updated
        $existingProduct->refresh();
        $this->assertEquals(129.99, $existingProduct->price);
        $this->assertEquals('Updated description', $existingProduct->description);
    }

    public function test_can_get_batch_status()
    {
        $syncService = new ProductSyncService();

        // Mock a batch
        $batch = Bus::fake();

        // This test would need more setup for actual batch testing
        // For now, we'll test the method exists and handles errors gracefully
        $status = $syncService->getBatchStatus('non-existent-batch-id');
        $this->assertNull($status);
    }
}
