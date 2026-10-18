<table class="meta">
    <tr>
        <td style="width: 48%;">@include('pdf.partials.customer', ['customerLabel' => 'Deliver to'])</td>
        <td class="spacer"></td>
        <td style="width: 49%;">
            <div class="label">Dispatch details</div>
            <div>Invoice: <strong>{{ $deliveryNote->invoice->invoice_number }}</strong></div>
            <div>Recipient: <strong>{{ $deliveryNote->recipient_name }}</strong></div>
            @if($deliveryNote->recipient_phone)<div>Recipient tel: {{ $deliveryNote->recipient_phone }}</div>@endif
            @if($deliveryNote->driver_name)<div>Driver: {{ $deliveryNote->driver_name }}</div>@endif
            @if($deliveryNote->delivered_at)<div>Delivered: {{ $deliveryNote->delivered_at->format('d M Y H:i') }}</div>@endif
        </td>
    </tr>
</table>
<table class="items">
    <thead><tr><th style="width: 54%; text-align: left;">Item description</th><th style="width: 30%; text-align: left;">Sizes / specifications</th><th class="center" style="width: 16%;">Dispatched quantity</th></tr></thead>
    <tbody>
        @foreach($deliveryNote->items as $item)
            @php
                $sizes = collect(['XS' => $item->size_xs, 'S' => $item->size_s, 'M' => $item->size_m, 'L' => $item->size_l, 'XL' => $item->size_xl, '2XL' => $item->size_2xl, '3XL' => $item->size_3xl, 'Custom' => $item->size_custom])->filter(fn ($quantity) => $quantity > 0)->map(fn ($quantity, $size) => $size.': '.$quantity)->implode(', ');
            @endphp
            <tr>
                <td style="width: 54%;"><div class="item-name">{{ $item->product_name }}</div>@if($item->invoiceItem?->category)<div class="category">{{ $item->invoiceItem->category }}</div>@endif</td>
                <td style="width: 30%;">{{ $sizes ?: ($item->invoiceItem?->custom_size_notes ?: '-') }}</td>
                <td class="center" style="width: 16%;"><strong>{{ $item->total_qty }}</strong>@if($item->invoiceItem?->pricing_unit)<br>{{ $item->invoiceItem->pricing_unit }}@endif</td>
            </tr>
        @endforeach
    </tbody>
</table>
@if($deliveryNote->driver_notes)<div class="notes"><strong>Delivery notes</strong><br>{{ $deliveryNote->driver_notes }}</div>@endif
<table class="signatures">
    <tr>
        <td><div class="label">Dispatched by</div><div class="signature-line">Name / signature / date</div></td>
        <td class="spacer"></td>
        <td><div class="label">Received by</div><div class="signature-line">Name / signature / date</div></td>
    </tr>
</table>
