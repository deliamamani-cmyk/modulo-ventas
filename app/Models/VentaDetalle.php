<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id', 'inventario_id', 'cantidad', 'precio_venta_unidad', 'subtotal',
    ];

    protected $casts = [
        'precio_venta_unidad' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cantidad' => 'integer',
    ];

    public function venta() { return $this->belongsTo(Venta::class); }
    public function inventario() { return $this->belongsTo(Inventario::class); }
}