@extends('layouts.app')

@section('title', 'Generate Delivery Note')
@section('page_title', 'Create Delivery Note')

@section('header_action')
    <a href="{{ route('delivery.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">&larr; Back to Delivery Notes</a>
@endsection

@section('content')
<div class="ui-page pt-2 min-w-0">
    <form action="{{ route('delivery.store') }}" method="POST" class="ui-page min-w-0">
        @csrf
        <div class="ui-section bg-white rounded-xl border border-slate-200/90 shadow-xs">
            <div class="ui-form-back no-print">@yield('header_action')</div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 mb-4">
                <h2 class="ui-section-heading text-slate-900">Dispatch details</h2>
                <p class="ui-help text-slate-500 mt-1">Choose an invoice to load its remaining quantities.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="lg:col-span-2 min-w-0">
                    <label for="invoiceSelect" class="ui-field-label block text-slate-700 mb-1">Sales Invoice *</label>
                    <select name="invoice_id" id="invoiceSelect" required onchange="handleInvoiceChange()" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                        <option value="">-- Choose Sales Invoice --</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}" @selected(old('invoice_id', $selectedInvoice?->id) == $invoice->id)>{{ $invoice->invoice_number }} — {{ $invoice->customer?->name ?? 'Customer' }}</option>
                        @endforeach
                    </select>
                    @error('invoice_id')<p class="text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="deliveryNumber" class="ui-field-label block text-slate-700 mb-1">Delivery Note Number *</label>
                    <input id="deliveryNumber" type="text" name="delivery_number" value="{{ old('delivery_number', $suggestedNumber) }}" maxlength="50" required class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono-num bg-slate-50">
                    @error('delivery_number')<p class="text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="dispatchDate" class="ui-field-label block text-slate-700 mb-1">Dispatch Date *</label>
                    <input id="dispatchDate" type="date" name="dispatch_date" value="{{ old('dispatch_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label for="recipientName" class="ui-field-label block text-slate-700 mb-1">Recipient Name *</label>
                    <input type="text" name="recipient_name" id="recipientName" value="{{ old('recipient_name', $selectedInvoice?->customer?->name) }}" maxlength="255" required class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label for="recipientPhone" class="ui-field-label block text-slate-700 mb-1">Recipient Phone</label>
                    <input type="text" name="recipient_phone" id="recipientPhone" value="{{ old('recipient_phone', $selectedInvoice?->customer?->phone) }}" maxlength="50" placeholder="+974" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label for="driverName" class="ui-field-label block text-slate-700 mb-1">Driver / Courier / Vehicle</label>
                    <input id="driverName" type="text" name="driver_name" value="{{ old('driver_name') }}" maxlength="255" class="w-full px-3 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label for="deliveryStatus" class="ui-field-label block text-slate-700 mb-1">Dispatch Status *</label>
                    <select id="deliveryStatus" name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white">
                        @foreach(['Pending' => 'Pending Dispatch', 'Dispatched' => 'Out for Delivery', 'Delivered' => 'Delivered'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'Dispatched') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="ui-section bg-white rounded-xl border border-slate-200/90 shadow-xs space-y-4 min-w-0">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="ui-section-heading text-slate-900">Items to deliver</h3>
                <p class="ui-help text-slate-500 mt-1">Enter quantities for this dispatch; use zero for later deliveries. Pending and dispatched notes already reserve their quantities.</p>
            </div>
            <p id="deliveryReviewWarning" role="alert" class="hidden rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900">Historical delivery items do not match this invoice unambiguously. Review its existing delivery notes before creating another dispatch.</p>
            <p id="deliveryEmpty" class="ui-help text-slate-500 py-4">Select a sales invoice to load its items.</p>
            <div data-paginated-table-region>
                <div class="overflow-x-auto max-w-full">
                <table id="deliveryItemsTable" data-paginate-table data-pagination-label="delivery items" data-pagination-row-selector=".deliv-row" class="ui-table w-full min-w-[850px] text-left border border-slate-200">
                    <thead class="bg-slate-100 text-slate-700 border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2.5 min-w-52">Invoice Item</th>
                            @foreach(['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', 'Custom'] as $size)
                                <th class="px-1 py-2.5 text-center">{{ $size }}</th>
                            @endforeach
                            <th class="px-3 py-2.5 text-center min-w-28">Dispatch Qty</th>
                        </tr>
                    </thead>
                    <tbody id="deliveryTbody" class="divide-y divide-slate-200"></tbody>
                </table>
                </div>
            </div>
            @error('items')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
            <div>
                <label for="driverNotes" class="ui-field-label block text-slate-700 mb-1">Handover instructions</label>
                <textarea id="driverNotes" name="driver_notes" rows="3" placeholder="Delivery location, contact instructions or handover requirements" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg">{{ old('driver_notes') }}</textarea>
            </div>
            <div class="pt-3 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <div class="text-xs text-slate-600">Total Handover Quantity: <strong id="totalDelivPieces" class="text-slate-900 font-mono-num text-sm">0</strong></div>
                <button id="generateDeliveryButton" type="submit" disabled class="px-5 py-2.5 rounded-lg text-xs font-bold text-white bg-[#0a192f] hover:bg-[#132a4a] disabled:opacity-50 disabled:cursor-not-allowed">Generate Delivery Note</button>
            </div>
        </div>
    </form>
</div>

<template id="deliveryRowTemplate">
    <tr class="deliv-row">
        <td class="p-2">
            <input type="hidden" data-field="invoice_item_id">
            <span class="delivery-product font-semibold text-slate-900"></span>
            <p class="delivery-remaining text-[11px] text-slate-500 mt-1"></p>
        </td>
        @foreach(\App\Models\DeliveryItem::SIZE_FIELDS as $field)
            <td class="p-1 text-center"><input type="number" data-field="{{ $field }}" min="0" step="1" oninput="recalcDeliv()" class="size-matrix-input deliv-size disabled:bg-slate-100 disabled:text-slate-300"></td>
        @endforeach
        <td class="p-2 text-center bg-slate-50"><input type="number" data-field="total_qty" min="0" step="1" required oninput="recalcDeliv()" class="w-20 p-1.5 text-center border border-slate-300 rounded font-mono-num font-bold deliv-qty"><p class="delivery-unit text-[10px] text-slate-500 mt-1"></p></td>
    </tr>
</template>

@push('scripts')
<script>
    const deliveryInvoices = {{ \Illuminate\Support\Js::from($invoiceOptions) }};
    const previousDeliveryItems = {{ \Illuminate\Support\Js::from(old('items')) }};

    function renderDeliveryItems(previousItems = null) {
        const selected = deliveryInvoices[document.getElementById('invoiceSelect').value];
        const tbody = document.getElementById('deliveryTbody');
        tbody.replaceChildren();
        const availableItems = selected ? Object.values(selected.items).filter(item => item.total_qty > 0) : [];
        document.getElementById('deliveryReviewWarning').classList.toggle('hidden', !selected?.needs_review);
        const empty = document.getElementById('deliveryEmpty');
        empty.classList.toggle('hidden', availableItems.length > 0);
        empty.textContent = selected ? 'No remaining invoice quantities are available to dispatch.' : 'Select a sales invoice to load its items.';
        document.getElementById('generateDeliveryButton').disabled = !availableItems.length || selected?.needs_review;
        const previous = previousItems ? Object.values(previousItems) : [];

        availableItems.forEach((item, index) => {
            const row = document.getElementById('deliveryRowTemplate').content.firstElementChild.cloneNode(true);
            row.dataset.hasSizes = item.has_sizes ? '1' : '0';
            row.querySelector('.delivery-product').textContent = item.product_name;
            row.querySelector('.delivery-remaining').textContent = `Remaining: ${item.total_qty} ${item.pricing_unit}`;
            row.querySelector('.delivery-unit').textContent = item.pricing_unit;
            const restored = previous.find(oldItem => Number(oldItem.invoice_item_id) === Number(item.invoice_item_id));
            row.querySelectorAll('[data-field]').forEach(input => {
                const field = input.dataset.field;
                input.name = `items[${index}][${field}]`;
                input.value = field === 'invoice_item_id' ? item.invoice_item_id : (restored?.[field] ?? item[field] ?? 0);
                input.setAttribute('aria-label', `${item.product_name} ${field.replace('size_', '').replace('_', ' ')}`);
                if (field !== 'invoice_item_id') input.max = item[field] ?? 0;
                if (input.classList.contains('deliv-size')) input.disabled = !item.has_sizes;
                if (field === 'total_qty') input.readOnly = item.has_sizes;
            });
            tbody.appendChild(row);
        });
        recalcDeliv();
    }

    function handleInvoiceChange() {
        const selected = deliveryInvoices[document.getElementById('invoiceSelect').value];
        document.getElementById('recipientName').value = selected?.customer_name ?? '';
        document.getElementById('recipientPhone').value = selected?.customer_phone ?? '';
        renderDeliveryItems();
    }

    function readDeliveryQuantity(input) {
        const value = Number(input.value);
        const isInteger = Number.isSafeInteger(value);
        input.setCustomValidity(isInteger ? '' : 'Enter a whole-number quantity.');
        // Post canonical integer text for valid exponent, decimal and leading-zero entry.
        if (isInteger && input.value !== '') input.value = String(value);
        return isInteger ? Math.max(0, value) : 0;
    }

    function recalcDeliv() {
        let total = 0;
        document.querySelectorAll('.deliv-row').forEach(row => {
            const quantity = row.querySelector('.deliv-qty');
            if (row.dataset.hasSizes === '1') {
                quantity.value = Array.from(row.querySelectorAll('.deliv-size')).reduce((sum, input) => sum + readDeliveryQuantity(input), 0);
            }
            total += readDeliveryQuantity(quantity);
        });
        document.getElementById('totalDelivPieces').textContent = total;
    }

    document.addEventListener('DOMContentLoaded', () => renderDeliveryItems(previousDeliveryItems));
</script>
@endpush
@endsection
