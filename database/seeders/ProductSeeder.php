<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'sku' => 'PROD-001',
                'name' => 'Smartphone Galaxy',
                'description' => 'Smartphone com tela de 6.5 polegadas, 128GB de armazenamento',
                'price' => 1299.99,
                'category' => 'Eletrônicos',
                'status' => 'active',
            ],
            [
                'sku' => 'PROD-002',
                'name' => 'Notebook Pro',
                'description' => 'Notebook com processador Intel i7, 16GB RAM, SSD 512GB',
                'price' => 3499.99,
                'category' => 'Informática',
                'status' => 'active',
            ],
            [
                'sku' => 'PROD-003',
                'name' => 'Mouse Wireless',
                'description' => 'Mouse óptico sem fio com bateria recarregável',
                'price' => 89.90,
                'category' => 'Acessórios',
                'status' => 'active',
            ],
            [
                'sku' => 'PROD-004',
                'name' => 'Teclado Mecânico',
                'description' => 'Teclado mecânico RGB com switches Cherry MX',
                'price' => 459.90,
                'category' => 'Acessórios',
                'status' => 'active',
            ],
            [
                'sku' => 'PROD-005',
                'name' => 'Monitor 4K',
                'description' => 'Monitor LED 27 polegadas com resolução 4K UHD',
                'price' => 1899.99,
                'category' => 'Informática',
                'status' => 'active',
            ],
            [
                'sku' => 'PROD-006',
                'name' => 'Tablet Android',
                'description' => 'Tablet com tela de 10 polegadas, 64GB de armazenamento',
                'price' => 799.99,
                'category' => 'Eletrônicos',
                'status' => 'active',
            ],
            [
                'sku' => 'PROD-007',
                'name' => 'Fone Bluetooth',
                'description' => 'Fone de ouvido Bluetooth com cancelamento de ruído',
                'price' => 299.90,
                'category' => 'Acessórios',
                'status' => 'active',
            ],
            [
                'sku' => 'PROD-008',
                'name' => 'Webcam HD',
                'description' => 'Webcam Full HD 1080p com microfone integrado',
                'price' => 199.90,
                'category' => 'Acessórios',
                'status' => 'active',
            ],
            [
                'sku' => 'PROD-009',
                'name' => 'SSD 1TB',
                'description' => 'SSD SATA III de 1TB para upgrade de computador',
                'price' => 599.90,
                'category' => 'Informática',
                'status' => 'active',
            ],
            [
                'sku' => 'PROD-010',
                'name' => 'Smartwatch',
                'description' => 'Relógio inteligente com monitoramento de saúde',
                'price' => 899.99,
                'category' => 'Eletrônicos',
                'status' => 'active',
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
