@php
    $company = $company ?? config('company');
    $printPreview = $printPreview ?? false;
    $document = match ($documentType) {
        'quotation' => $quotation,
        'invoice' => $invoice,
        'delivery' => $deliveryNote,
        'receipt' => $receipt,
    };
    [$title, $number, $date] = match ($documentType) {
        'quotation' => ['Quotation', $quotation->quotation_number, $quotation->quotation_date],
        'invoice' => ['Sales Invoice', $invoice->invoice_number, $invoice->invoice_date],
        'delivery' => ['Delivery Note', $deliveryNote->delivery_number, $deliveryNote->dispatch_date],
        'receipt' => ['Payment Receipt', $receipt->receipt_number, $receipt->payment_date],
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} {{ $number }} - {{ $company['name'] }}</title>
    @include('pdf.partials.styles')
</head>
<body class="{{ $printPreview ? 'preview' : '' }}">
    @if($printPreview)
        <div class="print-controls no-print">
            <span>{{ $title }} / {{ $number }}</span>
            <div><button type="button" onclick="window.print()">Print document</button> <button type="button" onclick="window.close()">Close</button></div>
        </div>
    @endif
    <main class="sheet">
        @include('pdf.partials.header')
        @if(in_array($documentType, ['quotation', 'invoice']))
            @include('pdf.partials.sales')
        @elseif($documentType === 'delivery')
            @include('pdf.partials.delivery')
        @else
            @include('pdf.partials.receipt')
        @endif
        <footer class="footer">
            {{ $company['name'] }}
            @if($company['registration_number']) | C.R. {{ $company['registration_number'] }} @endif
            @if($company['website']) | {{ $company['website'] }} @endif
        </footer>
    </main>
</body>
</html>
