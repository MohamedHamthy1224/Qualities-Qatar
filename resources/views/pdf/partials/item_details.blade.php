<div class="category">{{ $item->category }}</div>
<div class="item-name">{{ $item->product_name }}</div>
@if($item->input_mode === 'garment_matrix')
    <div class="item-detail">Sizes: {{ $item->size_breakdown_summary }}</div>
@endif
@if($item->custom_size_notes)<div class="item-detail">Specification: {{ $item->custom_size_notes }}</div>@endif
@if($item->setup_charges > 0)<div class="item-detail">Item setup: QAR {{ number_format($item->setup_charges, 2) }}</div>@endif
@if($item->print_charges > 0)<div class="item-detail">Item printing: QAR {{ number_format($item->print_charges, 2) }}</div>@endif
@if($item->line_discount > 0)<div class="item-detail">Item discount: - QAR {{ number_format($item->line_discount, 2) }}</div>@endif
