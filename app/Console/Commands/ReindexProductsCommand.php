<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class ReindexProductsCommand extends Command
{
    protected $signature = 'products:reindex';

    protected $description = 'Reindexa todos os produtos do banco de dados no Elasticsearch';

    public function handle(ElasticsearchService $elasticsearchService): int
    {
        $this->info('Iniciando reindexação dos produtos...');

        $products = Product::all();
        $total = $products->count();

        if ($total === 0) {
            $this->warn('Nenhum produto encontrado no banco.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $indexed = 0;
        foreach ($products as $product) {
            try {
                $elasticsearchService->indexProduct($product);
                $indexed++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Erro ao indexar produto ID {$product->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Reindexação concluída: {$indexed}/{$total} produtos enviados ao Elasticsearch.");

        return self::SUCCESS;
    }
}
