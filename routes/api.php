<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ErpCatalogOrderController;

use App\Http\Controllers\Api\CatalogOrderController;

Route::post('/catalogo/pedido', [CatalogOrderController::class, 'store']);

