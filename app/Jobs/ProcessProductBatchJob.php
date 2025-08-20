<?php

namespace App\Jobs;

use App\Jobs\ProcessProductJob;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ProcessProductBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $products;
    protected $batchSize;

    public function __construct(array $products, int $batchSize = 100)
    {
        $this->products = $products;
        $this->batchSize = $batchSize;
    }

    public function handle(): void
    {
        try {
            Log::info("Starting batch processing", [
                'total_products' => count($this->products),
                'batch_size' => $this->batchSize
            ]);

            $chunks = array_chunk($this->products, $this->batchSize);

            foreach ($chunks as $chunkIndex => $chunk) {
                $chunkNumber = $chunkIndex + 1;
                Log::info("Processing chunk {$chunkNumber}/" . count($chunks));

                $jobs = collect($chunk)->map(function ($productData) {
                    return new ProcessProductJob($productData);
                })->toArray();

                $batch = Bus::batch($jobs)
                    ->name("Product Chunk {$chunkNumber}")
                    ->onQueue('products')
                    ->dispatch();

                Log::info("Chunk {$chunkNumber} dispatched", [
                    'batch_id' => $batch->id,
                    'products_count' => count($chunk)
                ]);
            }

            Log::info("Batch processing completed successfully");

        } catch (\Exception $e) {
            Log::error("Batch processing failed", [
                'error' => $e->getMessage(),
                'products_count' => count($this->products)
            ]);
            throw $e;
        }
    }
}
