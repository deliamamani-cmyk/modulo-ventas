@extends('layouts.admin')

@section('content')
    <style>
        .tabla-reporte thead th {
            background: #d8d8e4;
            border: none;
            text-transform: uppercase;
            font-size: .72rem;
            letter-spacing: .3px;
        }
        .tabla-reporte tbody td { border: none; vertical-align: middle; }
        .tabla-reporte tfoot tr { background: #d8d8e4; }
    </style>

    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3>{{ ucfirst($titulo) }}</h3>
            @if (!($esPdf ?? false))
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.ventas.reportes.ver', array_merge(request()->query(), ['formato' => 'pdf'])) }}"
                        class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF</a>
                    <a href="{{ route('admin.ventas.reportes.ver', array_merge(request()->query(), ['formato' => 'excel'])) }}"
                        class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel-fill me-1"></i>Excel</a>
                    <a href="{{ route('admin.ventas.reportes') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            @endif
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="fw-bold mb-1">{{ $empresa }}</h5>
                        <div class="text-muted" style="font-size:.8rem;">{{ $titulo }} | {{ $desde }} al {{ $hasta }}</div>
                        <div class="text-muted mb-3" style="font-size:.8rem;">Emitido: {{ now()->format('d/m/Y H:i') }}</div>

                        <div class="d-flex gap-2 mb-3">
                            <div class="border rounded px-3 py-2">
                                <div class="text-muted" style="font-size:.72rem;">Total registros</div>
                                <div class="fw-bold">{{ $totalRegistros }}</div>
                            </div>
                            <div class="border rounded px-3 py-2">
                                <div class="text-muted" style="font-size:.72rem;">Total {{ $divisa }}</div>
                                <div class="fw-bold">{{ $divisa }} {{ number_format($totalBs, 2, '.', ',') }}</div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped tabla-reporte align-middle mb-0">
                                @if ($modo === 'ventas')
                                    {{-- ===== DETALLE DE VENTAS ===== --}}
                                    <thead>
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>Venta ID</th>
                                            <th>Cliente</th>
                                            <th>Sucursal</th>
                                            <th>Usuario</th>
                                            <th>Fecha</th>
                                            <th>Tipo pago</th>
                                            <th class="text-end">Total</th>
                                            @if (!($esPdf ?? false))<th class="text-center">Acción</th>@endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($ventas as $i => $v)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td><a href="{{ route('admin.ventas.show', $v->id) }}" class="fw-bold text-primary">#{{ $v->id }}</a></td>
                                                <td>{{ $v->cliente?->nombres_apellidos ?? 'Sin cliente' }}</td>
                                                <td>{{ $v->sucursal?->nombre ?? '—' }}</td>
                                                <td>{{ $v->usuario?->name ?? '—' }}</td>
                                                <td>
                                                    {{ $v->created_at
                                                        ? \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i')
                                                        : ($v->fecha_venta ? \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') : '—') }}
                                                </td>
                                                <td>{{ ucfirst(strtolower($v->tipo_pago ?? '—')) }}</td>
                                                <td class="text-end">{{ $divisa }} {{ number_format((float) $v->total_venta, 2, '.', ',') }}</td>
                                                @if (!($esPdf ?? false))
                                                    <td class="text-center">
                                                        <a href="{{ route('admin.ventas.show', $v->id) }}" class="btn btn-sm btn-info py-0" title="Ver venta">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr><td colspan="9" class="text-center text-muted py-4">No hay datos en el período seleccionado.</td></tr>
                                        @endforelse
                                    </tbody>
                                    @if ($ventas->count() > 0)
                                        <tfoot>
                                            <tr>
                                                <td colspan="7" class="text-end fw-bold">Totales:</td>
                                                <td class="text-end fw-bold">{{ $divisa }} {{ number_format($totalBs, 2, '.', ',') }}</td>
                                                @if (!($esPdf ?? false))<td></td>@endif
                                            </tr>
                                        </tfoot>
                                    @endif

                                @elseif ($modo === 'grupos')
                                    {{-- ===== AGRUPADO: SUCURSAL / TIPO DE PAGO ===== --}}
                                    <thead>
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>{{ $tipo === 'sucursal' ? 'Sucursal' : 'Tipo de pago' }}</th>
                                            <th class="text-end">Ventas</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($grupos as $i => $g)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $g['nombre'] }}</td>
                                                <td class="text-end">{{ $g['ventas'] }}</td>
                                                <td class="text-end">{{ $divisa }} {{ number_format($g['total'], 2, '.', ',') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted py-4">No hay datos en el período seleccionado.</td></tr>
                                        @endforelse
                                    </tbody>
                                    @if (count($grupos) > 0)
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">Totales:</td>
                                                <td class="text-end fw-bold">{{ $divisa }} {{ number_format($totalBs, 2, '.', ',') }}</td>
                                            </tr>
                                        </tfoot>
                                    @endif

                                @else
                                    {{-- ===== PRODUCTOS ===== --}}
                                    <thead>
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>Producto</th>
                                            <th>Presentación</th>
                                            <th>Laboratorio</th>
                                            <th>Categoría</th>
                                            <th class="text-end">Cantidad</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($productos as $i => $p)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $p['nombre'] }}</td>
                                                <td>{{ $p['presentacion'] }}</td>
                                                <td>{{ $p['laboratorio'] }}</td>
                                                <td>{{ $p['categoria'] }}</td>
                                                <td class="text-end">{{ $p['cant'] }}</td>
                                                <td class="text-end">{{ $divisa }} {{ number_format($p['total'], 2, '.', ',') }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center text-muted py-4">No hay datos en el período seleccionado.</td></tr>
                                        @endforelse
                                    </tbody>
                                    @if (count($productos) > 0)
                                        <tfoot>
                                            <tr>
                                                <td colspan="6" class="text-end fw-bold">Totales:</td>
                                                <td class="text-end fw-bold">{{ $divisa }} {{ number_format($totalBs, 2, '.', ',') }}</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection