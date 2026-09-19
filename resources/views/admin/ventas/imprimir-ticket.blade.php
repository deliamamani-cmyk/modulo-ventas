@php
    // Fecha y HORA reales de la venta 
    $fechaVenta = $venta->created_at
        ? \Carbon\Carbon::parse($venta->created_at)->format('d/m/Y')
        : ($venta->fecha_venta ? \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') : '—');

    $horaVenta = $venta->created_at
        ? \Carbon\Carbon::parse($venta->created_at)->format('H:i')
        : '—';

    // Convierte número a letras (español)
    if (! function_exists('numeroALetras')) {
        function numeroALetras($numero)
        {
            $numero = (float) $numero;
            $enteros = (int) $numero;
            $centavos = (int) round(($numero - $enteros) * 100);
            return strtoupper(trim(_num2str($enteros))) . ' CON ' . str_pad($centavos, 2, '0', STR_PAD_LEFT) . '/100';
        }

        function _num2str($n)
        {
            $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciseis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidos', 'veintitres', 'veinticuatro', 'veinticinco', 'veintiseis', 'veintisiete', 'veintiocho', 'veintinueve'];
            $decenas = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
            $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

            if ($n == 0) return 'cero';
            if ($n >= 1000000) {
                $m = (int) ($n / 1000000); $r = $n % 1000000;
                return trim(($m == 1 ? 'un millon' : _num2str($m) . ' millones') . ($r ? ' ' . _num2str($r) : ''));
            }
            if ($n >= 1000) {
                $m = (int) ($n / 1000); $r = $n % 1000;
                return trim(($m == 1 ? 'mil' : _num2str($m) . ' mil') . ($r ? ' ' . _num2str($r) : ''));
            }
            if ($n == 100) return 'cien';
            if ($n >= 100) {
                $c = (int) ($n / 100); $r = $n % 100;
                return trim($centenas[$c] . ($r ? ' ' . _num2str($r) : ''));
            }
            if ($n >= 30) {
                $d = (int) ($n / 10); $u = $n % 10;
                return trim($decenas[$d] . ($u ? ' y ' . $unidades[$u] : ''));
            }
            return $unidades[$n];
        }
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Venta #{{ $venta->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #000;
            width: 72mm;
            padding: 8mm 5mm 3mm;
        }

        .center { text-align: center; }
        .titulo { font-size: 12pt; font-weight: bold; margin-bottom: 2mm; }
        .sub { font-size: 8pt; margin-bottom: 1mm; }
        .line { border-top: 1px dashed #000; margin: 2mm 0; }
        .line-solid { border-top: 1px solid #000; margin: 2mm 0; }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 2mm;
            margin-bottom: 1mm;
            font-size: 8.5pt;
        }
        .info-row .label { font-weight: bold; white-space: nowrap; }

        /* ===== TABLA ALINEADA ===== */
        table.detalles { width: 100%; border-collapse: collapse; }
        table.detalles th { font-size: 8pt; border-bottom: 1px solid #000; padding-bottom: 1mm; }
        table.detalles td { font-size: 8pt; padding: 1mm 0; vertical-align: top; }

        th.prod, td.prod { width: 40%; text-align: left; }
        th.cant, td.cant { width: 12%; text-align: center; }
        th.precio, td.precio { width: 22%; text-align: right; }
        th.subtotal, td.subtotal { width: 26%; text-align: right; }

        .total-row { display: flex; justify-content: space-between; font-size: 10pt; font-weight: bold; }
        .son { font-size: 7.5pt; font-style: italic; text-align: center; margin-top: 1mm; }
        .qr { text-align: center; margin-top: 3mm; }
    </style>
</head>
<body>
    {{-- ===== ENCABEZADO ===== --}}
    <div class="center">
        <div class="titulo">{{ $ajuste->nombre ?? $ajuste->nombre_empresa ?? 'FARMACIA' }}</div>
        @if (!empty($ajuste->direccion))
            <div class="sub">{{ $ajuste->direccion }}</div>
        @endif
        @if (!empty($ajuste->telefono))
            <div class="sub">Tel: {{ $ajuste->telefono }}</div>
        @endif
        @if (!empty($ajuste->email))
            <div class="sub">{{ $ajuste->email }}</div>
        @endif
        @if (!empty($ajuste->nit))
            <div class="sub">NIT: {{ $ajuste->nit }}</div>
        @endif
    </div>

    <div class="line"></div>

    <div class="center">
        <div class="sub" style="font-weight:bold;">TICKET DE VENTA #{{ $venta->nro_venta ?? $venta->id }}</div>
    </div>

    {{-- ===== DATOS (con HORA real) ===== --}}
    <div class="info-row"><span class="label">Fecha:</span><span>{{ $fechaVenta }}</span></div>
    <div class="info-row"><span class="label">Hora:</span><span>{{ $horaVenta }}</span></div>
    <div class="info-row"><span class="label">Sucursal:</span><span>{{ $venta->sucursal?->nombre ?? 'N/A' }}</span></div>
    <div class="info-row"><span class="label">Cliente:</span><span>{{ $venta->cliente?->nombres_apellidos ?? 'Sin cliente' }}</span></div>
    @if ($venta->cliente?->ci_nit)
        <div class="info-row"><span class="label">DOC:</span><span>{{ $venta->cliente->ci_nit }}</span></div>
    @endif
    <div class="info-row"><span class="label">Tipo pago:</span><span>{{ ucfirst($venta->tipo_pago ?? '—') }}</span></div>
    <div class="info-row"><span class="label">Usuario:</span><span>{{ $venta->usuario?->name ?? 'N/A' }}</span></div>

    @if (in_array($venta->estado, ['anulada', 'cancelada']))
        <div class="center" style="font-weight:bold; margin:2mm 0;">*** CANCELADA ***</div>
    @endif

    <div class="line"></div>

    {{-- ===== PRODUCTOS ===== --}}
    <table class="detalles">
        <thead>
            <tr>
                <th class="prod">Producto</th>
                <th class="cant">Cant</th>
                <th class="precio">Precio</th>
                <th class="subtotal">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detalles as $detalle)
                <tr>
                    <td class="prod">{{ $detalle->inventario?->producto?->nombre_comercial ?? 'N/A' }}</td>
                    <td class="cant">{{ $detalle->cantidad }}</td>
                    <td class="precio">
                        {{ $ajuste->divisa }} {{ number_format((float) $detalle->precio_venta_unidad, 2, '.', ',') }}
                    </td>
                    <td class="subtotal">
                        {{ $ajuste->divisa }} {{ number_format((float) $detalle->cantidad * (float) $detalle->precio_venta_unidad, 2, '.', ',') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;">Sin detalles</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="line-solid"></div>

    {{-- ===== TOTALES ===== --}}
    <div class="info-row"><span>Ítems:</span><span>{{ $detalles->count() }}</span></div>
    <div class="info-row"><span>Cantidad total:</span><span>{{ $totalCantidad }}</span></div>

    <div class="total-row" style="margin-top:2mm;">
        <span>TOTAL {{ $ajuste->divisa ?? 'Bs.' }}</span>
        <span>{{ number_format((float) $venta->total_venta, 2, '.', ',') }}</span>
    </div>

    {{-- TOTAL EN LITERAL --}}
    <div class="son">Son: {{ numeroALetras($venta->total_venta) }} {{ $ajuste->divisa ?? 'Bs.' }}</div>

    <div class="line"></div>

    {{-- ===== QR + FECHA/HORA DEBAJO ===== --}}
    <div class="qr">
        <img src="{{ $qrDataUri }}" style="width: 25mm; height: 25mm;" alt="QR">
        <div class="sub" style="font-weight:bold; margin-top:1mm;">{{ $fechaVenta }} {{ $horaVenta }}</div>
    </div>

    {{-- ===== PIE ===== --}}
    <div class="center" style="margin-top:3mm;">
        <div class="sub" style="font-weight:bold;">¡Gracias por su compra!</div>
        <div class="sub">Conserve este ticket como comprobante</div>
    </div>
</body>
</html>