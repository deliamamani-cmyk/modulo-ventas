@extends('layouts.admin')

@section('content')
    <style>
        .report-range-group {
            display: flex;
            gap: .5rem;
            align-items: center;
            margin-bottom: .75rem;
        }

        .report-range-input {
            flex: 1 1 0;
            min-width: 0;
            width: 100%;
            height: 38px;
            padding: .4rem .65rem;
            border: 1px solid #d9dfe8;
            border-radius: .55rem;
            background: #fff;
            color: #2b2f36;
            font-size: .8rem;
            line-height: 1.2;
            box-sizing: border-box;
        }

        .report-range-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.8;
        }
    </style>
    <div class="page-heading">
        <h3>Reportes de Ventas</h3>
    </div>

    <div class="row g-3">
        {{--  Ventas por día --}}
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:38px;height:38px;background:#e8edfb;color:#435acf;"><i class="bi bi-calendar-event"></i></div>
                        <h6 class="mb-0 fw-bold">Ventas por día</h6>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.78rem;">Total de ventas agrupadas por día en un rango de fechas.</p>
                    <form method="GET" action="{{ route('admin.ventas.reportes.ver') }}">
                        <input type="hidden" name="tipo" value="dia">
                        <div class="report-range-group">
                            <input type="date" name="desde" class="report-range-input" value="{{ now()->startOfMonth()->toDateString() }}">
                            <input type="date" name="hasta" class="report-range-input" value="{{ now()->toDateString() }}">
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-eye me-1"></i>Ver</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Ventas por cliente --}}
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:38px;height:38px;background:#e6f6ec;color:#198754;"><i class="bi bi-people"></i></div>
                        <h6 class="mb-0 fw-bold">Ventas por cliente</h6>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.78rem;">Listado de ventas filtrado por un cliente específico.</p>
                    <form method="GET" action="{{ route('admin.ventas.reportes.ver') }}">
                        <input type="hidden" name="tipo" value="cliente">
                        <div class="mb-2">
                            <select name="cliente_id" class="form-select form-select-sm">
                                <option value="">Seleccionar cliente...</option>
                                @foreach ($clientes as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombres_apellidos ?? $c->nombre ?? ('Cliente #' . $c->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-eye me-1"></i>Ver</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Ventas por producto --}}
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:38px;height:38px;background:#e8edfb;color:#435acf;"><i class="bi bi-box-seam"></i></div>
                        <h6 class="mb-0 fw-bold">Ventas por producto</h6>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.78rem;">Productos vendidos en un período, con cantidades y totales.</p>
                    <form method="GET" action="{{ route('admin.ventas.reportes.ver') }}">
                        <input type="hidden" name="tipo" value="producto">
                        <div class="report-range-group">
                            <input type="date" name="desde" class="report-range-input" value="{{ now()->startOfMonth()->toDateString() }}">
                            <input type="date" name="hasta" class="report-range-input" value="{{ now()->toDateString() }}">
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-eye me-1"></i>Ver</button>
                    </form>
                </div>
            </div>
        </div>

        {{--  Ventas por sucursal --}}
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:38px;height:38px;background:#fdf3e4;color:#fd7e14;"><i class="bi bi-pin-map"></i></div>
                        <h6 class="mb-0 fw-bold">Ventas por sucursal</h6>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.78rem;">Ventas agrupadas por sucursal en un rango de fechas.</p>
                    <form method="GET" action="{{ route('admin.ventas.reportes.ver') }}">
                        <input type="hidden" name="tipo" value="sucursal">
                        <div class="report-range-group">
                            <input type="date" name="desde" class="report-range-input" value="{{ now()->startOfMonth()->toDateString() }}">
                            <input type="date" name="hasta" class="report-range-input" value="{{ now()->toDateString() }}">
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-eye me-1"></i>Ver</button>
                    </form>
                </div>
            </div>
        </div>

        {{--  Ventas por tipo de pago --}}
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:38px;height:38px;background:#fde8ef;color:#d63384;"><i class="bi bi-credit-card"></i></div>
                        <h6 class="mb-0 fw-bold">Ventas por tipo de pago</h6>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.78rem;">Distribución de ventas según el método de pago.</p>
                    <form method="GET" action="{{ route('admin.ventas.reportes.ver') }}">
                        <input type="hidden" name="tipo" value="pago">
                        <div class="report-range-group">
                            <input type="date" name="desde" class="report-range-input" value="{{ now()->startOfMonth()->toDateString() }}">
                            <input type="date" name="hasta" class="report-range-input" value="{{ now()->toDateString() }}">
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-eye me-1"></i>Ver</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Productos más vendidos --}}
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:38px;height:38px;background:#e8edfb;color:#435acf;"><i class="bi bi-graph-up-arrow"></i></div>
                        <h6 class="mb-0 fw-bold">Productos más vendidos</h6>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.78rem;">Ranking de los 20 productos con mayor cantidad de ventas.</p>
                    <form method="GET" action="{{ route('admin.ventas.reportes.ver') }}">
                        <input type="hidden" name="tipo" value="top">
                        <div class="report-range-group">
                            <input type="date" name="desde" class="report-range-input" value="{{ now()->startOfMonth()->toDateString() }}">
                            <input type="date" name="hasta" class="report-range-input" value="{{ now()->toDateString() }}">
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-eye me-1"></i>Ver</button>
                    </form>
                </div>
            </div>
        </div>

        {{--  Reporte general --}}
        <div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="d-flex align-items-center justify-content-center rounded-3 flex-shrink-0" style="width:38px;height:38px;background:#e6f6ec;color:#198754;"><i class="bi bi-file-earmark-text"></i></div>
                        <h6 class="mb-0 fw-bold">Reporte general</h6>
                    </div>
                    <p class="text-muted mb-3" style="font-size:.78rem;">Todas las ventas emitidas, con filtros de fechas.</p>
                    <form method="GET" action="{{ route('admin.ventas.reportes.ver') }}">
                        <input type="hidden" name="tipo" value="general">
                        <div class="report-range-group">
                            <input type="date" name="desde" class="report-range-input" value="{{ now()->startOfMonth()->toDateString() }}">
                            <input type="date" name="hasta" class="report-range-input" value="{{ now()->toDateString() }}">
                        </div>
                        <button class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-eye me-1"></i>Ver</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection