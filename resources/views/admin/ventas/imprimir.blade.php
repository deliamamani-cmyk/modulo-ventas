@php
    // Fecha + HORA real de la venta (created_at guarda la hora)
    $fechaHoraVenta = $venta->created_at
        ? \Carbon\Carbon::parse($venta->created_at)->format('d/m/Y H:i')
        : ($venta->fecha_venta ? \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y H:i') : '—');

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
    <meta charset="utf-8">
    <title>Comprobante de Venta #{{ $venta->id }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1a1a1a; }
        body { margin: 24px; }
        .te-embedded { font-weight: 600; }

        .header { position: relative; min-height: 105px; border-bottom: 2px solid #2b3a67; padding: 0 115px 10px 0; margin-bottom: 14px; }
        .header-left h1 { margin: 0 0 4px 0; font-size: 17px; color: #2b3a67; text-transform: uppercase; }
        .header-left p { margin: 1px 0; font-size: 9.5px; color: #444; }
        .header-right { position: absolute; top: 0; right: 115px; text-align: right; }
        .header-right p { margin: 1px 0; font-size: 10px; }
        .qr-img { position: absolute; top: 0; right: 0; width: 100px; height: 100px; }

        .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info-grid td { width: 50%; vertical-align: top; border: 1px solid #cfcfcf; padding: 6px 8px; }
        .grupo h3 { margin: 0 0 4px 0; font-size: 10.5px; text-transform: uppercase; color: #2b3a67; border-bottom: 1px solid #cfcfcf; padding-bottom: 3px; }
        .grupo p { margin: 2px 0; font-size: 9.5px; }
        .label { font-weight: 600; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items th { background: #2b3a67; color: #ffffff; border: 1px solid #2b3a67; padding: 5px 6px; font-size: 9.5px; text-transform: uppercase; }
        table.items td { border: 1px solid #cfcfcf; padding: 5px 6px; font-size: 9.5px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .totals { width: 46%; margin-left: 54%; border-collapse: collapse; }
        .totals td { padding: 3px 8px; font-size: 10px; border-bottom: 1px solid #e0e0e0; }
        .totals tr.total-final td { font-size: 13px; font-weight: 700; border-top: 2px solid #2b3a67; border-bottom: none; color: #2b3a67; }
        .totals tr.son td { border-bottom: none; font-size: 9px; font-style: italic; color: #444; }

        .nota { margin: 10px 0; font-size: 9.5px; color: #444; }
        .firma { margin-top: 45px; width: 210px; border-top: 1px solid #1a1a1a; text-align: center; padding-top: 4px; font-size: 9.5px; }
        .footer { margin-top: 20px; border-top: 1px solid #cfcfcf; padding-top: 8px; text-align: center; font-size: 8.5px; color: #666; }
        .anulada { color: #c0392b; font-weight: 700; text-align: center; font-size: 13px; margin: 6px 0; }
    </style>
</head>
<body>
    {{-- ===== ENCABEZADO ===== --}}
    <div class="header">
        <div class="header-left">
            <h1>{{ $ajuste->nombre ?? $ajuste->nombre_empresa ?? 'Farmacia' }}</h1>
            @if (!empty($ajuste->direccion))
                <p>{{ $ajuste->direccion }}</p>
            @endif
            @if (!empty($ajuste->telefono))
                <p>Tel: {{ $ajuste->telefono }}</p>
            @endif
            @if (!empty($ajuste->email))
                <p>{{ $ajuste->email }}</p>
            @endif
            @if (!empty($ajuste->web))
                <p>{{ $ajuste->web }}</p>
            @endif
        </div>
        <div class="header-right">
            <p><strong>Venta N°:</strong> {{ $venta->nro_venta ?? $venta->id }}</p>
            {{--AHORA con hora real (ej: 26/08/2026 14:09) --}}
            <p>{{ $fechaHoraVenta }}</p>
            <p><strong>Atendió:</strong> {{ ucfirst($venta->usuario?->name ?? 'Sistema') }}</p>
        </div>
        <img src="{{ $qrDataUri }}" class="qr-img" alt="QR">
    </div>

    @if (in_array($venta->estado, ['anulada', 'cancelada']))
        <div class="anulada">*** VENTA CANCELADA ***</div>
    @endif

    {{-- ===== CLIENTE / SUCURSAL ===== --}}
    <table class="info-grid">
        <tr>
            <td>
                <div class="grupo">
                    <h3>Cliente</h3>
                    <p class="te-embedded">{{ $venta->cliente?->nombres_apellidos ?? $venta->cliente?->nombre ?? 'Sin cliente' }}</p>
                    @if (!empty($venta->cliente?->ci_nit))
                        <p><span class="label">CI/NIT:</span> {{ $venta->cliente->ci_nit }}</p>
                    @endif
                    @if (!empty($venta->cliente?->telefono))
                        <p><span class="label">Tel:</span> {{ $venta->cliente->telefono }}</p>
                    @endif
                    @if (!empty($venta->cliente?->email))
                        <p><span class="label">Email:</span> {{ $venta->cliente->email }}</p>
                    @endif
                </div>
            </td>
            <td>
                <div class="grupo">
                    <h3>Sucursal / Pago</h3>
                    <p><span class="label">Sucursal:</span> {{ $venta->sucursal?->nombre ?? '—' }}</p>
                    <p><span class="label">Tipo de pago:</span> {{ ucfirst(strtolower($venta->tipo_pago ?? '—')) }}</p>
                    <p><span class="label">Ítems:</span> {{ $detalles->count() }} · <span class="label">Cant. total:</span> {{ $totalCantidad }}</p>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== PRODUCTOS ===== --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 24px;">#</th>
                <th style="width: 80px;">Código</th>
                <th>Producto</th>
                <th style="width: 80px;">Lote</th>
                <th style="width: 40px;">Cant.</th>
                <th style="width: 70px;">P. Unit.</th>
                <th style="width: 80px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detalles as $i => $d)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $d->inventario?->producto?->codigo_producto ?? '—' }}</td>
                    <td>
                        {{ $d->inventario?->producto?->nombre_comercial ?? 'Producto' }}
                        <br><small>{{ $d->inventario?->producto?->nombre_generico ?? '' }}</small>
                    </td>
                    <td>{{ $d->inventario?->lote?->numero_lote ?? '—' }}</td>
                    <td class="text-center">{{ $d->cantidad }}</td>
                    <td class="text-right">{{ $ajuste->divisa ?? 'Bs.' }} {{ number_format((float) $d->precio_venta_unidad, 2, '.', ',') }}</td>
                    <td class="text-right">{{ $ajuste->divisa ?? 'Bs.' }} {{ number_format((float) $d->subtotal, 2, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===== TOTALES ===== --}}
    <table class="totals">
        <tr>
            <td>Ítems:</td>
            <td class="text-right">{{ $detalles->count() }}</td>
        </tr>
        <tr>
            <td>Cantidad total:</td>
            <td class="text-right">{{ $totalCantidad }}</td>
        </tr>
        <tr class="total-final">
            <td>TOTAL VENTA:</td>
            <td class="text-right">{{ $ajuste->divisa ?? 'Bs.' }} {{ number_format((float) $venta->total_venta, 2, '.', ',') }}</td>
        </tr>
        <tr class="son">
            <td colspan="2" class="text-right">Son: {{ numeroALetras($venta->total_venta) }} {{ $ajuste->divisa ?? 'Bs.' }}</td>
        </tr>
    </table>

    {{-- ===== NOTA ===== --}}
    @if (!empty($venta->nota))
        <p class="nota"><span class="label">Nota:</span> {{ $venta->nota }}</p>
    @endif
    <br>
    {{-- ===== FIRMA ===== --}}
    <div class="firma">Firma del cliente</div>

    {{-- ===== PIE ===== --}}
    <div class="footer">
        {{ $ajuste->nombre ?? $ajuste->nombre_empresa ?? 'Farmacia' }} — Comprobante generado el {{ now()->format('d/m/Y H:i') }}<br>
        Gracias por su compra. Conserve este comprobante como garantía de su compra.
    </div>
</body>
</html>