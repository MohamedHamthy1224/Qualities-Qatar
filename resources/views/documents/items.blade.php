<div class="overflow-x-auto">
<table id="documentItemsTable" data-paginate-table data-pagination-label="items" class="ui-table w-full min-w-[960px] text-left">
    <thead class="bg-slate-100 text-slate-700 border-b border-slate-200">
        <tr>
            <th class="p-3">Product / Service</th><th class="p-3">Quantity / Sizes</th>
            <th class="p-3 text-right">Unit Price</th><th class="p-3 text-right">Setup</th>
            <th class="p-3 text-right">Printing</th><th class="p-3 text-right">Discount</th>
            <th class="p-3 text-right">Line Total</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100">
        @foreach($items as $item)
            <tr>
                <td class="p-3 max-w-sm">
                    <div class="ui-table-primary text-slate-900 break-words">{{ $item->product_name }}</div>
                    <div class="ui-table-secondary text-slate-500 mt-0.5">{{ $item->category }}</div>
                    @if($item->custom_size_notes)<div class="ui-table-secondary text-slate-500 mt-0.5 break-words">{{ $item->custom_size_notes }}</div>@endif
                </td>
                <td class="p-3">
                    <div class="font-bold">{{ $item->total_qty }} {{ $item->pricing_unit ?: 'units' }}</div>
                    @if($item->input_mode === 'garment_matrix')<div class="ui-table-secondary text-slate-500 mt-0.5">{{ $item->size_breakdown_summary }}</div>@endif
                </td>
                <td class="p-3 text-right font-mono-num">{{ number_format($item->unit_price, 2) }}</td>
                <td class="p-3 text-right font-mono-num">{{ number_format($item->setup_charges, 2) }}</td>
                <td class="p-3 text-right font-mono-num">{{ number_format($item->print_charges, 2) }}</td>
                <td class="p-3 text-right font-mono-num">{{ number_format($item->line_discount, 2) }}</td>
                <td class="p-3 text-right font-bold font-mono-num bg-slate-50">{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>
