<div class="totals-wrap">
    <table class="totals">
        <tr><td>Items subtotal</td><td class="right money">QAR {{ number_format($document->subtotal, 2) }}</td></tr>
        @if($document->setup_charges > 0)<tr><td>Document setup</td><td class="right money">QAR {{ number_format($document->setup_charges, 2) }}</td></tr>@endif
        @if($document->print_charges > 0)<tr><td>Document printing</td><td class="right money">QAR {{ number_format($document->print_charges, 2) }}</td></tr>@endif
        @if($document->discount > 0)<tr><td>Document discount</td><td class="right money">- QAR {{ number_format($document->discount, 2) }}</td></tr>@endif
        <tr class="grand"><td>Grand total</td><td class="right money">QAR {{ number_format($document->grand_total, 2) }}</td></tr>
        @if($documentType === 'invoice')
            <tr><td>Amount paid</td><td class="right money">QAR {{ number_format($document->paid_amount, 2) }}</td></tr>
            <tr class="balance"><td>Balance due</td><td class="right money">QAR {{ number_format($document->balance_due, 2) }}</td></tr>
        @endif
    </table>
</div>
