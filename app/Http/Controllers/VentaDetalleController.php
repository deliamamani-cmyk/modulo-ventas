<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\VentaDetalle;
use Illuminate\Http\Request;

class VentaDetalleController extends Controller
{
    // ============================================================
    // LISTADO DE DETALLES DE VENTA (con relaciones y paginación)
    // ============================================================
    public function index(Request $request)
    {
        $ajuste = Ajuste::query()->first();
        $divisa = $ajuste->divisa ?? 'Bs.';

        $search = $request->input('search');

        $query = VentaDetalle::with(['venta', 'inventario.producto', 'producto'])
            ->orderByDesc('id');

        if (!empty($search)) {
            $query->where(function ($w) use ($search) {
                $w->whereHas('venta', fn ($v) => $v->where('nro_venta', 'like', "%{$search}%"))
                    ->orWhereHas('producto', fn ($p) => $p
                        ->where('nombre_comercial', 'like', "%{$search}%")
                        ->orWhere('nombre_generico', 'like', "%{$search}%"))
                    ->orWhereHas('inventario.producto', fn ($p) => $p
                        ->where('nombre_comercial', 'like', "%{$search}%")
                        ->orWhere('nombre_generico', 'like', "%{$search}%"));
            });
        }

        $detalles = $query->paginate(15)->withQueryString();

        return view('admin.ventas.detalles', compact('ajuste', 'divisa', 'detalles', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Los detalles NO se crean manualmente: se generan al confirmar la venta (carrito).
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Los detalles NO se crean manualmente: se generan al confirmar la venta (carrito).
    }

    // ============================================================
    // DETALLE DE UN ÍTEM VENDIDO
    // ============================================================
    public function show($id)
    {
        $ajuste = Ajuste::query()->first();
        $divisa = $ajuste->divisa ?? 'Bs.';

        $detalle = VentaDetalle::with(['venta', 'inventario.producto', 'producto'])->findOrFail($id);

        return view('admin.ventas.detalle_show', compact('ajuste', 'divisa', 'detalle'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VentaDetalle $ventaDetalle)
    {
        // Los detalles de una venta emitida NO se editan (auditoría).
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VentaDetalle $ventaDetalle)
    {
        // Los detalles de una venta emitida NO se editan (auditoría).
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VentaDetalle $ventaDetalle)
    {
        // Los detalles de una venta emitida NO se eliminan (auditoría).
        // Para "revertir" existe la cancelación de venta
    }
}