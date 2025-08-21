<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageDownloadService
{
    protected $storagePath = 'products';
    protected $allowedExtensions = ['jpg', 'jpeg', 'png'];

    public function downloadAndStore(string $imageUrl, string $productTitle): ?string
    {
        try {
            $filename = $this->generateFilename($productTitle, $imageUrl);
            $fullPath = 'products/' . $filename;

            if (Storage::disk('public')->exists($fullPath)) {
                Log::info("Image already exists: {$filename}");
                return '/storage/' . $fullPath;
            }

            $response = Http::timeout(30)->get($imageUrl);
            
            if (!$response->successful()) {
                Log::warning("Failed to download image: {$imageUrl}", [
                    'status' => $response->status(),
                    'product_title' => $productTitle
                ]);
                return null;
            }

            $contentType = $response->header('Content-Type');
            if (!$this->isValidImageType($contentType)) {
                Log::warning("Invalid image content type: {$contentType}", [
                    'url' => $imageUrl,
                    'product_title' => $productTitle
                ]);
                return null;
            }

            $stored = Storage::disk('public')->put('products/' . $filename, $response->body());
            
            if ($stored) {
                Log::info("Image downloaded and stored successfully", [
                    'filename' => $filename,
                    'product_title' => $productTitle,
                    'size' => strlen($response->body())
                ]);
                
                return '/storage/products/' . $filename;
            } else {
                Log::error("Failed to store image", [
                    'filename' => $filename,
                    'product_title' => $productTitle
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error("Error downloading image", [
                'url' => $imageUrl,
                'product_title' => $productTitle,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected function generateFilename(string $productTitle, string $imageUrl): string
    {
        $cleanTitle = Str::slug($productTitle, '_');
        $extension = $this->getExtensionFromUrl($imageUrl);
        $cleanTitle = substr($cleanTitle, 0, 50);
        
        return "{$cleanTitle}.{$extension}";
    }

    protected function getExtensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        if (empty($extension) || !in_array(strtolower($extension), $this->allowedExtensions)) {
            return 'jpg';
        }
        
        return strtolower($extension);
    }

    protected function isValidImageType(?string $contentType): bool
    {
        if (empty($contentType)) {
            return false;
        }
        
        return str_starts_with($contentType, 'image/');
    }
}
