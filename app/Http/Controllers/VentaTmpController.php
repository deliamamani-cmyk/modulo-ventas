<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Arqueo;
use App\Models\ArqueoDetalle;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaHistorial;
use App\Models\VentaTmp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class VentaTmpController extends Controller
{
    // ============================================================
    //  HELPERS
    // ============================================================
    private function nombreCliente($c): string
    {
        // Incluye nombres_apellidos (tu tabla real)
        foreach (['nombres_apellidos', 'nombre_completo', 'nombre', 'nombres', 'razon_social', 'name'] as $col) {
            if (!empty($c->{$col})) {
                $extra = ($col === 'nombres' && !empty($c->apellidos)) ? ' ' . $c->apellidos : '';
                return $c->{$col} . $extra;
            }
        }
        return 'Cliente #' . $c->id;
    }

    private function documentoCliente($c): string
    {
        foreach (['ci_nit', 'ci', 'nit', 'documento', 'dni'] as $col) {
            if (!empty($c->{$col})) return (string) $c->{$col};
        }
        return '—';
    }

    private function carritoTmp()
    {
        return VentaTmp::with(['inventario.producto.laboratorio', 'inventario.producto.formaFarmaceutica', 'inventario.producto.presentacion', 'inventario.lote', 'inventario.sucursal'])
            ->where('usuario_id', Auth::id())
            ->get();
    }

    private function itemParaVista($t): array
    {
        $inv = $t->inventario;
        $prod = $inv?->producto;
        $precio = (float) ($inv->precio_venta_unidad ?? 0);

        return [
            'id' => $t->id,
            'inventario_id' => $t->inventario_id,
            'producto_id' => $t->producto_id,
            'codigo' => $prod->codigo_producto ?? '',
            'codigo_barra' => $prod->codigo_barra ?? '',
            'nombre_comercial' => $prod->nombre_comercial ?? '',
            'nombre_generico' => $prod->nombre_generico ?? '',
            'concentracion' => $prod->concentracion ?? '',
            'laboratorio' => $prod->laboratorio->nombre ?? '',
            'forma_present' => trim(($prod->formaFarmaceutica->nombre ?? '') . ' / ' . ($prod->presentacion->nombre ?? '')),
            'cantidad' => (int) $t->cantidad,
            'stock' => (int) ($inv->stock_actual ?? 0),
            'precio' => $precio,
            'subtotal' => ((int) $t->cantidad) * $precio,
        ];
    }

    private function respuestaCarrito($message = null)
    {
        $items = $this->carritoTmp()->map(fn ($t) => $this->itemParaVista($t))->values();

        return [
            'success' => true,
            'message' => $message,
            'items' => $items,
            'total' => $items->sum('subtotal'),
            'cantidad_total' => $items->sum('cantidad'),
        ];
    }

    private function sucursalDeVenta($tmps, $arqueo): int
    {
        foreach ($tmps as $t) {
            if (!empty($t->sucursal_id)) return (int) $t->sucursal_id;
            if (!empty($t->inventario->sucursal_id)) return (int) $t->inventario->sucursal_id;
        }

        if (!empty(Auth::user()->sucursal_id)) return (int) Auth::user()->sucursal_id;
        if ($arqueo && !empty($arqueo->sucursal_id)) return (int) $arqueo->sucursal_id;

        return (int) (Sucursal::query()->value('id') ?? 1);
    }

    // ============================================================
    //  CARRITO
    // ============================================================
    public function create(Request $request)
    {
        $ajuste = Ajuste::first();
        $divisa = $ajuste->divisa ?? 'Bs';

        $sucursalId = $request->integer('sucursal_id') ?: (int) (Sucursal::query()->value('id') ?? 0);

        $sucursales = Sucursal::orderBy('nombre')->get();

        $inventarios = Inventario::with(['producto.laboratorio', 'producto.categoria', 'producto.formaFarmaceutica', 'producto.presentacion', 'lote'])
            ->where('sucursal_id', $sucursalId)
            ->where('stock_actual', '>', 0)
            ->orderBy('id')
            ->get();

        $carritoInicial = $this->respuestaCarrito();

        $hayTurnoAbierto = Arqueo::query()->where('estado', 'abierto')->exists();

        return view('admin.carrito_ventas.create', compact(
            'ajuste', 'divisa', 'sucursales', 'sucursalId', 'inventarios', 'carritoInicial', 'hayTurnoAbierto'
        ));
    }

    public function index()
    {
        return $this->create(request());
    }

    // ============================================================
    //  AGREGAR (Ajax)
    // ============================================================
    public function addItems(Request $request)
    {
        try {
            $data = $request->validate([
                'inventario_id' => 'required|exists:inventarios,id',
                'cantidad' => 'nullable|integer|min:1',
            ]);

            $inv = Inventario::with(['producto', 'lote'])->findOrFail($data['inventario_id']);
            $cantidad = $data['cantidad'] ?? 1;

            if ($inv->lote && $inv->lote->fecha_vencimiento && $inv->lote->fecha_vencimiento->isPast()) {
                return response()->json([
                    'success' => false,
                    'type' => 'vencido',
                    'nombre' => $inv->producto->nombre_comercial ?? 'Producto',
                    'fecha_vencimiento' => $inv->lote->fecha_vencimiento->format('d/m/Y'),
                    'dias_vencido' => abs((int) now()->diffInDays($inv->lote->fecha_vencimiento, false)),
                ], 422);
            }

            $existente = VentaTmp::query()
                ->where('usuario_id', Auth::id())
                ->where('inventario_id', $inv->id)
                ->first();

            $nuevaCantidad = ($existente ? (int) $existente->cantidad : 0) + $cantidad;

            if ($nuevaCantidad > (int) $inv->stock_actual) {
                return response()->json(['success' => false, 'type' => 'stock', 'stock' => (int) $inv->stock_actual] + $this->respuestaCarrito(), 422);
            }

            if ($existente) {
                $existente->cantidad = $nuevaCantidad;
                $existente->save();
            } else {
                $tmp = new VentaTmp();
                $tmp->usuario_id = Auth::id();
                if (Schema::hasColumn('venta_tmps', 'sucursal_id')) $tmp->sucursal_id = $inv->sucursal_id;
                if (Schema::hasColumn('venta_tmps', 'producto_id')) $tmp->producto_id = $inv->producto_id;
                if (Schema::hasColumn('venta_tmps', 'inventario_id')) $tmp->inventario_id = $inv->id;
                $tmp->cantidad = $cantidad;
                if (Schema::hasColumn('venta_tmps', 'precio_venta_unidad')) $tmp->precio_venta_unidad = (float) $inv->precio_venta_unidad;
                if (Schema::hasColumn('venta_tmps', 'estado')) $tmp->estado = 'activo';
                $tmp->save();
            }

            return response()->json($this->respuestaCarrito('Producto agregado al carrito.'));
        } catch (\Throwable $e) {
            Log::error('addItems: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al agregar: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    //  ACTUALIZAR / ELIMINAR / LIMPIAR (Ajax)
    // ============================================================
    public function updateItem(Request $request, $itemId)
    {
        try {
            $tmp = VentaTmp::query()->where('usuario_id', Auth::id())->findOrFail($itemId);
            $data = $request->validate(['cantidad' => 'required|integer|min:0']);

            $stock = (int) ($tmp->inventario->stock_actual ?? 0);

            if ($data['cantidad'] > $stock) {
                return response()->json(['success' => false, 'type' => 'stock', 'stock' => $stock] + $this->respuestaCarrito(), 422);
            }

            $data['cantidad'] === 0 ? $tmp->delete() : $tmp->update(['cantidad' => $data['cantidad']]);

            return response()->json($this->respuestaCarrito());
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    public function removeItem($itemId)
    {
        VentaTmp::query()->where('usuario_id', Auth::id())->where('id', $itemId)->delete();
        return response()->json($this->respuestaCarrito());
    }

    public function clearItems(Request $request)
    {
        VentaTmp::query()->where('usuario_id', Auth::id())->delete();
        return response()->json($this->respuestaCarrito());
    }

    // ============================================================
    // CLIENTE RÁPIDO (Ajax)
    // ============================================================
    public function clienteRapido(Request $request)
    {
        $data = $request->validate([
            'ci_nit' => 'required|string|max:50',
            'nombre' => 'required|string|max:190',
            'telefono' => 'nullable|string|max:30',
            'correo' => 'nullable|email|max:190',
        ]);

        try {
            $cliente = new Cliente();

            // Documento
            foreach (['ci_nit', 'ci', 'nit', 'documento', 'dni'] as $col) {
                if (Schema::hasColumn('clientes', $col)) { $cliente->{$col} = $data['ci_nit']; break; }
            }

            // Nombre: nombres_apellidos primero (tu tabla real)
            if (Schema::hasColumn('clientes', 'nombres_apellidos')) $cliente->nombres_apellidos = $data['nombre'];
            elseif (Schema::hasColumn('clientes', 'nombre_completo')) $cliente->nombre_completo = $data['nombre'];
            elseif (Schema::hasColumn('clientes', 'nombre')) $cliente->nombre = $data['nombre'];
            elseif (Schema::hasColumn('clientes', 'nombres')) $cliente->nombres = $data['nombre'];
            elseif (Schema::hasColumn('clientes', 'razon_social')) $cliente->razon_social = $data['nombre'];

            // Teléfono y correo
            foreach (['telefono', 'celular'] as $col) {
                if (Schema::hasColumn('clientes', $col) && !empty($data['telefono'])) { $cliente->{$col} = $data['telefono']; break; }
            }
            foreach (['correo', 'email'] as $col) {
                if (Schema::hasColumn('clientes', $col) && !empty($data['correo'])) { $cliente->{$col} = $data['correo']; break; }
            }

            $cliente->save();

            return response()->json([
                'success' => true,
                'cliente' => [
                    'id' => $cliente->id,
                    'nombre' => $this->nombreCliente($cliente),
                    'ci_nit' => $this->documentoCliente($cliente),
                ],
                'message' => 'Cliente creado correctamente.',
            ]);
        } catch (\Throwable $e) {
            Log::error('clienteRapido: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al crear cliente: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    //  CHECKOUT
    // ============================================================
    public function checkout()
    {
        $ajuste = Ajuste::first();
        $divisa = $ajuste->divisa ?? 'Bs';

        $tmps = $this->carritoTmp();

        if ($tmps->isEmpty()) {
            return redirect()->route('admin.carrito_ventas.create')
                ->with('error', 'El carrito está vacío. Agrega productos antes de ir al checkout.');
        }

        $items = $tmps->map(fn ($t) => $this->itemParaVista($t))->values();
        $total = $items->sum('subtotal');

        $clientes = Cliente::query()->get()->map(fn ($c) => [
            'id' => $c->id,
            'nombre' => $this->nombreCliente($c),
            'ci_nit' => $this->documentoCliente($c),
        ])->sortBy('nombre')->values();

        $hayTurnoAbierto = Arqueo::query()->where('estado', 'abierto')->exists();

        return view('admin.carrito_ventas.checkout', compact(
            'ajuste', 'divisa', 'items', 'total', 'clientes', 'hayTurnoAbierto'
        ));
    }

    // ============================================================
    //  CONFIRMAR VENTA → OK lleva a VENTAS REALIZADAS
    // ============================================================
    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'tipo_pago' => 'required|string',
            'monto_recibido' => 'nullable|numeric|min:0',
            'nota' => 'nullable|string|max:1000',
        ]);

        $tmps = $this->carritoTmp();

        if ($tmps->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'El carrito está vacío.'], 422);
        }

        $items = $tmps->map(fn ($t) => $this->itemParaVista($t))->values();
        $total = $items->sum('subtotal');

        $recibido = $data['monto_recibido'] !== null ? (float) $data['monto_recibido'] : null;

        if ($recibido !== null && $recibido < $total) {
            return response()->json(['success' => false, 'message' => 'El monto recibido es menor al total de la venta.'], 422);
        }

        $cambio = ($recibido !== null) ? $recibido - $total : null;

        $arqueo = Arqueo::query()->where('estado', 'abierto')->orderByDesc('id')->first();

        if (! $arqueo) {
            return response()->json(['success' => false, 'message' => 'No hay turno (arqueo) abierto. Abra un turno en Cajas primero.'], 422);
        }

        $sucursalId = $this->sucursalDeVenta($tmps, $arqueo);

        DB::beginTransaction();

        try {
            foreach ($tmps as $t) {
                $inv = Inventario::query()->where('id', $t->inventario_id)->lockForUpdate()->first();

                if (! $inv || $inv->stock_actual < $t->cantidad) {
                    throw new \Exception('Stock insuficiente para: ' . ($t->inventario->producto->nombre_comercial ?? 'producto'));
                }

                $inv->decrement('stock_actual', $t->cantidad);
            }

            $nroVenta = 'V-' . str_pad((string) (Venta::query()->count() + 1), 5, '0', STR_PAD_LEFT);

            $venta = Venta::query()->create([
                'nro_venta' => $nroVenta,
                'sucursal_id' => $sucursalId,
                'cliente_id' => $data['cliente_id'] ?? null,
                'usuario_id' => Auth::id(),
                'fecha_venta' => now(),
                'total_venta' => $total,
                'tipo_pago' => $data['tipo_pago'],
                'monto_recibido' => $recibido,
                'cambio' => $cambio,
                'estado' => 'completada',
                'nota' => $data['nota'] ?? null,
            ]);

            foreach ($items as $item) {
                VentaDetalle::query()->create([
                    'venta_id' => $venta->id,
                    'inventario_id' => $item['inventario_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_venta_unidad' => $item['precio'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            try {
                $clienteSnap = null;
                if (!empty($data['cliente_id'])) {
                    $c = Cliente::query()->find($data['cliente_id']);
                    $clienteSnap = ['nombre' => $this->nombreCliente($c), 'ci_nit' => $this->documentoCliente($c)];
                }

                $hist = [];
                if (Schema::hasColumn('venta_historials', 'venta_id')) $hist['venta_id'] = $venta->id;
                if (Schema::hasColumn('venta_historials', 'nro_venta')) $hist['nro_venta'] = $nroVenta;
                if (Schema::hasColumn('venta_historials', 'cliente_nombre')) $hist['cliente_nombre'] = $clienteSnap['nombre'] ?? 'Cliente final';
                if (Schema::hasColumn('venta_historials', 'cliente_documento')) $hist['cliente_documento'] = $clienteSnap['ci_nit'] ?? null;
                if (Schema::hasColumn('venta_historials', 'sucursal_nombre')) $hist['sucursal_nombre'] = $venta->sucursal->nombre ?? '';
                if (Schema::hasColumn('venta_historials', 'usuario_nombre')) $hist['usuario_nombre'] = Auth::user()->name ?? '';
                if (Schema::hasColumn('venta_historials', 'fecha_venta')) $hist['fecha_venta'] = now();
                if (Schema::hasColumn('venta_historials', 'total_venta')) $hist['total_venta'] = $total;
                if (Schema::hasColumn('venta_historials', 'tipo_pago')) $hist['tipo_pago'] = $data['tipo_pago'];
                if (Schema::hasColumn('venta_historials', 'monto_recibido')) $hist['monto_recibido'] = $recibido;
                if (Schema::hasColumn('venta_historials', 'cambio')) $hist['cambio'] = $cambio;
                if (Schema::hasColumn('venta_historials', 'estado')) $hist['estado'] = 'completada';
                if (Schema::hasColumn('venta_historials', 'nota')) $hist['nota'] = $data['nota'] ?? null;
                if (Schema::hasColumn('venta_historials', 'detalles')) $hist['detalles'] = json_encode($items);

                if (!empty($hist)) VentaHistorial::query()->create($hist);
            } catch (\Throwable $eHist) {
                Log::warning('Historial de venta no registrado: ' . $eHist->getMessage());
            }

            try {
                $mov = [];
                if (Schema::hasColumn('arqueo_detalles', 'arqueo_id')) $mov['arqueo_id'] = $arqueo->id;
                if (Schema::hasColumn('arqueo_detalles', 'tipo')) $mov['tipo'] = 'ingreso';
                if (Schema::hasColumn('arqueo_detalles', 'concepto')) $mov['concepto'] = 'Venta ' . $nroVenta;
                if (Schema::hasColumn('arqueo_detalles', 'referencia')) $mov['referencia'] = (string) $venta->id;
                if (Schema::hasColumn('arqueo_detalles', 'monto')) $mov['monto'] = $total;

                if (!empty($mov)) {
                    ArqueoDetalle::query()->create($mov);
                    if (Schema::hasColumn('arqueos', 'total_ingresos')) $arqueo->increment('total_ingresos', $total);
                }
            } catch (\Throwable $eArqueo) {
                Log::warning('Ingreso al arqueo no registrado: ' . $eArqueo->getMessage());
            }

            VentaTmp::query()->where('usuario_id', Auth::id())->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta registrada exitosamente',
                'nro_venta' => $nroVenta,
                'redirect' => route('admin.ventas.index'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error al confirmar la venta: ' . $e->getMessage()], 500);
        }
    }
}