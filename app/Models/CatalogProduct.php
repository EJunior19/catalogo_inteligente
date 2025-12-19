<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogProduct extends Model
{
    // 👈 muy importante: tabla real en la BD
    protected $table = 'catalog_products';

    protected $fillable = [
        'scraper_id',
        'sku',
        'slug',
        'nombre_basico',
        'categoria',
        'precio_base',
        'precio_contado',
        'precio_recargo',
        'precio_cuota_3',
        'activo',
    ];

    public function presentation()
    {
        return $this->hasOne(CatalogPresentation::class, 'catalog_product_id');
    }

    public function images()
    {
        return $this->hasMany(CatalogProductImage::class, 'catalog_product_id');
    }
}
