<?php

use App\DTOs\ProductDTO;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ElasticsearchService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

uses(RefreshDatabase::class);

test('valida nome com menos de 3 caracteres', function () {
    $service = app(ProductService::class);
    $dto = new ProductDTO(
        sku: 'TEST-001',
        name: 'AB',
        price: 99.99
    );

    expect(fn() => $service->create($dto))
        ->toThrow(InvalidArgumentException::class, 'O nome deve ter no mínimo 3 caracteres');
});

test('valida preço zero ou negativo', function () {
    $service = app(ProductService::class);
    $dto = new ProductDTO(
        sku: 'TEST-002',
        name: 'Produto Teste',
        price: 0
    );

    expect(fn() => $service->create($dto))
        ->toThrow(InvalidArgumentException::class, 'O preço deve ser maior que zero');
});

test('valida SKU único', function () {
    Product::factory()->create(['sku' => 'DUPLICATE-001']);

    $service = app(ProductService::class);
    $dto = new ProductDTO(
        sku: 'DUPLICATE-001',
        name: 'Produto Teste',
        price: 99.99
    );

    expect(fn() => $service->create($dto))
        ->toThrow(InvalidArgumentException::class, 'SKU já existe');
});

test('busca produto por ID', function () {
    $product = Product::factory()->create();
    Cache::flush();

    $service = app(ProductService::class);

    $result = $service->findById($product->id);
    expect($result)->not->toBeNull();
    expect($result->id)->toBe($product->id);
});

test('atualiza produto corretamente', function () {
    $product = Product::factory()->create([
        'name' => 'Nome Antigo',
        'price' => 50.00,
    ]);

    $service = app(ProductService::class);
    $dto = new ProductDTO(
        name: 'Nome Atualizado',
        price: 150.00
    );

    $updated = $service->update($product->id, $dto);
    
    expect($updated->name)->toBe('Nome Atualizado');
    expect($updated->price)->toBe(150.00);
});

test('exclui produto corretamente', function () {
    $product = Product::factory()->create();

    $service = app(ProductService::class);
    $result = $service->delete($product->id);
    
    expect($result)->toBeTrue();
    $this->assertSoftDeleted('products', ['id' => $product->id]);
});
