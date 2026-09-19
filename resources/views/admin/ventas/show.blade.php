@extends('layouts.admin')

@section('content')
    @php
        $clienteNombre = 'Sin cliente';
        $clienteDoc = 'N/A';
        if ($venta->cliente) {
            $c = $venta->cliente;
            $clienteNombre = $c->nombres_apellidos ?? $c->nombre_completo ?? $c->nombre ?? trim(($c->nombres ?? '') . ' ' . ($c->apellidos ?? '')) ?: ('Cliente #' . $c->id);
            $clienteDoc = $c->ci_nit ?? $c->ci ?? $c->nit ?? 'N/A';
        }
    @endphp

    <style>
        .titulo-pagina { font-size: 1.35rem; font-weight: 700; }
        .sub-pagina { font-size: .8rem; }
        .card .etiqueta { font-size: .72rem; }
        .card .valor-info { font-size: .95rem; font-weight: 700; }
        .card .valor-info-sub { font-size: .72rem; }
        .card .valor-metrica { font-size: 1.35rem; font-weight: 700; }
        .table th { font-size: .78rem; }
        .table td { font-size: .8rem; }
        .card-title { font-size: .95rem; }

        /* encabezado gris, SIN bordes verticales */
        .tabla-detalle thead th {
            background: #d8d8e4;
            border: none;
        }
        .tabla-detalle tbody td {
            border: none;
            vertical-align: middle;
        }
        /*  Fila de totales con fondo gris */
        .tabla-detalle tfoot tr {
            background: #d8d8e4;
        }
    </style>

    {{-- ===== ENCABEZADO ===== --}}
    <div class="page-heading">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="titulo-pagina mb-1">Detalle de Venta #{{ $venta->id }}</h3>
                <small class="text-muted sub-pagina">Vista detallada de la venta registrada.</small>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0" style="font-size: .8rem;">
                    <li class="breadcrumb-item"><a href="{{ url('/home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.ventas.index') }}">Ventas</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.ventas.index') }}" class="btn btn-light-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver al Listado
        </a>
    </div>

    {{-- ===== TARJETAS DE INFORMACIÓN ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body py-3">
                    <div class="etiqueta text-muted mb-1">Fecha de Venta</div>
                    <div class="valor-info">{{ $venta->fecha_venta ? \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') : '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body py-3">
                    <div class="etiqueta text-muted mb-1">Sucursal</div>
                    <div class="valor-info">{{ $venta->sucursal->nombre ?? '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body py-3">
                    <div class="etiqueta text-muted mb-1">Cliente</div>
                    <div class="valor-info">{{ $clienteNombre }}</div>
                    <div class="valor-info-sub text-muted">{{ $clienteDoc }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body py-3">
                    <div class="etiqueta text-muted mb-1">Registrado por</div>
                    <div class="valor-info">{{ $venta->usuario->name ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MÉTRICAS ===== --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm" style="border-left: 4px solid #435ebe;">
                <div class="card-body py-3">
                    <div class="etiqueta text-muted mb-1">Ítems</div>
                    <div class="valor-metrica">{{ $totalItems }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm" style="border-left: 4px solid #00c4ff;">
                <div class="card-body py-3">
                    <div class="etiqueta text-muted mb-1">Cantidad Total</div>
                    <div class="valor-metrica">{{ $totalCantidad }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm" style="border-left: 4px solid #198754;">
                <div class="card-body py-3">
                    <div class="etiqueta text-muted mb-1">Monto Total</div>
                    <div class="valor-metrica text-primary">{{ $divisa }} {{ number_format((float) $venta->total_venta, 2, '.', ',') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm" style="border-left: 4px solid #ffc107;">
                <div class="card-body py-3">
                    <div class="etiqueta text-muted mb-1">Tipo de pago</div>
                    <div class="valor-metrica text-capitalize">{{ $venta->tipo_pago ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== DETALLE DE PRODUCTOS ===== --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Detalle de Productos</h4>

            @if (in_array($venta->estado, ['completada', 'emitida']))
                <span class="badge bg-success">Emitida</span>
            @elseif (in_array($venta->estado, ['anulada', 'cancelada']))
                <span class="badge bg-danger">Cancelada</span>
            @else
                <span class="badge bg-warning text-dark">{{ ucfirst($venta->estado) }}</span>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped tabla-detalle align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Producto</th>
                            <th>Lote</th>
                            {{-- Cantidad y P. Venta a la DERECHA --}}
                            <th class="text-end">Cantidad</th>
                            <th class="text-end">P. Venta</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($detalles as $i => $d)
                            @php
                                $prod = $d->inventario?->producto;
                                $lote = $d->inventario?->lote;
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $prod->nombre_comercial ?? '—' }}</strong><br>
                                    <small class="text-muted">Código: {{ $prod->codigo_producto ?? '—' }}</small><br>
                                    <small class="text-muted">{{ $prod->nombre_generico ?? '' }}</small>
                                    @if (!empty($prod->concentracion))
                                        <br><small class="text-muted">{{ $prod->concentracion }}</small>
                                    @endif
                                </td>
                                <td>{{ $lote->numero_lote ?? '—' }}</td>
                                <td class="text-end">{{ $d->cantidad }}</td>
                                <td class="text-end">{{ $divisa }} {{ number_format((float) $d->precio_venta_unidad, 2, '.', ',') }}</td>
                                <td class="text-end">{{ $divisa }} {{ number_format((float) $d->subtotal, 2, '.', ',') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Sin productos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Total Venta:</td>
                            <td class="text-end fw-bold">{{ $divisa }} {{ number_format((float) $venta->total_venta, 2, '.', ',') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- ===== NOTA ===== --}}
            <div class="border rounded bg-light p-3 mt-3">
                <small class="text-muted">Nota: {{ $venta->nota ?: 'ninguno' }}</small>
            </div>
        </div>
    </div>
@endsection