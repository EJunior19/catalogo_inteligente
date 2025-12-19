<?php

namespace App\Http\Controllers;

use App\Models\CatalogProduct;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Listado de productos del catálogo inteligente.
     */
    public function index()
    {
        $products = CatalogProduct::where('activo', true)
            ->orderBy('id', 'desc')
            ->paginate(24);

        return view('products.index', compact('products'));
    }

    /**
     * Detalle de un producto (por slug o id).
     */
    public function show($idOrSlug)
    {
        $product = CatalogProduct::where('activo', true)
            ->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)
                  ->orWhere('slug', $idOrSlug)
                  ->orWhere('sku', $idOrSlug);
            })
            ->with(['presentation', 'images'])
            ->firstOrFail();

        return view('products.show', compact('product'));
    }
}
