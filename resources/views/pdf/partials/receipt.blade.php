@php($allocations = $allocations ?? $receipt->paymentGroup())
<div class="amount-box"><div class="label">Amount received</div><strong>QAR {{ number_format($allocations->sum('amount_received'), 2) }}</strong></div>
<table class="meta"><tr><td>@include('pdf.partials.customer', ['customerLabel' => 'Received from'])</td></tr></table>
<table class="details">
    <tr><td class="detail-label">Payment method</td><td><strong>{{ $receipt->payment_method }}</strong></td></tr>
    @if($receipt->reference_number)<tr><td class="detail-label">Reference</td><td>{{ $receipt->reference_number }}</td></tr>@endif
    @if($receipt->notes)<tr><td class="detail-label">Notes</td><td>{{ $receipt->notes }}</td></tr>@endif
</table>
<div class="label" style="margin-top: 22px;">Payment allocation</div>
<table class="items">
    <thead><tr><th style="width: 50%; text-align: left;">Invoice / account</th><th class="right" style="width: 25%;">Allocated (QAR)</th><th class="right" style="width: 25%;">Current balance (QAR)</th></tr></thead>
    <tbody>
        @foreach($allocations as $allocation)
            <tr><td><strong>{{ $allocation->invoice?->invoice_number ?? 'Customer account credit' }}</strong></td><td class="right money">{{ number_format($allocation->amount_received, 2) }}</td><td class="right money">{{ $allocation->invoice ? number_format($allocation->invoice->balance_due, 2) : '-' }}</td></tr>
        @endforeach
    </tbody>
</table>
<div class="muted">Invoice balances shown are current balances at the time this document is generated.</div>
<table class="signatures"><tr><td><div class="label">Received by</div><div class="signature-line">Authorized signature / stamp</div></td></tr></table>
