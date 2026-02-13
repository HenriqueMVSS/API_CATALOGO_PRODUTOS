<?php

namespace App\Http\Controllers;

use App\DTOs\ProductDTO;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/products",
     *     summary="Criar um novo produto",
     *     tags={"Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"sku", "name", "price"},
     *             @OA\Property(property="sku", type="string", example="PROD-001"),
     *             @OA\Property(property="name", type="string", example="Produto Exemplo"),
     *             @OA\Property(property="description", type="string", example="Descrição do produto"),
     *             @OA\Property(property="price", type="number", format="float", example=99.99),
     *             @OA\Property(property="category", type="string", example="Eletrônicos"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Produto criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $dto = ProductDTO::fromArray($request->validated());
            $product = $this->productService->create($dto);

            return response()->json([
                'data' => $product,
                'message' => 'Produto criado com sucesso.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar produto.',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Buscar produto por ID",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Produto encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(response=404, description="Produto não encontrado")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return response()->json([
                'message' => 'Produto não encontrado.',
            ], 404);
        }

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/products/{id}",
     *     summary="Atualizar produto",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="sku", type="string", example="PROD-001"),
     *             @OA\Property(property="name", type="string", example="Produto Atualizado"),
     *             @OA\Property(property="description", type="string", example="Nova descrição"),
     *             @OA\Property(property="price", type="number", format="float", example=149.99),
     *             @OA\Property(property="category", type="string", example="Eletrônicos"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Produto atualizado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(response=404, description="Produto não encontrado"),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $dto = ProductDTO::fromArray($request->validated());
            $product = $this->productService->update($id, $dto);

            return response()->json([
                'data' => $product,
                'message' => 'Produto atualizado com sucesso.',
            ]);
        } catch (\InvalidArgumentException $e) {
            $statusCode = str_contains($e->getMessage(), 'não encontrado') ? 404 : 422;
            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        } catch (\Exception $e) {
            Log::error('Error updating product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao atualizar produto.',
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/products/{id}",
     *     summary="Excluir produto (soft delete)",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Produto excluído com sucesso"
     *     ),
     *     @OA\Response(response=404, description="Produto não encontrado")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productService->delete($id);

            return response()->json([
                'message' => 'Produto excluído com sucesso.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao excluir produto.',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Listar produtos com paginação e filtros",
     *     tags={"Products"},
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
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         @OA\Schema(type="string", enum={"active", "inactive"})
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
     *     @OA\Response(
     *         response=200,
     *         description="Lista de produtos",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $filters = $request->only(['category', 'status', 'min_price', 'max_price']);

        $products = $this->productService->paginate($perPage, $filters);

        return response()->json($products);
    }

    /**
     * @OA\Post(
     *     path="/api/products/{id}/image",
     *     summary="Upload de imagem do produto",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Imagem enviada com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(response=404, description="Produto não encontrado"),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120'], // 5MB max
        ]);

        try {
            $product = $this->productService->uploadImage($id, $request->file('image'));

            return response()->json([
                'data' => $product,
                'message' => 'Imagem enviada com sucesso.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error uploading product image', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao enviar imagem.',
            ], 500);
        }
    }
}
