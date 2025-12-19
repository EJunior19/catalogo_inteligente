<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScraperProductImage extends Model
{
    protected $connection = 'scraper';
    protected $table = 'imagenes_productos'; // nombre de la tabla del scraper

    public $timestamps = false;

    // Si la tabla tiene campos distintos, ajustamos después
}
