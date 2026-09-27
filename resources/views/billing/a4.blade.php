<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document->fullNumber() }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; margin: 0; padding: 24px; }
        .header { width: 100%; border-bottom: 2px solid #111827; padding-bottom: 12px; }
        .header td { vertical-align: top; }
        .company-name { font-size: 16px; font-weight: bold; }
        .doc-box { border: 2px solid #111827; text-align: center; padding: 8px 12px; }
        .doc-box .ruc { font-size: 14px; font-weight: bold; }
        .doc-type { font-size: 13px; font-weight: bold; margin-top: 4px; }
        .doc-number { font-size: 12px; margin-top: 2px; }
        .section-title { font-weight: bold; background: #f3f4f6; padding: 4px 6px; margin: 14px 0 6px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { border-bottom: 1px solid #e5e7eb; padding: 5px 6px; text-align: left; }
        table.items th { background: #f9fafb; }
        .right { text-align: right; }
        .totals { width: 45%; margin-left: auto; margin-top: 8px; border-collapse: collapse; }
        .totals td { padding: 3px 6px; }
        .totals .grand td { border-top: 2px solid #111827; font-weight: bold; font-size: 13px; }
        .legend { margin-top: 16px; font-size: 10px; color: #4b5563; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">{{ $settings->trade_name ?: $settings->business_name }}</div>
                <div>{{ $settings->business_name }}</div>
                <div>{{ $settings->address }}</div>
                @if ($settings->phone)
                    <div>Tel: {{ $settings->phone }}</div>
                @endif
                @if ($settings->email)
                    <div>{{ $settings->email }}</div>
                @endif
            </td>
            <td style="width: 40%;">
                <div class="doc-box">
                    <div class="ruc">RUC {{ $settings->ruc }}</div>
                    <div class="doc-type">{{ strtoupper($document->type->label()) }}</div>
                    <div class="doc-number">{{ $document->fullNumber() }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Datos del comprobante</div>
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%;">
                <strong>Cliente:</strong> {{ $document->customer_name ?: 'CLIENTES VARIOS' }}<br>
                @if ($document->customer_doc_number)
                    <strong>{{ $document->customer_doc_type?->label() }}:</strong> {{ $document->customer_doc_number }}<br>
                @endif
                @if ($document->customer_address)
                    <strong>Dirección:</strong> {{ $document->customer_address }}<br>
                @endif
            </td>
            <td style="width: 50%;">
                <strong>Fecha de emisión:</strong> {{ $document->issue_date?->format('d/m/Y') }}<br>
                <strong>Moneda:</strong> {{ $document->currency }}<br>
                @if ($order)
                    <strong>Pedido:</strong> #{{ $order->number }}
                    @if ($order->diningTable)
                        · Mesa {{ $order->diningTable->name }}
                    @endif
                    <br>
                @endif
            </td>
        </tr>
    </table>

    <div class="section-title">Detalle</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 40%;">Descripción</th>
                <th class="right">Cant.</th>
                <th class="right">P. Unit.</th>
                <th class="right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach (($order?->items ?? collect())->where('status', '!=', \App\Enums\OrderItemStatus::Void) as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if ($item->modifiers->isNotEmpty())
                            <br><span style="color:#6b7280;font-size:9px;">{{ $item->modifiers->pluck('modifier_name')->implode(', ') }}</span>
                        @endif
                    </td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }}</td>
                    <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Op. gravadas</td>
            <td class="right">{{ number_format((float) $document->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>IGV (18%)</td>
            <td class="right">{{ number_format((float) $document->tax_total, 2) }}</td>
        </tr>
        @if ((float) $document->tip > 0)
            <tr>
                <td>Propina</td>
                <td class="right">{{ number_format((float) $document->tip, 2) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>Total</td>
            <td class="right">{{ $document->currency }} {{ number_format((float) $document->total, 2) }}</td>
        </tr>
    </table>

    <div class="legend">
        {{ \App\Support\NumberToWords::soles((float) $document->total) }}
    </div>

    @if ($document->sunat_description)
        <div class="legend">
            <strong>SUNAT:</strong> {{ $document->sunat_description }}
        </div>
    @endif

    <div class="footer">
        Documento generado por Restivo · {{ $document->fullNumber() }}
    </div>
</body>
</html>
