<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pode criar um produto', function () {
    $data = [
        'sku' => 'TEST-001',
        'name' => 'Produto Teste',
        'description' => 'Descrição do produto teste',
        'price' => 99.99,
        'category' => 'Teste',
        'status' => 'active',
    ];

    $response = $this->postJson('/api/products', $data);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'sku',
                'name',
                'description',
                'price',
                'category',
                'status',
                'created_at',
                'updated_at',
            ],
            'message',
        ]);

    $this->assertDatabaseHas('products', [
        'sku' => 'TEST-001',
        'name' => 'Produto Teste',
    ]);
});

test('não pode criar produto com SKU duplicado', function () {
    Product::factory()->create(['sku' => 'DUPLICATE-001']);

    $data = [
        'sku' => 'DUPLICATE-001',
        'name' => 'Produto Duplicado',
        'price' => 99.99,
    ];

    $response = $this->postJson('/api/products', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sku']);
});

test('não pode criar produto com nome menor que 3 caracteres', function () {
    $data = [
        'sku' => 'TEST-002',
        'name' => 'AB',
        'price' => 99.99,
    ];

    $response = $this->postJson('/api/products', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('não pode criar produto com preço zero ou negativo', function () {
    $data = [
        'sku' => 'TEST-003',
        'name' => 'Produto Teste',
        'price' => 0,
    ];

    $response = $this->postJson('/api/products', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['price']);
});

test('pode buscar produto por ID', function () {
    $product = Product::factory()->create();

    $response = $this->getJson("/api/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
            ],
        ]);
});

test('retorna 404 quando produto não existe', function () {
    $response = $this->getJson('/api/products/99999');

    $response->assertStatus(404);
});

test('pode atualizar um produto', function () {
    $product = Product::factory()->create([
        'name' => 'Nome Antigo',
        'price' => 50.00,
    ]);

    $updateData = [
        'name' => 'Nome Atualizado',
        'price' => 150.00,
    ];

    $response = $this->putJson("/api/products/{$product->id}", $updateData);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'name' => 'Nome Atualizado',
                'price' => '150.00',
            ],
        ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Nome Atualizado',
    ]);
});

test('pode excluir um produto (soft delete)', function () {
    $product = Product::factory()->create();

    $response = $this->deleteJson("/api/products/{$product->id}");

    $response->assertStatus(200);

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('pode listar produtos com paginação', function () {
    Product::factory()->count(25)->create();

    $response = $this->getJson('/api/products?per_page=10');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'current_page',
            'per_page',
            'total',
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('pode filtrar produtos por categoria', function () {
    Product::factory()->create(['category' => 'Eletrônicos']);
    Product::factory()->create(['category' => 'Informática']);
    Product::factory()->create(['category' => 'Eletrônicos']);

    $response = $this->getJson('/api/products?category=Eletrônicos');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
});

test('pode filtrar produtos por status', function () {
    Product::factory()->create(['status' => 'active']);
    Product::factory()->create(['status' => 'inactive']);
    Product::factory()->create(['status' => 'active']);

    $response = $this->getJson('/api/products?status=active');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
});

test('pode filtrar produtos por faixa de preço', function () {
    Product::factory()->create(['price' => 50.00]);
    Product::factory()->create(['price' => 100.00]);
    Product::factory()->create(['price' => 200.00]);

    $response = $this->getJson('/api/products?min_price=75&max_price=150');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.price'))->toBe('100.00');
});
