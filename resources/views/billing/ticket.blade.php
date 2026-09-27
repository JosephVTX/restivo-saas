<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document->fullNumber() }}</title>
    <style>
        * { font-family: DejaVu Sans Mono, monospace; }
        body { font-size: 10px; color: #000; margin: 0; padding: 6px; }
        .center { text-align: center; }
        .title { font-size: 13px; font-weight: bold; }
        .muted { color: #444; }
        hr { border: none; border-top: 1px dashed #000; margin: 5px 0; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { padding: 2px 0; text-align: left; vertical-align: top; }
        .right { text-align: right; }
        .total-line { font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="center">
        <div class="title">{{ $settings->trade_name ?: $settings->business_name }}</div>
        <div>{{ $settings->business_name }}</div>
        @if ($settings->ruc)
            <div>RUC {{ $settings->ruc }}</div>
        @endif
        <div>{{ $settings->address }}</div>
    </div>

    <hr>

    <div class="center">
        <strong>{{ strtoupper($document->type->label()) }}</strong><br>
        {{ $document->fullNumber() }}<br>
        {{ $document->issue_date?->format('d/m/Y') }}
    </div>

    <hr>

    <div>
        Cliente: {{ $document->customer_name ?: 'CLIENTES VARIOS' }}<br>
        @if ($document->customer_doc_number)
            {{ $document->customer_doc_type?->label() }}: {{ $document->customer_doc_number }}<br>
        @endif
        @if ($order)
            Pedido: #{{ $order->number }}
            @if ($order->diningTable)
                · Mesa {{ $order->diningTable->name }}
            @endif
            <br>
        @endif
    </div>

    <hr>

    <table class="items">
        @foreach (($order?->items ?? collect())->where('status', '!=', \App\Enums\OrderItemStatus::Void) as $item)
            <tr>
                <td colspan="2">
                    {{ $item->product_name }}
                    @if ($item->modifiers->isNotEmpty())
                        <br><span class="muted">{{ $item->modifiers->pluck('modifier_name')->implode(', ') }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }} x {{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="right">{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <hr>

    <table class="items">
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ number_format((float) $document->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>IGV</td>
            <td class="right">{{ number_format((float) $document->tax_total, 2) }}</td>
        </tr>
        @if ((float) $document->tip > 0)
            <tr>
                <td>Propina</td>
                <td class="right">{{ number_format((float) $document->tip, 2) }}</td>
            </tr>
        @endif
        <tr class="total-line">
            <td>TOTAL {{ $document->currency }}</td>
            <td class="right">{{ number_format((float) $document->total, 2) }}</td>
        </tr>
    </table>

    <hr>

    <div class="center muted">{{ \App\Support\NumberToWords::soles((float) $document->total) }}</div>

    <hr>

    <div class="center">¡Gracias por su visita!</div>
</body>
</html>
