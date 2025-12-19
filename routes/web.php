<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogProductController;

Route::get('/', function () {
    return redirect()->route('productos.index');
});

Route::get('/productos', [CatalogProductController::class, 'index'])
    ->name('productos.index');

Route::get('/productos/{slug}', [CatalogProductController::class, 'show'])
    ->name('productos.show');


/**
 * Ejecutar scraping desde panel administrativo
 */
Route::post('/productos/scrapear', [ScraperController::class, 'scrapear'])
    ->name('productos.scrapear');

/**
 * Página de prueba interna para ver si todo funciona
 */
Route::get('/test', [TestController::class, 'index'])
    ->name('test.index');

/**
 * Health check (para supervisor, monitoreo o uptime robot)
 */
Route::get('/health', function () {
    return [
        'status' => 'ok',
        'app'    => config('app.name'),
        'time'   => now()->toDateTimeString(),
    ];
});
