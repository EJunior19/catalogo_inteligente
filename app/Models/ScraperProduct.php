<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScraperProduct extends Model
{
    protected $connection = 'scraper';
    protected $table = 'productos'; // nombre real de la tabla del scraper

    public $timestamps = false;

    protected $casts = [
        'extra_json' => 'array',
    ];

    // Relación con imágenes (ajustá el nombre de la FK si hace falta)
    public function images()
    {
        return $this->hasMany(ScraperProductImage::class, 'producto_id');
    }
}
