<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'brand',
        'category',
        'sku',
        'code',
        'price',
        'image_url',
        'presentacion_ia',   // opcional
    ];
}
