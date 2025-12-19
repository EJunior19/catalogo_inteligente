<?php

namespace App\Console\Commands;

use App\Models\CatalogProduct;
use App\Models\ScraperProduct;
use App\Models\CatalogPresentation;
use App\Services\AiPresentationService;
use Illuminate\Console\Command;

class CatalogGeneratePresentations extends Command
{
    protected $signature = 'catalogo:generar-presentaciones
                            {--limit=50 : Límite de productos a procesar}
                            {--force : Regenerar aunque ya exista presentación}';

    protected $description = 'Genera presentaciones IA para los productos del catálogo.';

    public function __construct(
        protected AiPresentationService $aiService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $query = CatalogProduct::query()->orderBy('id');

        if (!$force) {
            $query->doesntHave('presentation');
        }

        $productos = $query->limit($limit)->get();

        if ($productos->isEmpty()) {
            $this->warn('No se encontraron productos para generar presentaciones.');
            return self::SUCCESS;
        }

        $this->info("Generando presentaciones para {$productos->count()} productos...");
        $bar = $this->output->createProgressBar($productos->count());
        $bar->start();

        foreach ($productos as $catalogProduct) {
            $scraper = ScraperProduct::on('scraper')->find($catalogProduct->scraper_id);

            if (!$scraper) {
                $bar->advance();
                continue;
            }

            $data = $this->aiService->generarParaProducto($scraper, $catalogProduct);

            if (!$data) {
                $bar->advance();
                continue;
            }

            CatalogPresentation::updateOrCreate(
                ['catalog_product_id' => $catalogProduct->id],
                $data
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Presentaciones IA generadas ✅');

        return self::SUCCESS;
    }
}
