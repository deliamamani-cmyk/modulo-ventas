<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\VentaHistorial;
use Illuminate\Http\Request;

class VentaHistorialController extends Controller
{
    // ============================================================
    // HISTORIAL DE VENTAS (snapshot) con búsqueda + filtros
    // ============================================================
    public function index(Request $request)
    {
        $ajuste = Ajuste::query()->first();
        $divisa = $ajuste->divisa ?? 'Bs.';

        $search     = $request->input('search');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $query = VentaHistorial::query()->orderByDesc('id');

        // ===== Búsqueda por texto =====
        if (!empty($search)) {
            $query->where(function ($w) use ($search) {
                $w->where('venta_id', 'like', "%{$search}%")
                    ->orWhere('cliente_nombre', 'like', "%{$search}%")
                    ->orWhere('cliente_documento', 'like', "%{$search}%")
                    ->orWhere('sucursal_nombre', 'like', "%{$search}%")
                    ->orWhere('usuario_nombre', 'like', "%{$search}%")
                    ->orWhere('tipo_pago', 'like', "%{$search}%");
            });
        }

        // ===== Filtro por estado =====
        if (!empty($estado)) {
            if ($estado === 'emitida') {
                $query->whereIn('estado', ['emitida', 'completada']);
            } elseif ($estado === 'cancelada') {
                $query->whereIn('estado', ['cancelada', 'anulada']);
            } else {
                $query->where('estado', $estado);
            }
        }

        // ===== Filtro por rango de fechas =====
        if (!empty($fechaDesde)) $query->whereDate('fecha_venta', '>=', $fechaDesde);
        if (!empty($fechaHasta)) $query->whereDate('fecha_venta', '<=', $fechaHasta);

        $historials = $query->paginate(10)->withQueryString();

        return view('admin.ventas.historial', compact(
            'ajuste', 'divisa', 'historials', 'search', 'estado', 'fechaDesde', 'fechaHasta'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // El historial NO se crea manualmente: se genera solo al registrar la venta.
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // El historial NO se crea manualmente: se genera solo al registrar la venta.
    }

    // ============================================================
    // DETALLE DE UN REGISTRO DEL HISTORIAL
    // ============================================================
    public function show($id)
    {
        $ajuste = Ajuste::query()->first();
        $divisa = $ajuste->divisa ?? 'Bs.';

        $historial = VentaHistorial::findOrFail($id);

        return view('admin.ventas.historial_show', compact('ajuste', 'divisa', 'historial'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VentaHistorial $ventaHistorial)
    {
        // 
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VentaHistorial $ventaHistorial)
    {
        // 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VentaHistorial $ventaHistorial)
    {
        // 
    }
}