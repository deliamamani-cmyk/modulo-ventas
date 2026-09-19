<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        body { margin: 24px; }
        .center { text-align: center; }
        h1 { font-size: 14px; font-weight: bold; margin: 0 0 2px 0; }
        .sub { font-size: 10px; color: #333; margin-bottom: 3px; }
        .meta { font-size: 8px; color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #1a73e8; color: #ffffff; padding: 5px 6px; font-size: 8.5px; text-align: left; }
        tbody td { padding: 5px 6px; font-size: 8.5px; border-bottom: 1px solid #e3e3ec; }
        tbody tr:nth-child(even) td { background: #f4f4f8; }
        tfoot td { background: #e8e8ee; font-weight: bold; padding: 5px 6px; font-size: 8.5px; }
        .right { text-align: right; }
        .footer { margin-top: 20px; text-align: center; font-size: 7.5px; color: #888; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="center">
        <h1>{{ $empresa }}</h1>
        <div class="sub">{{ $titulo }}</div>
        <div class="meta">
            {{ now()->format('d/m/Y H:i') }} | Desde: {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} | Hasta: {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
        </div>
    </div>

    <table>
        @if ($modo === 'ventas')
            <thead>
                <tr>
                    <th style="width: 20px;">#</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Sucursal</th>
                    <th>Tipo Pago</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ventas as $i => $v)
                    <tr>
                        <td>{{ $v->id }}</td>
                        <td>
                            {{ $v->created_at
                                ? \Carbon\Carbon::parse($v->created_at)->format('d/m/Y H:i')
                                : ($v->fecha_venta ? \Carbon\Carbon::parse($v->fecha_venta)->format('d/m/Y') : '—') }}
                        </td>
                        <td>{{ $v->cliente?->nombres_apellidos ?? 'Sin cliente' }}</td>
                        <td>{{ $v->sucursal?->nombre ?? '—' }}</td>
                        <td>{{ ucfirst(strtolower($v->tipo_pago ?? '—')) }}</td>
                        <td class="right">{{ $divisa }} {{ number_format((float) $v->total_venta, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="center">Sin datos en el período.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="right">Total {{ $totalRegistros }} ventas</td>
                    <td class="right">{{ $divisa }} {{ number_format($totalBs, 2, '.', ',') }}</td>
                </tr>
            </tfoot>

        @elseif ($modo === 'grupos')
            {{-- ===== AGRUPADO  ===== --}}
            <thead>
                <tr>
                    <th style="width: 20px;">#</th>
                    <th>{{ $tipo === 'sucursal' ? 'Sucursal' : 'Tipo de pago' }}</th>
                    <th class="right">Ventas</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($grupos as $i => $g)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $g['nombre'] }}</td>
                        <td class="right">{{ $g['ventas'] }}</td>
                        <td class="right">{{ $divisa }} {{ number_format($g['total'], 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="center">Sin datos en el período.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="right">Totales:</td>
                    <td class="right">{{ $divisa }} {{ number_format($totalBs, 2, '.', ',') }}</td>
                </tr>
            </tfoot>

        @else
            <thead>
                <tr>
                    <th style="width: 20px;">#</th>
                    <th>Producto</th>
                    <th>Presentación</th>
                    <th>Laboratorio</th>
                    <th>Categoría</th>
                    <th class="right">Cantidad</th>
                    <th class="right">Total</th>
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
                        <td class="right">{{ $p['cant'] }}</td>
                        <td class="right">{{ $divisa }} {{ number_format($p['total'], 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="center">Sin datos en el período.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="right">Totales:</td>
                    <td class="right">{{ $divisa }} {{ number_format($totalBs, 2, '.', ',') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        {{ $empresa }} — Reporte generado {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>