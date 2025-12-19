<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogProductImage extends Model
{
    protected $fillable = [
        'catalog_product_id',
        'url',
        'es_principal',
    ];

    public function product()
    {
        return $this->belongsTo(CatalogProduct::class, 'catalog_product_id');
    }
}
