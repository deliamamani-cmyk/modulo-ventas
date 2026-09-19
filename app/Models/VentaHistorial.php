<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaHistorial extends Model
{
    protected $table = 'venta_historials';

    protected $fillable = [
        'venta_id',
        'cliente_nombre',
        'cliente_documento',
        'cliente_email',
        'cliente_celular',
        'sucursal_nombre',
        'usuario_nombre',
        'fecha_venta',
        'total_venta',
        'estado',
        'tipo_pago',
        'nota',
        'detalles',
    ];

    protected $casts = [
        'detalles' => 'array',
        'total_venta' => 'decimal:2',
        'fecha_venta' => 'datetime',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}