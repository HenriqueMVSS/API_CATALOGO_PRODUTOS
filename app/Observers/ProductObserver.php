<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ElasticsearchService;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    public function __construct() {}

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        try {
            $elasticsearchService = app(ElasticsearchService::class);
            $elasticsearchService->indexProduct($product);
        } catch (\Exception $e) {
            Log::warning('Failed to index product after creation', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        try {
            $elasticsearchService = app(ElasticsearchService::class);
            $elasticsearchService->updateProduct($product);
        } catch (\Exception $e) {
            Log::warning('Failed to update product in Elasticsearch', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        try {
            $elasticsearchService = app(ElasticsearchService::class);
            $elasticsearchService->deleteProduct($product->id);
        } catch (\Exception $e) {
            Log::warning('Failed to delete product from Elasticsearch', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
