<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type'); // 'full_sync', 'batch_sync', 'manual_sync'
            $table->string('status'); // 'started', 'completed', 'failed'
            $table->integer('total_products_fetched')->default(0);
            $table->integer('products_created')->default(0);
            $table->integer('products_updated')->default(0);
            $table->integer('products_skipped')->default(0);
            $table->integer('products_failed')->default(0);
            $table->integer('total_batches')->default(0);
            $table->string('batch_id')->nullable(); // For queued syncs
            $table->text('error_message')->nullable();
            $table->json('sync_options')->nullable(); // Store batch size, API URL, etc.
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
            
            $table->index(['sync_type', 'status']);
            $table->index(['started_at']);
            $table->index(['batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
