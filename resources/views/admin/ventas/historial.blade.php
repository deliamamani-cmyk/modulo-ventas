@extends('layouts.admin')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        #modalVistaPrevia .modal-dialog { margin: 2rem auto; }
        #iframePdf { width: 100%; height: 75vh; border: 0; background: #525659; }
        .historial-page { font-size: .84rem; }
        .historial-page h3 { font-size: 1.3rem; }
        .historial-page .page-heading .btn { font-size: .8rem; padding: .5rem .75rem; }
        .historial-page .card-header { padding: .95rem 1.15rem; }
        .historial-page .card-title { font-size: 1.05rem; }
        .historial-page .card-body { padding: 1.15rem; }
        .historial-page .form-control,
        .historial-page .form-select,
        .historial-page .btn { font-size: .8rem; }
        .historial-page .table th,
        .historial-page .table td { font-size: .84rem; }
        .historial-page .table th { white-space: nowrap; }
        .historial-page .table td { line-height: 1.4; }
        .historial-page .table small { font-size: .8rem; }
        .historial-page .acciones-col { min-width: 130px; white-space: nowrap; }
    </style>

    <div class="historial-page page-heading">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3>Historial de Ventas</h3>
            <a href="{{ route('admin.ventas.index') }}" class="btn btn-light border btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Volver a Ventas
            </a>
        </div>
    </div>

    <section class="historial-page section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Registro historico de ventas (snapshot)</h4>
                    </div>
                    <div class="card-body">
                        {{-- =====  FILTROS ===== --}}
                        <form method="GET" action="{{ route('admin.ventas.historial') }}" class="row g-2 mb-3">
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
                                <a href="{{ route('admin.ventas.historial') }}" class="btn btn-sm btn-light border" title="Limpiar filtros">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th>Venta ID</th>
                                        <th>Cliente</th>
                                        <th>Sucursal</th>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th class="acciones-col text-center">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($historials as $h)
                                        @php $cancelada = in_array($h->estado, ['anulada', 'cancelada']); @endphp
                                        <tr>
                                            <td>{{ $h->id }}</td>
                                            <td><a href="{{ route('admin.ventas.show', $h->venta_id) }}" class="fw-bold text-primary">#{{ $h->venta_id }}</a></td>
                                            <td>
                                                {{ $h->cliente_nombre ?? 'Sin cliente' }}<br>
                                                <small class="text-muted">{{ $h->cliente_documento ?? '' }}</small>
                                            </td>
                                            <td>{{ $h->sucursal_nombre ?? '—' }}</td>
                                            <td>{{ $h->usuario_nombre ?? '—' }}</td>
                                            <td>{{ $h->fecha_venta ? \Carbon\Carbon::parse($h->fecha_venta)->format('d/m/Y H:i') : '—' }}</td>
                                            <td><strong>{{ $divisa }} {{ number_format((float) $h->total_venta, 2, '.', ',') }}</strong></td>
                                            <td>
                                                @if ($cancelada)
                                                    <span class="badge bg-danger">Cancelada</span>
                                                @else
                                                    <span class="badge bg-success">Emitida</span>
                                                @endif
                                            </td>
                                            <td class="acciones-col text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    {{-- 👁 Ver venta --}}
                                                    <a href="{{ route('admin.ventas.show', $h->venta_id) }}" class="btn btn-sm btn-info" title="Ver venta">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    @if (! $cancelada)
                                                        {{-- 🖨 Factura --}}
                                                        <button type="button" class="btn btn-sm btn-warning" title="Ver factura"
                                                            onclick="abrirVistaPrevia('{{ route('admin.ventas.imprimir', $h->venta_id) }}', 'factura')">
                                                            <i class="bi bi-printer"></i>
                                                        </button>
                                                        {{-- 🧾 Ticket --}}
                                                        <button type="button" class="btn btn-sm btn-warning" title="Ver ticket"
                                                            onclick="abrirVistaPrevia('{{ route('admin.ventas.ticket', $h->venta_id) }}', 'ticket')">
                                                            <i class="bi bi-receipt"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">No hay registros en el historial.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($historials->count() > 0)
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
                                <small class="text-muted">
                                    Mostrando {{ $historials->firstItem() }} a {{ $historials->lastItem() }} de {{ $historials->total() }} registros
                                </small>
                                <div>{{ $historials->links('vendor.pagination.bootstrap-5-no-summary') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- =====  MODAL VISTA PREVIA PDF (igual que en el listado) ===== --}}
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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
    });
</script>
@endpush