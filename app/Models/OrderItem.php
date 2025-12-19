<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'catalog_product_id',
        'scraper_id',
        'sku',
        'cantidad',
        'precio_unitario',
        'precio_total',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(CatalogProduct::class, 'catalog_product_id');
    }
}
