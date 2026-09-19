<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'nro_venta', 'sucursal_id', 'cliente_id', 'usuario_id',
        'fecha_venta', 'total_venta', 'tipo_pago', 'monto_recibido',
        'cambio', 'estado', 'nota',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'total_venta' => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
    ];

    public function sucursal() { return $this->belongsTo(Sucursal::class); }
    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function usuario() { return $this->belongsTo(User::class, 'usuario_id'); }
    public function detalles() { return $this->hasMany(VentaDetalle::class); }
    public function historial() { return $this->hasOne(VentaHistorial::class); }
}