@php
    $company = $company ?? config('company');
    $document = $invoice;
    $documentType = 'invoice';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }} - {{ $company['name'] }}</title>
    <style>
        @page { size: 80mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        body { font: 11px/1.4 'Courier New', monospace; color: #111; width: 74mm; max-width: 100%; margin: 0 auto; padding: 3mm 0; overflow-wrap: anywhere; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { padding: 4px 0; vertical-align: top; }
        .center { text-align: center; } .right { text-align: right; } .strong, .item-name { font-weight: bold; }
        .logo { display: block; width: 140px; height: auto; margin: 0 auto 8px; }
        .divider { border-top: 1px dashed #333; margin: 9px 0; }
        .item-detail, .category { font-size: 10px; margin-top: 2px; }
        .item { border-bottom: 1px dotted #777; padding: 8px 0; break-inside: avoid; }
        .totals td:last-child { width: 46%; }
        .totals .grand, .totals .balance { font-weight: bold; border-top: 1px solid #333; }
        .notes { white-space: pre-line; margin-top: 10px; }
        .no-print { padding: 8px; background: #10243e; text-align: center; margin-bottom: 12px; }
        button { cursor: pointer; padding: 6px 9px; border: 0; background: #e6c260; color: #10243e; font-weight: bold; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print"><button type="button" onclick="window.print()">Print 80mm invoice</button> <button type="button" onclick="window.close()">Close</button></div>
    <div class="center">
        @if(!empty($company['logo_data_uri']))
            <img class="logo" src="{{ $company['logo_data_uri'] }}" alt="{{ $company['name'] }}" width="140" height="82">
        @else
            <strong>{{ $company['name'] }}</strong>
        @endif
        @if($company['address'])<div>{{ $company['address'] }}</div>@endif
        @if($company['phone'])<div>Tel: {{ $company['phone'] }}</div>@endif
        @if($company['email'])<div>{{ $company['email'] }}</div>@endif
        @if($company['registration_number'])<div>C.R. {{ $company['registration_number'] }}</div>@endif
        <div class="divider"></div>
        <strong>SALES INVOICE</strong><div>{{ $invoice->invoice_number }}</div>
        <div>{{ $invoice->invoice_date->format('d M Y') }}</div>
        <div>{{ $invoice->status }}</div>
    </div>
    <div class="divider"></div>
    <div><strong>Customer:</strong> {{ $invoice->customer->name }}</div>
    @if($invoice->customer->phone)<div>Tel: {{ $invoice->customer->phone }}</div>@endif
    @if($invoice->due_date)<div>Due: {{ $invoice->due_date->format('d M Y') }}</div>@endif
    @foreach($invoice->items as $item)
        <div class="item">
            @include('pdf.partials.item_details')
            <table><tr><td style="width: 60%;">{{ $item->total_qty }} {{ $item->pricing_unit ?: 'unit' }} x {{ number_format($item->unit_price, 2) }}</td><td class="right strong">{{ number_format($item->line_total, 2) }}</td></tr></table>
        </div>
    @endforeach
    <div class="divider"></div>
    @include('pdf.partials.totals')
    @if($invoice->receipts->isNotEmpty())
        <div class="divider"></div>
        <strong>Payments allocated to this invoice</strong>
        @foreach($invoice->receipts as $payment)
            <table><tr><td style="width: 63%;">{{ $payment->receipt_number }}<br>{{ $payment->payment_date->format('d M Y') }} / {{ $payment->payment_method }}</td><td class="right">QAR {{ number_format($payment->amount_received, 2) }}</td></tr></table>
        @endforeach
    @endif
    @if($invoice->notes)<div class="notes"><strong>Notes</strong><br>{{ $invoice->notes }}</div>@endif
    @if($company['invoice_terms'])<div class="notes"><strong>Terms</strong><br>{{ $company['invoice_terms'] }}</div>@endif
    <div class="divider"></div>
    <div class="center">Thank you for your business.<br>{{ $company['website'] }}<br>All amounts in QAR.</div>
</body>
</html>
