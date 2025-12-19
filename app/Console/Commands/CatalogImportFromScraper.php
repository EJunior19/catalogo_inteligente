<?php

namespace App\Console\Commands;

use App\Models\ScraperProduct;
use App\Models\ScraperProductImage;
use App\Models\CatalogProduct;
use App\Models\CatalogProductImage;
use App\Models\CatalogPresentation;
use App\Services\PricingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogImportFromScraper extends Command
{
    /**
     * Nombre del comando para Artisan.
     */
    protected $signature = 'catalogo:importar-desde-scraper
                            {--limit=100 : Límite de productos a importar}
                            {--categoria= : Filtrar por categoría del scraper (opcional)}
                            {--force : Forzar actualización aunque el producto exista}
                            {--sync-images : Sincronizar imágenes aunque no uses --force}
                            {--fresh : Limpia TODO el catálogo (productos, imágenes, presentaciones) antes de importar}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Importa productos desde la DB del scraper al catálogo inteligente con precios calculados.';

    public function __construct(
        protected PricingService $pricingService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit      = (int) $this->option('limit');
        $categoria  = $this->option('categoria');
        $force      = (bool) $this->option('force');
        $syncImages = (bool) $this->option('sync-images');
        $fresh      = (bool) $this->option('fresh');

        $this->info("Iniciando importación desde SCRAPER → CATÁLOGO");
        $this->info("Límite: {$limit} | Categoría: " . ($categoria ?: 'todas') . " | Force: " . ($force ? 'sí' : 'no') . " | Sync Images: " . ($syncImages ? 'sí' : 'no') . " | Fresh: " . ($fresh ? 'sí' : 'no'));

        // ==========================
        // MODO FRESH (LIMPIA CATÁLOGO)
        // ==========================
        if ($fresh) {
            $this->warn("⚠️ MODO FRESH ACTIVADO: se limpiará TODO el catálogo antes de importar.");
            if (!$this->confirm('¿Seguro que querés borrar productos, imágenes y presentaciones del CATÁLOGO?', true)) {
                $this->info('Cancelado.');
                return self::SUCCESS;
            }

            DB::transaction(function () {
                // orden recomendado por FK
                CatalogPresentation::query()->delete();
                CatalogProductImage::query()->delete();
                CatalogProduct::query()->delete();
            });

            $this->info("🧹 Catálogo limpiado correctamente.");
        }

        // ==========================
        // QUERY BASE AL SCRAPER
        // ==========================
        $query = ScraperProduct::query();

        if ($categoria) {
            // Si guardás categorías en extra_json->categorias_html (array)
            $query->whereJsonContains('extra_json->categorias_html', $categoria);
        }

        $productos = $query
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($productos->isEmpty()) {
            $this->warn('No se encontraron productos en el scraper con ese filtro.');
            return self::SUCCESS;
        }

        $this->info("Se encontraron {$productos->count()} productos en el scraper.");
        $bar = $this->output->createProgressBar($productos->count());
        $bar->start();

        foreach ($productos as $scraperProduct) {

            try {
                DB::transaction(function () use ($scraperProduct, $force, $syncImages) {

                    // Precio base seguro
                    $precioBase = (int) ($scraperProduct->precio ?? 0);

                    // Si no hay precio, igual podés importar, pero sin cuotas reales
                    $precios = $this->pricingService->calcularPrecios($precioBase);

                    // Categoría legible desde extra_json o campo directo
                    $categoriaTexto = null;
                    $extra = $scraperProduct->extra_json ?? [];

                    if (is_array($extra) && isset($extra['categorias_html'][0]) && !empty($extra['categorias_html'][0])) {
                        $categoriaTexto = $extra['categorias_html'][0];
                    } elseif (isset($scraperProduct->categoria) && !empty($scraperProduct->categoria)) {
                        $categoriaTexto = $scraperProduct->categoria;
                    }

                    // Slug basado en nombre + id para evitar duplicados
                    $nombreBase = (string) ($scraperProduct->nombre ?? ('Producto ' . $scraperProduct->id));
                    $slugBase = Str::slug($nombreBase);
                    $slug = ($slugBase ?: 'producto') . '-' . $scraperProduct->id;

                    // Buscar si ya existe en el catálogo
                    $catalogProduct = CatalogProduct::where('scraper_id', $scraperProduct->id)->first();

                    if (!$catalogProduct) {
                        $catalogProduct = new CatalogProduct();
                        $catalogProduct->scraper_id = $scraperProduct->id;
                    } else {
                        // Si existe y no hay --force, solo actualizar precios y salir (salvo sync-images)
                        if (!$force) {
                            $catalogProduct->precio_base     = $precioBase;
                            $catalogProduct->precio_contado  = $precios['precio_contado'];
                            $catalogProduct->precio_recargo  = $precios['precio_recargo'];
                            $catalogProduct->precio_cuota_3  = $precios['precio_cuota_3'];
                            $catalogProduct->save();

                            if (!$syncImages) {
                                return; // fin transaction
                            }
                            // si sync-images = true, seguimos para refrescar imágenes
                        }
                    }

                    // Guardar / actualizar datos completos
                    $catalogProduct->sku            = $scraperProduct->sku ?: ('SCR-' . $scraperProduct->id);
                    $catalogProduct->slug           = $slug;
                    $catalogProduct->nombre_basico  = $nombreBase;
                    $catalogProduct->categoria      = $categoriaTexto;
                    $catalogProduct->precio_base    = $precioBase;
                    $catalogProduct->precio_contado = $precios['precio_contado'];
                    $catalogProduct->precio_recargo = $precios['precio_recargo'];
                    $catalogProduct->precio_cuota_3 = $precios['precio_cuota_3'];
                    $catalogProduct->activo         = true;
                    $catalogProduct->save();

                    // ==========================
                    // IMÁGENES DESDE EL SCRAPER
                    // ==========================
                    // Si usás force o sync-images, refrescamos las imágenes
                    if ($force || $syncImages) {
                        $catalogProduct->images()->delete();
                    }

                    // Si ya tiene imágenes y no pediste refresh, no tocar
                    if ((!$force && !$syncImages) && $catalogProduct->images()->exists()) {
                        return;
                    }

                    $imagenes = ScraperProductImage::where('producto_id', $scraperProduct->id)->get();

                    if ($imagenes->isNotEmpty()) {
                        $primera = true;

                        foreach ($imagenes as $img) {
                            $url = $img->url_original ?? $img->ruta_local ?? null;
                            if (!$url) continue;

                            CatalogProductImage::create([
                                'catalog_product_id' => $catalogProduct->id,
                                'url'                => $url,
                                'es_principal'       => $primera,
                            ]);

                            $primera = false;
                        }
                    }
                });

            } catch (\Throwable $e) {
                $this->error("\nError al importar producto scraper ID {$scraperProduct->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Importación finalizada ✅');

        return self::SUCCESS;
    }
}
