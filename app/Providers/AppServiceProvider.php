<?php

namespace App\Providers;

use App\Models\Product;
use App\Observers\ProductObserver;
use App\Repositories\ProductRepository;
use App\Services\ElasticsearchService;
use App\Services\ProductService;
use App\Services\S3Service;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Elasticsearch Client
        $this->app->singleton(Client::class, function ($app) {
            return ClientBuilder::create()
                ->setHosts([config('elasticsearch.host')])
                ->build();
        });

        // Services
        $this->app->singleton(ElasticsearchService::class);
        $this->app->singleton(S3Service::class);
        $this->app->singleton(ProductRepository::class);
        
        $this->app->singleton(ProductService::class, function ($app) {
            return new ProductService(
                $app->make(ProductRepository::class),
                $app->make(ElasticsearchService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar Observer
        Product::observe(ProductObserver::class);

        // Criar índice do Elasticsearch na inicialização
        try {
            $elasticsearchService = $this->app->make(ElasticsearchService::class);
            $elasticsearchService->createIndex();
        } catch (\Exception $e) {
            // Log mas não falha a aplicação se Elasticsearch não estiver disponível
            \Log::warning('Failed to create Elasticsearch index on boot', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
