<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogPresentation extends Model
{
    protected $fillable = [
        'catalog_product_id',
        'nombre_premium',
        'titulo_marketing',
        'resumen_corto',
        'bullets_sensoriales',
        'texto_cuotas',
        'notas_aroma',
        'genero',
        'historia',
    ];

    protected $casts = [
        'bullets_sensoriales' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(CatalogProduct::class, 'catalog_product_id');
    }
}
