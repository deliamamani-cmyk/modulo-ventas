@extends('layouts.admin')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        #modalCancelarVenta .modal-dialog { margin: 3.5rem auto; }
        #modalVistaPrevia .modal-dialog { margin: 2rem auto; }
        #iframePdf { width: 100%; height: 75vh; border: 0; background: #525659; }

        .card-metrica .icono {
            width: 48px; height: 48px; border-radius: 10px; color: #fff;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .card-metrica .icono i { display: block; line-height: 1; font-size: 1.3rem; }
        .ventas-page { font-size: .84rem; }
        .ventas-page h3 { font-size: 1.3rem; }
        .ventas-page .btn { font-size: .8rem; }
        .ventas-page .card-metrica .titulo { font-size: .8rem; text-transform: uppercase; letter-spacing: .3px; }
        .ventas-page .card-metrica .valor { font-size: 1.35rem; font-weight: 700; line-height: 1.2; }
        .ventas-page .card-metrica .sub { font-size: .8rem; }
        .ventas-page .card-grafico .card-title { font-size: 1.05rem; }
        .ventas-page .card-header { padding: .95rem 1.15rem; }
        .ventas-page .card-title { font-size: 1.05rem; }
        .ventas-page .form-control,
        .ventas-page .form-select { font-size: .8rem; }
        .ventas-page .table th,
        .ventas-page .table td { font-size: .84rem; }
        .ventas-page .table th { white-space: nowrap; }
        .ventas-page .table td { vertical-align: middle; line-height: 1.4; }
        .ventas-page .acciones-col { min-width: 130px; width: 130px; white-space: nowrap; }
    </style>

    <div class="ventas-page page-heading">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3>Ventas realizadas</h3>
            <a href="{{ route('admin.carrito_ventas.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-cart-plus me-1"></i>Nueva venta
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-center justify-content-between" role="alert">
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-center justify-content-between" role="alert">
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ===== TARJETAS MÉTRICAS ===== --}}
    <div class="ventas-page row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card card-metrica shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="icono" style="background:#435acf;"><i class="bi bi-cart-fill"></i></div>
                    <div><div class="text-muted titulo">Ventas hoy</div>
                        <div class="valor">{{ $ventasHoyCount }}</div>
                        <div class="text-muted sub">{{ $divisa }}{{ number_format($ventasHoyTotal, 2, '.', ',') }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card card-metrica shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="icono" style="background:#198754;"><i class="bi bi-bag-check-fill"></i></div>
                    <div><div class="text-muted titulo">Ventas del mes</div>
                        <div class="valor">{{ $ventasMesCount }}</div>
                        <div class="text-muted sub">{{ $divisa }}{{ number_format($ventasMesTotal, 2, '.', ',') }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card card-metrica shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="icono" style="background:#0dcaf0;"><i class="bi bi-wallet2"></i></div>
                    <div><div class="text-muted titulo">Total recaudado</div>
                        <div class="valor">{{ $divisa }}{{ number_format($totalRecaudado, 2, '.', ',') }}</div>
                        <div class="text-muted sub">Todas las emitidas</div></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card card-metrica shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="icono" style="background:#dc3545;"><i class="bi bi-x-circle-fill"></i></div>
                    <div><div class="text-muted titulo">Canceladas</div>
                        <div class="valor">{{ $canceladasCount }}</div>
                        <div class="text-muted sub">Ventas anuladas</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== GRÁFICOS CON TAMAÑO CONTROLADO ===== --}}
    <div class="ventas-page row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card card-grafico shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Ventas de los últimos 7 días</h5>
                    {{-- Altura fija: el gráfico NO se gigante --}}
                    <div style="height: 250px;">
                        <canvas id="chartVentas7"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-grafico shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Tipo de pago</h5>
                    {{-- Dona pequeña y centrada como el video --}}
                    <div class="d-flex justify-content-center" style="height: 210px;">
                        <canvas id="chartTipoPago"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== LISTADO CON FILTROS Y EXPORTAR ===== --}}
    <section class="ventas-page section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Listado de ventas</h4>
                        <a href="{{ route('admin.ventas.exportar', request()->query()) }}" class="btn btn-success btn-sm">
                            <i class="bi bi-file-earmark-excel me-1"></i>Exportar
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.ventas.index') }}" class="row g-2 mb-3">
                            <div class="col-md-4">
                                <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                                    placeholder="Buscar por ID, cliente, sucursal, usuario...">
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-select form-select-sm">
                                    <option value="">Todos los estados</option>
                                    <option value="emitida" @selected($estado === 'emitida')>Emitida</option>
                                    <option value="cancelada" @selected($estado === 'cancelada')>Cancelada</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="bi bi-search me-1"></i>Buscar</button>
                                <a href="{{ route('admin.ventas.index') }}" class="btn btn-sm btn-light border" title="Limpiar filtros">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Sucursal</th>
                                        <th>Cliente</th>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                        <th>Tipo pago</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th class="acciones-col text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($ventas as $venta)
                                        @php
                                            $clienteNombre = 'Sin cliente';
                                            if ($venta->cliente) {
                                                $c = $venta->cliente;
                                                $clienteNombre = $c->nombres_apellidos ?? $c->nombre_completo ?? $c->nombre ?? trim(($c->nombres ?? '') . ' ' . ($c->apellidos ?? '')) ?: ('Cliente #' . $c->id);
                                            }
                                            $cancelada = in_array($venta->estado, ['anulada', 'cancelada']);
                                        @endphp
                                        <tr>
                                            <td>{{ $venta->id }}</td>
                                            <td>{{ $venta->sucursal->nombre ?? '—' }}</td>
                                            <td>{{ $clienteNombre }}</td>
                                            <td>{{ $venta->usuario->name ?? '—' }}</td>
                                            <td>{{ $venta->fecha_venta ? \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') : '—' }}</td>
                                            <td>{{ ucfirst(strtolower($venta->tipo_pago ?? '—')) }}</td>
                                            <td><strong>{{ $divisa }} {{ number_format((float) $venta->total_venta, 2, '.', ',') }}</strong></td>
                                            <td>
                                                @if ($cancelada)
                                                    <span class="badge bg-danger">Cancelada</span>
                                                @else
                                                    <span class="badge bg-success">Emitida</span>
                                                @endif
                                            </td>
                                            <td class="acciones-col text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <a href="{{ route('admin.ventas.show', $venta->id) }}" class="btn btn-sm btn-info" title="Ver venta">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    @if (! $cancelada)
                                                        <button type="button" class="btn btn-sm btn-warning" title="Ver factura"
                                                            onclick="abrirVistaPrevia('{{ route('admin.ventas.imprimir', $venta->id) }}', 'factura')">
                                                            <i class="bi bi-printer"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning" title="Ver ticket"
                                                            onclick="abrirVistaPrevia('{{ route('admin.ventas.ticket', $venta->id) }}', 'ticket')">
                                                            <i class="bi bi-receipt"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" title="Cancelar venta"
                                                            data-url="{{ route('admin.ventas.anular', $venta->id) }}"
                                                            data-id="{{ $venta->id }}"
                                                            data-total="{{ number_format((float) $venta->total_venta, 2, '.', ',') }}"
                                                            onclick="abrirCancelacion(this)">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">No hay ventas registradas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($ventas->count() > 0)
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
                                <small class="text-muted">
                                    Mostrando {{ $ventas->firstItem() }} a {{ $ventas->lastItem() }} de {{ $ventas->total() }} registros
                                </small>
                                <div>{{ $ventas->links('vendor.pagination.bootstrap-5-no-summary') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== MODAL VISTA PREVIA PDF ===== --}}
    <div class="modal fade" id="modalVistaPrevia" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title">Vista previa</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" onclick="imprimirPdf()">
                            <i class="bi bi-printer me-1"></i>Imprimir
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
                <div class="modal-body p-0">
                    <iframe id="iframePdf" src="about:blank"></iframe>
                </div>
            </div>
        </div>
    </div>

    {{-- =====  MODAL CANCELAR VENTA ===== --}}
    <div class="modal fade" id="modalCancelarVenta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Cancelar Venta <span id="txtNro">#—</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1" style="font-size: .85rem;">
                        ¿Está seguro de cancelar esta venta de <strong class="text-primary">{{ $divisa }}<span id="txtTotal">0.00</span></strong>?
                    </p>
                    <p class="text-muted mb-0" style="font-size: .75rem;">
                        Se devolverá el stock al inventario y se revertirá el ingreso del arqueo. Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                    <button type="button" class="btn btn-danger" id="btnCancelarVenta">
                        <i class="bi bi-x-circle me-1"></i>Sí, cancelar venta
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        let urlCancelar = null;

        // ===== BARRAS  =====
        new Chart(document.getElementById('chartVentas7'), {
            type: 'bar',
            data: {
                labels: @json($ventas7Dias->pluck('label')),
                datasets: [{
                    label: 'Ventas',
                    data: @json($ventas7Dias->pluck('total')),
                    backgroundColor: '#435acf',
                    borderRadius: 4,
                    maxBarThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,   
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: (v) => '{{ $divisa }}' + v } }
                }
            }
        });

        // ===== DONA =====
        new Chart(document.getElementById('chartTipoPago'), {
            type: 'doughnut',
            data: {
                labels: @json($tipoPago->pluck('tipo_pago')),
                datasets: [{
                    data: @json($tipoPago->pluck('cantidad')),
                    backgroundColor: ['#435acf', '#198754', '#ffc107', '#dc3545', '#0dcaf0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,   
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }
                }
            }
        });

        // =====  VISTA PREVIA PDF =====
        const modalVista = new bootstrap.Modal(document.getElementById('modalVistaPrevia'));
        const iframePdf = document.getElementById('iframePdf');
        const dialogVista = document.querySelector('#modalVistaPrevia .modal-dialog');

        window.abrirVistaPrevia = function (url, tipo) {
            if (tipo === 'ticket') {
                dialogVista.classList.remove('modal-xl');
                dialogVista.style.maxWidth = '430px';
            } else {
                dialogVista.classList.add('modal-xl');
                dialogVista.style.maxWidth = '';
            }
            iframePdf.src = url;
            modalVista.show();
        };

        window.imprimirPdf = function () {
            iframePdf.contentWindow.focus();
            iframePdf.contentWindow.print();
        };

        document.getElementById('modalVistaPrevia').addEventListener('hidden.bs.modal', function () {
            iframePdf.src = 'about:blank';
        });

        // =====  CANCELAR VENTA =====
        const modalCancelar = new bootstrap.Modal(document.getElementById('modalCancelarVenta'));

        window.abrirCancelacion = function (btn) {
            urlCancelar = btn.getAttribute('data-url');
            document.getElementById('txtNro').textContent = '#' + btn.getAttribute('data-id');
            document.getElementById('txtTotal').textContent = btn.getAttribute('data-total');
            modalCancelar.show();
        };

        document.getElementById('btnCancelarVenta').addEventListener('click', function () {
            if (!urlCancelar) return;

            const btn = this;
            btn.disabled = true;

            fetch(urlCancelar, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                btn.disabled = false;

                if (ok && d.success) {
                    modalCancelar.hide();
                    Swal.fire({ icon: 'success', title: d.message, timer: 2500, showConfirmButton: false, position: 'top-end' })
                        .then(() => window.location.reload());
                } else {
                    modalCancelar.hide();
                    Swal.fire('Error', d.message || 'No se pudo cancelar la venta.', 'error');
                }
            })
            .catch(() => {
                btn.disabled = false;
                modalCancelar.hide();
                Swal.fire('Error', 'Error de conexión.', 'error');
            });
        });
    });
</script>
@endpush