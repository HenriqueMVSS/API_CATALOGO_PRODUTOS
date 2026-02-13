<?php

namespace App\Services;

use App\DTOs\ProductDTO;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductService
{
    private const CACHE_TTL = 90; // 90 segundos
    private const CACHE_PREFIX = 'product:';
    private const SEARCH_CACHE_PREFIX = 'product_search:';

    public function __construct(
        private ProductRepository $repository,
        private ElasticsearchService $elasticsearchService
    ) {}

    public function create(ProductDTO $dto): Product
    {
        // Validações de negócio
        if (strlen($dto->name) < 3) {
            throw new \InvalidArgumentException('O nome deve ter no mínimo 3 caracteres.');
        }

        if ($dto->price <= 0) {
            throw new \InvalidArgumentException('O preço deve ser maior que zero.');
        }

        if ($this->repository->findBySku($dto->sku)) {
            throw new \InvalidArgumentException('SKU já existe.');
        }

        $product = $this->repository->create($dto->toArray());

        // Indexar no Elasticsearch
        try {
            $this->elasticsearchService->indexProduct($product);
        } catch (\Exception $e) {
            Log::warning('Failed to index product after creation', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Product created', ['product_id' => $product->id]);

        return $product;
    }

    public function findById(int $id): ?Product
    {
        $cacheKey = self::CACHE_PREFIX . $id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return $this->repository->findById($id);
        });
    }

    public function update(int $id, ProductDTO $dto): Product
    {
        $product = $this->repository->findById($id);

        if (!$product) {
            throw new \InvalidArgumentException('Produto não encontrado.');
        }

        // Validações
        if (isset($dto->name) && strlen($dto->name) < 3) {
            throw new \InvalidArgumentException('O nome deve ter no mínimo 3 caracteres.');
        }

        if (isset($dto->price) && $dto->price <= 0) {
            throw new \InvalidArgumentException('O preço deve ser maior que zero.');
        }

        if (isset($dto->sku) && $dto->sku !== $product->sku) {
            if ($this->repository->findBySku($dto->sku)) {
                throw new \InvalidArgumentException('SKU já existe.');
            }
        }

        $product = $this->repository->update($product, array_filter($dto->toArray(), fn($value) => $value !== null));

        // Invalidar cache
        Cache::forget(self::CACHE_PREFIX . $id);
        Cache::forget(self::SEARCH_CACHE_PREFIX . '*');

        // Atualizar no Elasticsearch
        try {
            $this->elasticsearchService->updateProduct($product);
        } catch (\Exception $e) {
            Log::warning('Failed to update product in Elasticsearch', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Product updated', ['product_id' => $product->id]);

        return $product;
    }

    public function delete(int $id): bool
    {
        $product = $this->repository->findById($id);

        if (!$product) {
            throw new \InvalidArgumentException('Produto não encontrado.');
        }

        $result = $this->repository->delete($product);

        // Invalidar cache
        Cache::forget(self::CACHE_PREFIX . $id);
        Cache::forget(self::SEARCH_CACHE_PREFIX . '*');

        // Remover do Elasticsearch
        try {
            $this->elasticsearchService->deleteProduct($id);
        } catch (\Exception $e) {
            Log::warning('Failed to delete product from Elasticsearch', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Product deleted', ['product_id' => $id]);

        return $result;
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters);
    }

    public function search(array $params): array
    {
        // Não cachear páginas muito altas
        $page = $params['page'] ?? 1;
        if ($page > 50) {
            return $this->elasticsearchService->search($params);
        }

        // Criar chave de cache baseada nos parâmetros
        $cacheKey = self::SEARCH_CACHE_PREFIX . md5(json_encode($params));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($params) {
            return $this->elasticsearchService->search($params);
        });
    }

    public function uploadImage(int $id, $file): Product
    {
        $product = $this->repository->findById($id);

        if (!$product) {
            throw new \InvalidArgumentException('Produto não encontrado.');
        }

        $s3Service = app(S3Service::class);
        $imageUrl = $s3Service->uploadFile($file, "products/{$id}");

        $product = $this->repository->update($product, ['image_url' => $imageUrl]);

        // Invalidar cache
        Cache::forget(self::CACHE_PREFIX . $id);

        // Atualizar no Elasticsearch
        try {
            $this->elasticsearchService->updateProduct($product);
        } catch (\Exception $e) {
            Log::warning('Failed to update product image in Elasticsearch', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('Product image uploaded', ['product_id' => $product->id]);

        return $product;
    }
}
