<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thermal Slip — {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            background: #fff;
            color: #000;
            font-size: 11px;
            line-height: 1.25;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
        .double-divider { border-bottom: 2px double #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th, td { padding: 3px 0; }
        .no-print {
            padding: 8px;
            background: #0a192f;
            color: #fff;
            text-align: center;
            font-family: sans-serif;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .btn-print {
            background: #d4af37;
            color: #000;
            font-weight: bold;
            padding: 4px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; width: 100%; }
            @page { margin: 0; size: 80mm auto; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Print 80mm Thermal Slip</button>
        <button onclick="window.close()" style="margin-left: 5px; background: #333; color: #fff; border: none; padding: 4px 8px; cursor: pointer;">Close</button>
    </div>

    <div class="text-center">
        <div class="bold" style="font-size: 14px;">QUALITIES UNIFORMS & GIFTS</div>
        <div>Salwa Road, Zone 56, Doha - Qatar</div>
        <div>Tel: +974 4455 1234 | C.R. 128492</div>
        <div class="double-divider"></div>
        <div class="bold">COUNTER BILLING RECEIPT</div>
        <div>INV: {{ $invoice->invoice_number }}</div>
        <div>{{ $invoice->invoice_date->format('d/m/Y') }} {{ now()->format('H:i') }}</div>
    </div>

    <div class="divider"></div>

    <div>
        <div><strong>Customer:</strong> {{ $invoice->customer->name }}</div>
        @if($invoice->customer->phone)
            <div><strong>Tel:</strong> {{ $invoice->customer->phone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr style="border-bottom: 1px solid #000;">
                <th style="text-align: left;">Item / Sizes</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Price</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $it)
                <tr>
                    <td colspan="4" class="bold">{{ $it->product_name }}</td>
                </tr>
                <tr style="font-size: 9px; color: #333;">
                    <td colspan="4">
                        [XS:{{ $it->size_xs }} S:{{ $it->size_s }} M:{{ $it->size_m }} L:{{ $it->size_l }} XL:{{ $it->size_xl }} 2X:{{ $it->size_2xl }} 3X:{{ $it->size_3xl }}]
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="text-center">{{ $it->total_qty }}</td>
                    <td class="text-right">{{ number_format($it->unit_price, 2) }}</td>
                    <td class="text-right bold">{{ number_format($it->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Total Items/Qty:</td>
            <td class="text-right bold">{{ $invoice->total_qty }} pcs</td>
        </tr>
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">QAR {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->discount > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">- QAR {{ number_format($invoice->discount, 2) }}</td>
            </tr>
        @endif
        <tr class="bold" style="font-size: 13px;">
            <td>GRAND TOTAL:</td>
            <td class="text-right">QAR {{ number_format($invoice->grand_total, 2) }}</td>
        </tr>
        <tr>
            <td>Amount Paid:</td>
            <td class="text-right bold">QAR {{ number_format($invoice->paid_amount, 2) }}</td>
        </tr>
        <tr class="bold" style="font-size: 13px;">
            <td>BALANCE DUE:</td>
            <td class="text-right">QAR {{ number_format($invoice->balance_due, 2) }}</td>
        </tr>
    </table>

    <div class="double-divider"></div>

    <div class="text-center" style="font-size: 9px;">
        *** THANK YOU FOR YOUR BUSINESS ***<br>
        Goods once altered or tailored cannot be returned.<br>
        Uniforms & Gifts Solutions • State of Qatar
    </div>

</body>
</html>
