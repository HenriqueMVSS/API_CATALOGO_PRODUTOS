<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/search/products",
     *     summary="Buscar produtos com ElasticSearch",
     *     tags={"Search"},
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Busca textual em name e description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         @OA\Schema(type="string", enum={"price", "created_at"}, default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resultados da busca",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="page", type="integer"),
     *             @OA\Property(property="per_page", type="integer")
     *         )
     *     )
     * )
     */
    public function products(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'q',
                'category',
                'min_price',
                'max_price',
                'status',
                'sort',
                'order',
                'page',
                'per_page',
            ]);

            $result = $this->productService->search($params);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error searching products', [
                'error' => $e->getMessage(),
                'params' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Erro ao realizar busca.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
