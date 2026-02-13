<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pode buscar produtos com ElasticSearch', function () {
    // Criar produtos para busca
    Product::factory()->create([
        'name' => 'Smartphone Galaxy',
        'description' => 'Smartphone com tela grande',
        'category' => 'Eletrônicos',
        'price' => 1299.99,
    ]);

    Product::factory()->create([
        'name' => 'Notebook Pro',
        'description' => 'Notebook potente',
        'category' => 'Informática',
        'price' => 3499.99,
    ]);

    // Aguardar um pouco para o ElasticSearch indexar
    sleep(2);

    $response = $this->getJson('/api/search/products?q=Smartphone');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'total',
            'page',
            'per_page',
        ]);

    expect($response->json('total'))->toBeGreaterThan(0);
});

test('pode filtrar busca por categoria', function () {
    Product::factory()->create([
        'name' => 'Produto 1',
        'category' => 'Eletrônicos',
    ]);

    Product::factory()->create([
        'name' => 'Produto 2',
        'category' => 'Informática',
    ]);

    sleep(2);

    $response = $this->getJson('/api/search/products?category=Eletrônicos');

    $response->assertStatus(200);
    expect($response->json('total'))->toBeGreaterThan(0);
});

test('pode filtrar busca por faixa de preço', function () {
    Product::factory()->create(['price' => 100.00]);
    Product::factory()->create(['price' => 500.00]);
    Product::factory()->create(['price' => 1000.00]);

    sleep(2);

    $response = $this->getJson('/api/search/products?min_price=200&max_price=800');

    $response->assertStatus(200);
    expect($response->json('total'))->toBeGreaterThan(0);
});

test('pode ordenar resultados da busca', function () {
    Product::factory()->create(['price' => 100.00]);
    Product::factory()->create(['price' => 500.00]);
    Product::factory()->create(['price' => 200.00]);

    sleep(2);

    $response = $this->getJson('/api/search/products?sort=price&order=asc');

    $response->assertStatus(200);
    $prices = collect($response->json('data'))->pluck('price')->map(fn($p) => (float) $p)->toArray();
    expect($prices)->toBeSorted();
});
