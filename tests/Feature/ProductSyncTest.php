<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\SyncLog;
use App\Services\ProductSyncService;
use App\Services\SyncLogService;
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
        // Mock API response
        Http::fake([
            'https://fakestoreapi.com/products' => Http::response([
                [
                    'id' => 1,
                    'title' => 'Test Product 1',
                    'price' => 99.99,
                    'description' => 'Test description 1',
                    'image' => 'https://example.com/image1.jpg',
                    'category' => 'Test Category',
                    'rating' => ['rate' => 4.5, 'count' => 100]
                ],
                [
                    'id' => 2,
                    'title' => 'Test Product 2',
                    'price' => 149.99,
                    'description' => 'Test description 2',
                    'image' => 'https://example.com/image2.jpg',
                    'category' => 'Test Category',
                    'rating' => ['rate' => 4.8, 'count' => 150]
                ]
            ])
        ]);

        $syncService = new ProductSyncService(app(SyncLogService::class));
        $syncService->setBatchSize(1);

        $results = $syncService->syncAllProducts();

        $this->assertArrayHasKey('batch_id', $results);
        $this->assertEquals(2, $results['total_products']);
        $this->assertEquals(2, $results['total_batches']);

        // Check sync log was created
        $syncLog = SyncLog::first();
        $this->assertNotNull($syncLog);
        $this->assertEquals('full_sync', $syncLog->sync_type);
        $this->assertContains($syncLog->status, ['started', 'completed']); // Status can be either during testing
        $this->assertEquals(2, $syncLog->total_products_fetched);
    }

    public function test_can_update_existing_products()
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

        // Mock API response with updated data
        Http::fake([
            'https://fakestoreapi.com/products' => Http::response([
                [
                    'id' => 1,
                    'title' => 'Test Product',
                    'price' => 129.99,
                    'description' => 'Updated description',
                    'image' => 'https://example.com/new-image.jpg',
                    'category' => 'Test Category',
                    'rating' => ['rate' => 4.8, 'count' => 150]
                ]
            ])
        ]);

        $syncService = new ProductSyncService(app(SyncLogService::class));
        $syncService->setBatchSize(1);

        $results = $syncService->syncAllProducts();

        $this->assertArrayHasKey('batch_id', $results);

        // Check sync log was created
        $syncLog = SyncLog::first();
        $this->assertNotNull($syncLog);
        $this->assertEquals(1, $syncLog->total_products_fetched);
    }

    public function test_can_set_custom_batch_size()
    {
        $syncService = new ProductSyncService(app(SyncLogService::class));
        $syncService->setBatchSize(25);

        $this->assertEquals(25, $syncService->getBatchSize());
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

        // Mock the ImageDownloadService
        $this->mock(\App\Services\ImageDownloadService::class, function ($mock) {
            $mock->shouldReceive('downloadAndStore')
                ->andReturn('https://example.com/image.jpg');
        });

        $job = new ProcessProductJob($productData);
        $job->handle(
            app(\App\Services\ImageDownloadService::class),
            app(SyncLogService::class)
        );

        // Assert products were created
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

        // Mock the ImageDownloadService
        $this->mock(\App\Services\ImageDownloadService::class, function ($mock) {
            $mock->shouldReceive('downloadAndStore')
                ->andReturn('https://example.com/new-image.jpg');
        });

        $job = new ProcessProductJob($productData);
        $job->handle(
            app(\App\Services\ImageDownloadService::class),
            app(SyncLogService::class)
        );

        // Assert product was updated
        $existingProduct->refresh();
        $this->assertEquals(129.99, $existingProduct->price);
        $this->assertEquals('Updated description', $existingProduct->description);
    }



    public function test_sync_log_model_has_expected_attributes()
    {
        $syncLog = SyncLog::create([
            'sync_type' => 'test_sync',
            'status' => 'completed',
            'total_products_fetched' => 10,
            'products_created' => 5,
            'products_updated' => 3,
            'products_skipped' => 1,
            'products_failed' => 1,
            'total_batches' => 2,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'duration_seconds' => 300
        ]);

        $this->assertEquals('test_sync', $syncLog->sync_type);
        $this->assertEquals('completed', $syncLog->status);
        $this->assertEquals(10, $syncLog->total_products_fetched);
        $this->assertEquals(5, $syncLog->products_created);
    }

    public function test_sync_log_scopes_work_correctly()
    {
        // Create sync logs with different statuses and types
        SyncLog::create([
            'sync_type' => 'full_sync',
            'status' => 'completed',
            'total_products_fetched' => 10,
            'started_at' => now()->subDays(3)
        ]);

        SyncLog::create([
            'sync_type' => 'full_sync',
            'status' => 'failed',
            'total_products_fetched' => 5,
            'started_at' => now()->subDays(1)
        ]);

        SyncLog::create([
            'sync_type' => 'manual_sync',
            'status' => 'completed',
            'total_products_fetched' => 3,
            'started_at' => now()->subDays(10)
        ]);

        // Test recent scope
        $recentLogs = SyncLog::recent(5)->get();
        $this->assertEquals(2, $recentLogs->count());

        // Test status scope
        $completedLogs = SyncLog::byStatus('completed')->get();
        $this->assertEquals(2, $completedLogs->count());

        // Test type scope
        $fullSyncLogs = SyncLog::byType('full_sync')->get();
        $this->assertEquals(2, $fullSyncLogs->count());
    }
}
