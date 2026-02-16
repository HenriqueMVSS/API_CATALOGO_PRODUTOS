<?php

namespace App\Services;

use App\Models\Product;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    private const INDEX_NAME = 'products';

    public function __construct(
        private Client $client
    ) {}

    private function ensureIndexExists(): void
    {
        try {
            $this->createIndex();
        } catch (\Exception $e) {
            Log::warning('Elasticsearch index creation skipped or failed', ['error' => $e->getMessage()]);
        }
    }

    public function createIndex(): void
    {
        $params = [
            'index' => self::INDEX_NAME,
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'sku' => ['type' => 'keyword'],
                        'name' => ['type' => 'text', 'analyzer' => 'standard'],
                        'description' => ['type' => 'text', 'analyzer' => 'standard'],
                        'price' => ['type' => 'float'],
                        'category' => ['type' => 'keyword'],
                        'status' => ['type' => 'keyword'],
                        'created_at' => ['type' => 'date'],
                    ],
                ],
            ],
        ];

        try {
            $this->client->indices()->create($params);
            Log::info('Elasticsearch index created', ['index' => self::INDEX_NAME]);
        } catch (ClientResponseException $e) {
            $body = (string) $e->getResponse()->getBody();
            if ($e->getResponse()->getStatusCode() === 400 && str_contains($body, 'resource_already_exists_exception')) {
                return;
            }
            Log::error('Failed to create Elasticsearch index', [
                'error' => $e->getMessage(),
                'index' => self::INDEX_NAME,
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create Elasticsearch index', [
                'error' => $e->getMessage(),
                'index' => self::INDEX_NAME,
            ]);
            throw $e;
        }
    }

    public function indexProduct(Product $product): void
    {
        $this->ensureIndexExists();
        $params = [
            'index' => self::INDEX_NAME,
            'id' => $product->id,
            'body' => $product->toSearchableArray(),
        ];

        try {
            $this->client->index($params);
            Log::info('Product indexed in Elasticsearch', ['product_id' => $product->id]);
        } catch (\Exception $e) {
            Log::error('Failed to index product', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
            ]);
        }
    }

    public function updateProduct(Product $product): void
    {
        $this->indexProduct($product);
    }

    public function deleteProduct(int $productId): void
    {
        $params = [
            'index' => self::INDEX_NAME,
            'id' => $productId,
        ];

        try {
            $this->client->delete($params);
            Log::info('Product deleted from Elasticsearch', ['product_id' => $productId]);
        } catch (\Exception $e) {
            Log::warning('Failed to delete product from Elasticsearch', [
                'error' => $e->getMessage(),
                'product_id' => $productId,
            ]);
        }
    }

    public function search(array $params): array
    {
        $this->ensureIndexExists();
        $query = [
            'index' => self::INDEX_NAME,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [],
                        'filter' => [],
                    ],
                ],
            ],
        ];

        // Text search
        if (!empty($params['q'])) {
            $query['body']['query']['bool']['must'][] = [
                'multi_match' => [
                    'query' => $params['q'],
                    'fields' => ['name^2', 'description'],
                    'type' => 'best_fields',
                ],
            ];
        }

        // Filters
        if (!empty($params['category'])) {
            $query['body']['query']['bool']['filter'][] = [
                'term' => ['category' => $params['category']],
            ];
        }

        if (!empty($params['status'])) {
            $query['body']['query']['bool']['filter'][] = [
                'term' => ['status' => $params['status']],
            ];
        }

        if (isset($params['min_price']) || isset($params['max_price'])) {
            $range = [];
            if (isset($params['min_price'])) {
                $range['gte'] = $params['min_price'];
            }
            if (isset($params['max_price'])) {
                $range['lte'] = $params['max_price'];
            }
            $query['body']['query']['bool']['filter'][] = [
                'range' => ['price' => $range],
            ];
        }

        // Default match all if no conditions
        if (empty($query['body']['query']['bool']['must']) && empty($query['body']['query']['bool']['filter'])) {
            $query['body']['query'] = ['match_all' => new \stdClass()];
        }

        // Sorting
        $sortField = $params['sort'] ?? 'created_at';
        $sortOrder = $params['order'] ?? 'desc';
        $query['body']['sort'] = [
            [$sortField => ['order' => $sortOrder]],
        ];

        // Pagination
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 15;
        $query['body']['from'] = ($page - 1) * $perPage;
        $query['body']['size'] = $perPage;

        try {
            $response = $this->client->search($query);
            
            return [
                'data' => array_map(function ($hit) {
                    return $hit['_source'];
                }, $response['hits']['hits']),
                'total' => $response['hits']['total']['value'],
                'page' => $page,
                'per_page' => $perPage,
            ];
        } catch (\Exception $e) {
            Log::error('Elasticsearch search failed', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);
            throw $e;
        }
    }
}
