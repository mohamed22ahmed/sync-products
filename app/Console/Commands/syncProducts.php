<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class syncProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync products from fakestoreapi.com';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing products from fakestoreapi.com');

        $response = Http::get('https://fakestoreapi.com/products');
        $products = $response->json();

    }
}
