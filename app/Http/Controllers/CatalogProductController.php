<?php

namespace App\Http\Controllers;

use App\Models\CatalogProduct;
use Illuminate\Http\Request;

class CatalogProductController extends Controller
{
    public function index()
    {
        $products = CatalogProduct::query()
            ->with(['images', 'presentation'])
            ->where('activo', true)
            ->orderByDesc('id')
            ->paginate(12);

        return view('productos.index', compact('products'));
    }

    public function show(string $slug)
    {
        $product = CatalogProduct::query()
            ->with(['images', 'presentation'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('productos.show', compact('product'));
    }
}
