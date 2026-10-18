<table class="meta">
    <tr>
        <td style="width: 48%;">@include('pdf.partials.customer', ['customerLabel' => $documentType === 'quotation' ? 'Prepared for' : 'Bill to'])</td>
        <td class="spacer"></td>
        <td style="width: 49%;">
            <div class="label">Order details</div>
            @if($documentType === 'quotation')
                <div>Category: <strong>{{ $document->category }}</strong></div>
                <div>Target delivery: <strong>{{ $document->delivery_date?->format('d M Y') ?? 'Not specified' }}</strong></div>
            @else
                @if($document->quotation)<div>Quotation: <strong>{{ $document->quotation->quotation_number }}</strong></div>@endif
                <div>Payment due: <strong>{{ $document->due_date?->format('d M Y') ?? 'Not specified' }}</strong></div>
            @endif
            <div>Item lines: <strong>{{ $document->items->count() }}</strong></div>
            <div class="muted">Quantities and units are shown per item.</div>
        </td>
    </tr>
</table>
<table class="items">
    <thead><tr><th style="width: 50%; text-align: left;">Description / specifications</th><th class="center" style="width: 14%;">Quantity</th><th class="right" style="width: 17%;">Rate (QAR)</th><th class="right" style="width: 19%;">Line total (QAR)</th></tr></thead>
    <tbody>
        @foreach($document->items as $item)
            <tr>
                <td>@include('pdf.partials.item_details')</td>
                <td class="center"><strong>{{ $item->total_qty }}</strong><br><span class="muted">{{ $item->pricing_unit ?: 'unit' }}</span></td>
                <td class="right money">{{ number_format($item->unit_price, 2) }}</td>
                <td class="right money strong">{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="muted" style="font-size: 8px;">Line totals include any item setup, printing and item discount shown above. All amounts are in Qatari Riyals (QAR).</div>
@include('pdf.partials.totals')
@if($document->notes)<div class="notes"><strong>Notes</strong><br>{{ $document->notes }}</div>@endif
@if($terms = $company[$documentType.'_terms'])<div class="notes"><strong>Terms</strong><br>{{ $terms }}</div>@endif
