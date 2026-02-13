<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="API Catálogo de Produtos",
 *     version="1.0.0",
 *     description="API REST para gerenciamento de catálogo de produtos com busca ElasticSearch, cache Redis e integração AWS S3"
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor da API"
 * )
 *
 * @OA\Tag(
 *     name="Products",
 *     description="Endpoints para gerenciamento de produtos"
 * )
 *
 * @OA\Tag(
 *     name="Search",
 *     description="Endpoints de busca com ElasticSearch"
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="sku", type="string", example="PROD-001"),
 *     @OA\Property(property="name", type="string", example="Produto Exemplo"),
 *     @OA\Property(property="description", type="string", example="Descrição do produto", nullable=true),
 *     @OA\Property(property="price", type="number", format="float", example=99.99),
 *     @OA\Property(property="category", type="string", example="Eletrônicos", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active"),
 *     @OA\Property(property="image_url", type="string", example="https://example.com/image.jpg", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
abstract class Controller
{
    //
}
