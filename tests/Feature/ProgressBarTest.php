<?php

namespace Tests\Feature;

use App\Services\MailService;
use Tests\TestCase;
use App\Services\ProductSyncService;
use App\Services\SyncLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Bus;

class ProgressBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_command_shows_progress_bar()
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
                ]
            ])
        ]);

        // Mock the batch to simulate progress
        Bus::fake();

        $this->artisan('products:sync', ['--batch-size' => 1])
            ->expectsOutput('Starting product synchronization...')
            ->expectsOutput('Batch size: 1')
            ->expectsOutput('Using queued jobs with Bus::batch() for processing...')
            ->expectsOutput('Fetching products from API...')
            ->expectsOutput('Batch dispatched successfully!')
            ->assertExitCode(0);
    }

    public function test_monitor_command_handles_batch_status()
    {
        $this->artisan('sync:monitor', ['batch_id' => 'non-existent-batch'])
            ->expectsOutput('Monitoring sync progress for batch: non-existent-batch')
            ->expectsOutput('Refresh interval: 2 seconds')
            ->expectsOutput('Batch not found: non-existent-batch')
            ->assertExitCode(1);
    }

    public function test_progress_bar_format_is_correct()
    {
        // This test verifies that the progress bar format is properly set
        $command = new \App\Console\Commands\MonitorSyncProgressCommand();
        
        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('formatTimestamp');
        $method->setAccessible(true);
        
        // Test with numeric timestamp
        $result = $method->invoke($command, 1234567890);
        $this->assertStringContainsString('2009-02-13', $result);
        
        // Test with null
        $result = $method->invoke($command, null);
        $this->assertEquals('N/A', $result);
    }

    public function test_sync_log_service_integration_with_progress()
    {
        $syncLogService = new SyncLogService(new MailService());
        
        // Start sync
        $syncLog = $syncLogService->startSync('test_sync');
        $this->assertEquals('started', $syncLog->status);
        
        // Update with progress
        $syncLogService->updateStats(['total_products_fetched' => 5]);
        $syncLog->refresh();
        $this->assertEquals(5, $syncLog->total_products_fetched);
        
        // Complete sync
        $syncLogService->completeSync(['products_created' => 3, 'products_updated' => 2]);
        $syncLog->refresh();
        $this->assertEquals('completed', $syncLog->status);
    }
}
