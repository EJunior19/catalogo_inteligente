<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'nombre_cliente',
        'email',
        'telefono',
        'documento',
        'direccion',
        'ciudad',
        'total',
        'metodo',
        'estado',
        'enviado_a_erp',
        'erp_pedido_id',
    ];

    protected $casts = [
        'enviado_a_erp' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}

