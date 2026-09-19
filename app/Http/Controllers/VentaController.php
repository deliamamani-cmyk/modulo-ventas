<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Arqueo;
use App\Models\ArqueoDetalle;
use App\Models\Inventario;
use App\Models\Venta;
use App\Models\VentaHistorial;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Exports\VentasExport;
use App\Models\Cliente;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class VentaController extends Controller
{
    // ============================================================
    // VENTAS REALIZADAS + MÉTRICAS + GRÁFICOS + FILTROS
    // ============================================================
    public function index(Request $request)
    {
        $ajuste = Ajuste::query()->first();
        $divisa = $ajuste->divisa ?? 'Bs.';

        $search     = $request->input('search');
        $estado     = $request->input('estado');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $query = Venta::with(['sucursal', 'cliente', 'usuario'])->orderBy('id', 'desc');

        // =====  FILTROS =====
        if (!empty($search)) {
            $query->where(function ($w) use ($search) {
                $w->where('id', 'like', "%{$search}%")
                    ->orWhere('nro_venta', 'like', "%{$search}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombres_apellidos', 'like', "%{$search}%"))
                    ->orWhereHas('sucursal', fn ($s) => $s->where('nombre', 'like', "%{$search}%"))
                    ->orWhereHas('usuario', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if (!empty($estado)) {
            if ($estado === 'emitida') {
                $query->whereIn('estado', ['emitida', 'completada']);
            } elseif ($estado === 'cancelada') {
                $query->whereIn('estado', ['cancelada', 'anulada']);
            } else {
                $query->where('estado', $estado);
            }
        }

        if (!empty($fechaDesde)) $query->whereDate('fecha_venta', '>=', $fechaDesde);
        if (!empty($fechaHasta)) $query->whereDate('fecha_venta', '<=', $fechaHasta);

        $ventas = $query->paginate(10)->withQueryString();

        // ===== MÉTRICAS (tarjetas) =====
        $activas = fn ($q) => $q->whereNotIn('estado', ['anulada', 'cancelada']);

        $ventasHoyCount = $activas(Venta::query()->whereDate('created_at', now()->toDateString()))->count();
        $ventasHoyTotal = (float) $activas(Venta::query()->whereDate('created_at', now()->toDateString()))->sum('total_venta');

        $ventasMesCount = $activas(Venta::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year))->count();
        $ventasMesTotal = (float) $activas(Venta::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year))->sum('total_venta');

        $totalRecaudado  = (float) $activas(Venta::query())->sum('total_venta');
        $canceladasCount = Venta::query()->whereIn('estado', ['anulada', 'cancelada'])->count();

        // ===== GRÁFICO BARRAS: últimos 7 días =====
        $ventas7Dias = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dia = now()->subDays($i);
            $ventas7Dias->push([
                'label' => $dia->format('d/m'),
                'total' => (float) $activas(Venta::query()->whereDate('created_at', $dia->toDateString()))->sum('total_venta'),
            ]);
        }

        // ===== GRÁFICO DONA: tipo de pago =====
        $tipoPago = $activas(Venta::query())
            ->select('tipo_pago', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('tipo_pago')
            ->get();

        return view('admin.ventas.index', compact(
            'ajuste', 'divisa', 'ventas', 'search', 'estado', 'fechaDesde', 'fechaHasta',
            'ventasHoyCount', 'ventasHoyTotal',
            'ventasMesCount', 'ventasMesTotal',
            'totalRecaudado', 'canceladasCount',
            'ventas7Dias', 'tipoPago'
        ));
    }

    // ============================================================
    // EXPORTAR A EXCEL (.xlsx) 
    // ============================================================
    public function exportar(Request $request)
    {
        $ajuste  = Ajuste::query()->first();
        $divisa  = $ajuste->divisa ?? 'Bs.';
        $empresa = $ajuste->nombre ?? $ajuste->nombre_empresa ?? 'Farmacia';

        $query = Venta::with(['sucursal', 'cliente', 'usuario'])->orderBy('id', 'desc');

        if (!empty($request->input('search'))) {
            $s = $request->input('search');
            $query->where(function ($w) use ($s) {
                $w->where('id', 'like', "%{$s}%")
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombres_apellidos', 'like', "%{$s}%"))
                    ->orWhereHas('sucursal', fn ($su) => $su->where('nombre', 'like', "%{$s}%"))
                    ->orWhereHas('usuario', fn ($u) => $u->where('name', 'like', "%{$s}%"));
            });
        }

        $estado = $request->input('estado');
        if (!empty($estado)) {
            if ($estado === 'emitida') {
                $query->whereIn('estado', ['emitida', 'completada']);
            } elseif ($estado === 'cancelada') {
                $query->whereIn('estado', ['cancelada', 'anulada']);
            }
        }

        if (!empty($request->input('fecha_desde'))) $query->whereDate('fecha_venta', '>=', $request->input('fecha_desde'));
        if (!empty($request->input('fecha_hasta'))) $query->whereDate('fecha_venta', '<=', $request->input('fecha_hasta'));

        $ventas = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ventas');

        $sheet->setCellValue('A1', $empresa);
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        $sheet->setCellValue('A2', 'Reporte de Ventas — ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

        $headers = ['# Venta', 'Cliente', 'Documento', 'Sucursal', 'Usuario', 'Fecha', 'Tipo Pago', 'Total', 'Estado'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $col++;
        }

        $row = 5;
        foreach ($ventas as $v) {
            $clienteNombre = 'Sin cliente';
            $documento = '';
            if ($v->cliente) {
                $clienteNombre = $v->cliente->nombres_apellidos ?? $v->cliente->nombre ?? 'Sin cliente';
                $documento = $v->cliente->ci_nit ?? '';
            }

            $sheet->setCellValue('A' . $row, $v->id);
            $sheet->setCellValue('B' . $row, $clienteNombre);
            $sheet->setCellValue('C' . $row, $documento);
            $sheet->setCellValue('D' . $row, $v->sucursal?->nombre ?? '');
            $sheet->setCellValue('E' . $row, $v->usuario?->name ?? '');
            $sheet->setCellValue('F' . $row, $v->fecha_venta ? \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y H:i') : '');
            $sheet->setCellValue('G' . $row, ucfirst(strtolower($v->tipo_pago ?? '')));
            $sheet->setCellValue('H' . $row, $divisa . ' ' . number_format((float) $v->total_venta, 2, '.', ','));
            $sheet->setCellValue('I' . $row, ucfirst($v->estado));
            $row++;
        }

        foreach (range('A', 'I') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'ventas_' . now()->format('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ============================================================
    // DETALLE DE VENTA 
    // ============================================================
    public function show($id)
    {
        $venta = Venta::findOrFail($id);

        $venta->load([
            'sucursal', 'cliente', 'usuario',
            'detalles.inventario.producto.categoria',
            'detalles.inventario.producto.laboratorio',
            'detalles.inventario.producto.formaFarmaceutica',
            'detalles.inventario.producto.presentacion',
            'detalles.inventario.lote',
        ]);

        $detalles = $venta->detalles->sortBy('id')->values();
        $totalItems = $detalles->count();
        $totalCantidad = (int) $detalles->sum('cantidad');

        $ajuste = Ajuste::query()->first();
        $divisa = $ajuste->divisa ?? 'Bs.';

        return view('admin.ventas.show', compact(
            'venta', 'detalles', 'totalItems', 'totalCantidad', 'ajuste', 'divisa'
        ));
    }

    // ============================================================
    // COMPROBANTE EN PDF (carta) CON QR
    // ============================================================
    public function imprimir(Venta $venta)
    {
        $ajuste = Ajuste::query()->first();

        $venta->load([
            'sucursal', 'cliente', 'usuario',
            'detalles.inventario.producto.categoria',
            'detalles.inventario.producto.laboratorio',
            'detalles.inventario.producto.formaFarmaceutica',
            'detalles.inventario.producto.presentacion',
            'detalles.inventario.lote',
        ]);

        $detalles = $venta->detalles->sortBy('id')->values();
        $totalItems = $detalles->count();
        $totalCantidad = (int) $detalles->sum('cantidad');

        $qrData = json_encode([
            'venta'   => $venta->id,
            'fecha'   => $venta->fecha_venta ? $venta->fecha_venta->format('Y-m-d H:i') : null,
            'cliente' => $venta->cliente ? ($venta->cliente->nombres_apellidos ?? 'Sin cliente') : 'Sin cliente',
            'total'   => number_format((float) $venta->total_venta, 2, '.', ','),
            'divisa'  => $ajuste->divisa ?? 'Bs.',
            'estado'  => $venta->estado,
        ], JSON_UNESCAPED_UNICODE);

        $qrCode = new QrCode($qrData);
        $writer = new PngWriter();
        $qrResult = $writer->write($qrCode);
        $qrDataUri = $qrResult->getDataUri();

        $pdf = Pdf::loadView('admin.ventas.imprimir', compact(
            'venta', 'detalles', 'totalItems', 'totalCantidad', 'ajuste', 'qrDataUri'
        ))->setPaper('letter');

        return $pdf->stream('venta_' . $venta->id . '.pdf');
    }

    // ============================================================
    // TICKET TÉRMICO (80mm) CON QR
    // ============================================================
    public function imprimirTicket(Venta $venta)
    {
        $ajuste = Ajuste::query()->first();

        $venta->load([
            'sucursal', 'cliente', 'usuario',
            'detalles.inventario.producto.categoria',
            'detalles.inventario.producto.laboratorio',
            'detalles.inventario.producto.formaFarmaceutica',
            'detalles.inventario.producto.presentacion',
            'detalles.inventario.lote',
        ]);

        $detalles = $venta->detalles->sortBy('id')->values();
        $totalItems = $detalles->count();
        $totalCantidad = (int) $detalles->sum('cantidad');

        $qrData = json_encode([
            'venta'   => $venta->id,
            'fecha'   => $venta->fecha_venta ? $venta->fecha_venta->format('d/m/Y H:i') : null,
            'cliente' => $venta->cliente ? ($venta->cliente->nombres_apellidos ?? 'Sin cliente') : 'Sin cliente',
            'total'   => number_format((float) $venta->total_venta, 2, '.', ','),
            'divisa'  => $ajuste->divisa ?? 'Bs.',
            'estado'  => $venta->estado,
        ], JSON_UNESCAPED_UNICODE);

        $qrCode = new QrCode($qrData);
        $writer = new PngWriter();
        $qrResult = $writer->write($qrCode);
        $qrDataUri = $qrResult->getDataUri();

        $pdf = Pdf::loadView('admin.ventas.imprimir-ticket', compact(
            'venta', 'detalles', 'totalItems', 'totalCantidad', 'ajuste', 'qrDataUri'
        ));

        $pdf->setOption([
            'dpi' => 120,
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Arial',
        ]);

        $pdf->setPaper([0, 0, 226.77, 9999999], 'portrait');

        return $pdf->stream('ticket_venta_' . $venta->id . '.pdf');
    }

    // ============================================================
    //  CANCELAR VENTA (redirect)
    // ============================================================
    public function cancel(Venta $venta)
    {
        if (! in_array($venta->estado, ['emitida', 'completada'])) {
            return redirect()->back()->with('error', 'Solo se pueden cancelar ventas en estado emitida.');
        }

        $resultado = $this->ejecutarAnulacion($venta);

        if ($resultado !== true) {
            return redirect()->back()->with('error', $resultado);
        }

        return redirect()->back()->with('success', 'Venta #' . $venta->id . ' cancelada exitosamente.');
    }

    // ============================================================
    // VERSIÓN JSON (para tu modal AJAX)
    // ============================================================
    public function anular($id)
    {
        $venta = Venta::findOrFail($id);

        if (! in_array($venta->estado, ['emitida', 'completada'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cancelar ventas en estado emitida.',
            ], 422);
        }

        $resultado = $this->ejecutarAnulacion($venta);

        if ($resultado !== true) {
            return response()->json(['success' => false, 'message' => $resultado], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Venta #' . $venta->id . ' cancelada exitosamente.',
        ]);
    }

    // ============================================================
    // LÓGICA DE ANULACIÓN (stock + arqueo + estado + historial)
    // ============================================================
    private function ejecutarAnulacion(Venta $venta)
    {
        DB::beginTransaction();

        try {
            $venta->load('detalles');

            foreach ($venta->detalles as $detalle) {
                Inventario::where('id', $detalle->inventario_id)
                    ->increment('stock_actual', $detalle->cantidad);
            }

            $detalleArqueo = ArqueoDetalle::where('tipo', 'ingreso')
                ->whereIn('referencia', [(string) $venta->id, 'venta_' . $venta->id])
                ->first();

            if ($detalleArqueo) {
                $arqueo = Arqueo::query()->find($detalleArqueo->arqueo_id);

                $detalleArqueo->delete();

                if ($arqueo && Schema::hasColumn('arqueos', 'total_ingresos')) {
                    $arqueo->decrement('total_ingresos', (float) $detalleArqueo->monto);
                }
            }

            $venta->update(['estado' => 'cancelada']);

            VentaHistorial::where('venta_id', $venta->id)->update(['estado' => 'cancelada']);

            DB::commit();

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();

            return 'Error al anular la venta: ' . $e->getMessage();
        }
    }

    // ============================================================
    // REPORTES DE VENTAS (página de 7 tarjetas)
    // ============================================================
    public function reportes()
    {
        $ajuste = Ajuste::query()->first();
        $divisa = $ajuste->divisa ?? 'Bs.';
        $clientes = Cliente::query()->orderBy('nombres_apellidos')->get();

        return view('admin.ventas.reportes', compact('ajuste', 'divisa', 'clientes'));
    }

        // ============================================================
    // VER REPORTE (pantalla + PDF vertical + Excel)
    // ============================================================
    public function verReporte(Request $request)
    {
        $ajuste  = Ajuste::query()->first();
        $divisa  = $ajuste->divisa ?? 'Bs.';
        $empresa = $ajuste->nombre ?? $ajuste->nombre_empresa ?? 'Farmacia';

        $tipo      = $request->input('tipo', 'general');
        $desde     = $request->input('desde') ?: now()->startOfMonth()->toDateString();
        $hasta     = $request->input('hasta') ?: now()->toDateString();
        $clienteId = $request->input('cliente_id');
        $formato   = $request->input('formato');

        $base = Venta::with(['sucursal', 'cliente', 'usuario', 'detalles.inventario.producto'])
            ->whereNotIn('estado', ['anulada', 'cancelada'])
            ->whereDate('fecha_venta', '>=', $desde)
            ->whereDate('fecha_venta', '<=', $hasta);

        $titulos = [
            'dia'      => 'Reporte de ventas por día',
            'cliente'  => 'Reporte de ventas por cliente',
            'producto' => 'Reporte de ventas por producto',
            'sucursal' => 'Reporte de ventas por sucursal',
            'pago'     => 'Reporte de ventas por tipo de pago',
            'top'      => 'Reporte de productos más vendidos (Top 20)',
            'general'  => 'Reporte general de ventas',
        ];
        $titulo = $titulos[$tipo] ?? 'Reporte de ventas';

        $modo = in_array($tipo, ['producto', 'top']) ? 'productos'
              : (in_array($tipo, ['sucursal', 'pago']) ? 'grupos' : 'ventas');

        $ventas = collect();
        $productos = [];
        $grupos = [];

        if ($modo === 'ventas') {
            $q = clone $base;
            if ($tipo === 'cliente' && $clienteId) $q->where('cliente_id', $clienteId);
            $ventas = $q->orderByDesc('id')->get();
            $totalRegistros = $ventas->count();
            $totalBs = (float) $ventas->sum('total_venta');
        } elseif ($modo === 'grupos') {
            $todas = $base->get();
            $totalRegistros = $todas->count();
            $totalBs = (float) $todas->sum('total_venta');

            $agrupadas = $tipo === 'sucursal'
                ? $todas->groupBy(fn ($v) => $v->sucursal?->nombre ?? '—')
                : $todas->groupBy(fn ($v) => ucfirst(strtolower($v->tipo_pago ?? '—')));

            foreach ($agrupadas as $nombre => $g) {
                $grupos[] = [
                    'nombre' => $nombre,
                    'ventas' => $g->count(),
                    'total'  => (float) $g->sum('total_venta'),
                ];
            }
        } else {
            $map = [];
            foreach ($base->get() as $v) {
                foreach ($v->detalles as $d) {
                    $prod   = $d->inventario?->producto;
                    $nombre = $prod?->nombre_comercial ?? 'Producto';
                    $codigo = $prod?->codigo_producto ?? '—';
                    $key    = $codigo . '|' . $nombre;

                    if (!isset($map[$key])) {
                        $concentracion = $prod?->concentracion ?? '';
                        $unidad = $prod?->unidad ?? $prod?->unidad_medida ?? '';
                        $map[$key] = [
                            'nombre'       => $nombre,
                            'codigo'       => $codigo,
                            'presentacion' => trim($concentracion . ' ' . $unidad) ?: '—',
                            'laboratorio'  => $prod?->laboratorio?->nombre ?? '—',
                            'categoria'    => $prod?->categoria?->nombre ?? '—',
                            'cant'         => 0,
                            'total'        => 0.0,
                        ];
                    }
                    $map[$key]['cant'] += $d->cantidad;
                    $map[$key]['total'] += (float) $d->subtotal;
                }
            }
            usort($map, fn ($a, $b) => $b['cant'] <=> $a['cant']);
            if ($tipo === 'top') $map = array_slice($map, 0, 20);
            $productos = $map;
            $totalRegistros = (int) array_sum(array_column($map, 'cant'));
            $totalBs = (float) array_sum(array_column($map, 'total'));
        }

        $data = compact(
            'ajuste', 'divisa', 'empresa', 'titulo', 'tipo', 'modo',
            'ventas', 'productos', 'grupos', 'totalRegistros', 'totalBs', 'desde', 'hasta'
        );

        // ===========================================================
        // PDF — VERTICAL (portrait) 
        // ===========================================================
        if ($formato === 'pdf') {
            $pdf = Pdf::loadView('admin.ventas.reporte_pdf', $data)->setPaper('letter');
            return $pdf->stream('reporte_' . $tipo . '_' . now()->format('Ymd_His') . '.pdf');
        }

        // ===========================================================
        // EXCEL
        // ===========================================================
        if ($formato === 'excel') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte');

            $lastCol = $modo === 'ventas' ? 'H' : ($modo === 'productos' ? 'G' : 'D');

            $sheet->setCellValue('A1', $empresa);
            $sheet->mergeCells('A1:' . $lastCol . '1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

            $sheet->setCellValue('A2', $titulo);
            $sheet->mergeCells('A2:' . $lastCol . '2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

            $sheet->setCellValue('A3',
                now()->format('d/m/Y H:i') .
                ' | Desde: ' . \Carbon\Carbon::parse($desde)->format('d/m/Y') .
                ' | Hasta: ' . \Carbon\Carbon::parse($hasta)->format('d/m/Y')
            );
            $sheet->mergeCells('A3:' . $lastCol . '3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('666666');

            if ($modo === 'ventas') {
                $headers = ['# Venta', 'Cliente', 'Documento', 'Sucursal', 'Usuario', 'Fecha', 'Tipo Pago', 'Total'];
            } elseif ($modo === 'productos') {
                $headers = ['#', 'Producto', 'Presentación', 'Laboratorio', 'Categoría', 'Cantidad', 'Total'];
            } else {
                $headers = ['#', $tipo === 'sucursal' ? 'Sucursal' : 'Tipo de pago', 'Ventas', 'Total'];
            }

            foreach ($headers as $i => $h) {
                $sheet->setCellValue(chr(65 + $i) . '5', $h);
            }
            $headerRange = 'A5:' . $lastCol . '5';
            $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A73E8');

            $row = 6;
            if ($modo === 'ventas') {
                foreach ($ventas as $v) {
                    $sheet->fromArray([
                        $v->id,
                        $v->cliente?->nombres_apellidos ?? 'Sin cliente',
                        $v->cliente?->ci_nit ?? '',
                        $v->sucursal?->nombre ?? '—',
                        $v->usuario?->name ?? '—',
                        $v->created_at ? \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i') : '',
                        ucfirst(strtolower($v->tipo_pago ?? '—')),
                        $divisa . ' ' . number_format((float) $v->total_venta, 2, '.', ','),
                    ], null, 'A' . $row);
                    $row++;
                }
                $sheet->setCellValue('G' . $row, 'Total ' . $totalRegistros . ' ventas');
                $sheet->setCellValue('H' . $row, $divisa . ' ' . number_format($totalBs, 2, '.', ','));
            } elseif ($modo === 'productos') {
                $n = 1;
                foreach ($productos as $p) {
                    $sheet->fromArray([
                        $n++, $p['nombre'], $p['presentacion'], $p['laboratorio'], $p['categoria'], $p['cant'],
                        $divisa . ' ' . number_format($p['total'], 2, '.', ','),
                    ], null, 'A' . $row);
                    $row++;
                }
                $sheet->setCellValue('F' . $row, 'Totales:');
                $sheet->setCellValue('G' . $row, $divisa . ' ' . number_format($totalBs, 2, '.', ','));
            } else {
                $n = 1;
                foreach ($grupos as $g) {
                    $sheet->fromArray([
                        $n++, $g['nombre'], $g['ventas'], $divisa . ' ' . number_format($g['total'], 2, '.', ','),
                    ], null, 'A' . $row);
                    $row++;
                }
                $sheet->setCellValue('C' . $row, 'Totales:');
                $sheet->setCellValue('D' . $row, $divisa . ' ' . number_format($totalBs, 2, '.', ','));
            }

            $totalRange = 'A' . $row . ':' . $lastCol . $row;
            $sheet->getStyle($totalRange)->getFont()->setBold(true);
            $sheet->getStyle($totalRange)->getAlignment()->setHorizontal('right');
            $sheet->getStyle($totalRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8EE');

            $row += 2;
            $sheet->setCellValue('A' . $row, $empresa . ' — Reporte generado ' . now()->format('d/m/Y H:i'));
            $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A' . $row)->getFont()->setSize(9)->getColor()->setRGB('888888');

            foreach (range('A', $lastCol) as $c) {
                $sheet->getColumnDimension($c)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, 'reporte_' . $tipo . '_' . now()->format('Ymd_His') . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        // ===========================================================
        // VISTA EN PANTALLA
        // ===========================================================
        return view('admin.ventas.reporte_ver', $data);
    }
}