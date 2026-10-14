@extends('layouts.app')

@section('title', 'Generate Delivery Note')
@section('page_title', 'Dispatch & Delivery Note Generator')

@section('header_action')
    <a href="{{ route('delivery.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
        &larr; Back to Delivery Notes
    </a>
@endsection

@section('content')
<div class="space-y-6 pt-2">

    <form action="{{ route('delivery.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Invoice Selection & Header Info -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-indigo-500"></span>
                    <h2 class="font-bold text-sm text-slate-900">Dispatch Manifest Header</h2>
                </div>
                <div class="text-xs text-slate-500">
                    Auto-populates items & size matrix from confirmed sales invoices.
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <!-- Invoice Selector -->
                <div class="lg:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">Select Sales Invoice to Dispatch *</label>
                    <select name="invoice_id" id="invoiceSelect" required onchange="handleInvoiceChange(this)" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                        <option value="">-- Choose Sales Invoice --</option>
                        @foreach($invoices as $inv)
                            <option value="{{ $inv->id }}" {{ (isset($selectedInvoice) && $selectedInvoice->id == $inv->id) ? 'selected' : '' }} data-customer="{{ $inv->customer->name ?? '' }}" data-phone="{{ $inv->customer->phone ?? '' }}">
                                {{ $inv->invoice_number }} — {{ $inv->customer->name ?? 'Walk-in' }} ({{ $inv->total_qty }} pcs • QAR {{ number_format($inv->grand_total, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Delivery Number -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Delivery Note # *</label>
                    <input type="text" name="delivery_number" value="{{ old('delivery_number', $suggestedNumber) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none font-mono-num font-bold text-[#0a192f] bg-slate-50">
                </div>

                <!-- Dispatch Date -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Dispatch Date *</label>
                    <input type="date" name="dispatch_date" value="{{ old('dispatch_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>

                <!-- Recipient Name -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Recipient Contact Person *</label>
                    <input type="text" name="recipient_name" id="recipientName" value="{{ $selectedInvoice->customer->name ?? '' }}" required placeholder="e.g. Mr. James Alistair" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>

                <!-- Recipient Phone -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Recipient Qatar Phone (+974)</label>
                    <input type="text" name="recipient_phone" id="recipientPhone" value="{{ $selectedInvoice->customer->phone ?? '' }}" placeholder="+974 5511 2233" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>

                <!-- Driver / Courier -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Delivery Driver / Vehicle</label>
                    <input type="text" name="driver_name" value="Rashid Khan (Van 04)" placeholder="Driver name or courier company" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>

                <!-- Initial Status -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Initial Dispatch Status *</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                        <option value="Dispatched" selected>Dispatched (Out for Delivery)</option>
                        <option value="Pending">Pending Dispatch (Packing Stage)</option>
                        <option value="Delivered">Delivered (Handover Complete)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Itemized Dispatch Size Breakdown Matrix -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-bold text-sm text-slate-900">Physical Items & Size Breakdown to Hand Over</h3>
                    <p class="text-xs text-slate-500">Quantities will be printed on the official handover voucher for receiver signature.</p>
                </div>
                <button type="button" onclick="addDeliveryRow()" class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800">
                    + Add Line Item
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="deliveryItemsTable" class="w-full text-left text-xs border border-slate-200 rounded-lg">
                    <thead class="bg-slate-100/90 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2.5 w-72">Uniform Item Description</th>
                            <th class="px-1 py-2.5 text-center">XS</th>
                            <th class="px-1 py-2.5 text-center">S</th>
                            <th class="px-1 py-2.5 text-center">M</th>
                            <th class="px-1 py-2.5 text-center">L</th>
                            <th class="px-1 py-2.5 text-center">XL</th>
                            <th class="px-1 py-2.5 text-center">2XL</th>
                            <th class="px-1 py-2.5 text-center">3XL</th>
                            <th class="px-1 py-2.5 text-center">Custom</th>
                            <th class="px-3 py-2.5 text-center bg-slate-200/60 font-mono-num font-bold">Total Dispatched</th>
                            <th class="px-2 py-2.5 text-center w-8"></th>
                        </tr>
                    </thead>
                    <tbody id="deliveryTbody" class="divide-y divide-slate-200 bg-white">
                        @if(isset($selectedInvoice) && $selectedInvoice->items->count() > 0)
                            @foreach($selectedInvoice->items as $idx => $it)
                                <tr class="deliv-row hover:bg-slate-50">
                                    <td class="p-2">
                                        <input type="text" name="items[{{ $idx }}][product_name]" value="{{ $it->product_name }}" required class="w-full text-xs p-1.5 border border-slate-300 rounded font-semibold focus:outline-none">
                                    </td>
                                    <td class="p-1 text-center"><input type="number" min="0" name="items[{{ $idx }}][size_xs]" value="{{ $it->size_xs }}" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                    <td class="p-1 text-center"><input type="number" min="0" name="items[{{ $idx }}][size_s]" value="{{ $it->size_s }}" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                    <td class="p-1 text-center"><input type="number" min="0" name="items[{{ $idx }}][size_m]" value="{{ $it->size_m }}" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                    <td class="p-1 text-center"><input type="number" min="0" name="items[{{ $idx }}][size_l]" value="{{ $it->size_l }}" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                    <td class="p-1 text-center"><input type="number" min="0" name="items[{{ $idx }}][size_xl]" value="{{ $it->size_xl }}" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                    <td class="p-1 text-center"><input type="number" min="0" name="items[{{ $idx }}][size_2xl]" value="{{ $it->size_2xl }}" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                    <td class="p-1 text-center"><input type="number" min="0" name="items[{{ $idx }}][size_3xl]" value="{{ $it->size_3xl }}" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                    <td class="p-1 text-center"><input type="number" min="0" name="items[{{ $idx }}][size_custom]" value="{{ $it->size_custom }}" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                    <td class="p-2 text-center bg-slate-50 font-bold font-mono-num text-sm text-[#0a192f]">
                                        <span class="row-deliv-qty">{{ $it->total_qty }}</span>
                                        <input type="hidden" name="items[{{ $idx }}][total_qty]" value="{{ $it->total_qty }}" class="deliv-qty-hidden">
                                    </td>
                                    <td class="p-2 text-center">
                                        <button type="button" onclick="this.closest('tr').remove(); recalcDeliv();" class="text-slate-400 hover:text-rose-600 font-bold">&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr class="deliv-row hover:bg-slate-50">
                                <td class="p-2">
                                    <input type="text" name="items[0][product_name]" value="Custom Pique Polo Shirts" required class="w-full text-xs p-1.5 border border-slate-300 rounded font-semibold focus:outline-none">
                                </td>
                                <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_xs]" value="5" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_s]" value="10" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_m]" value="15" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_l]" value="10" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_xl]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_2xl]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_3xl]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_custom]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
                                <td class="p-2 text-center bg-slate-50 font-bold font-mono-num text-sm text-[#0a192f]">
                                    <span class="row-deliv-qty">40</span>
                                    <input type="hidden" name="items[0][total_qty]" value="40" class="deliv-qty-hidden">
                                </td>
                                <td class="p-2 text-center">
                                    <button type="button" onclick="this.closest('tr').remove(); recalcDeliv();" class="text-slate-400 hover:text-rose-600 font-bold">&times;</button>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Driver & Courier Delivery Handover Instructions</label>
                <textarea name="driver_notes" rows="3" placeholder="e.g. Call client 30 mins before arrival. Deliver to gate 2 receiving bay. Obtain stamp and signature on physical delivery sheet." class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                <div class="text-xs text-slate-600 font-medium">
                    Total Handover Units: <strong id="totalDelivPieces" class="text-slate-900 font-mono-num text-sm">40 pcs</strong>
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-xs font-extrabold text-white bg-[#0a192f] hover:bg-[#132a4a] shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Generate Official Delivery Note</span>
                </button>
            </div>
        </div>

    </form>

</div>

@push('scripts')
<script>
    let dIndex = 10;

    function handleInvoiceChange(select) {
        const opt = select.options[select.selectedIndex];
        if (opt && opt.dataset.customer) {
            document.getElementById('recipientName').value = opt.dataset.customer;
            document.getElementById('recipientPhone').value = opt.dataset.phone || '';
        }
    }

    function addDeliveryRow() {
        const tbody = document.getElementById('deliveryTbody');
        const tr = document.createElement('tr');
        tr.className = 'deliv-row hover:bg-slate-50';
        tr.innerHTML = `
            <td class="p-2">
                <input type="text" name="items[${dIndex}][product_name]" value="Custom Uniform Item" required class="w-full text-xs p-1.5 border border-slate-300 rounded font-semibold focus:outline-none">
            </td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${dIndex}][size_xs]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${dIndex}][size_s]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${dIndex}][size_m]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${dIndex}][size_l]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${dIndex}][size_xl]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${dIndex}][size_2xl]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${dIndex}][size_3xl]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${dIndex}][size_custom]" value="0" oninput="recalcDeliv()" class="size-matrix-input deliv-size"></td>
            <td class="p-2 text-center bg-slate-50 font-bold font-mono-num text-sm text-[#0a192f]">
                <span class="row-deliv-qty">0</span>
                <input type="hidden" name="items[${dIndex}][total_qty]" value="0" class="deliv-qty-hidden">
            </td>
            <td class="p-2 text-center">
                <button type="button" onclick="this.closest('tr').remove(); recalcDeliv();" class="text-slate-400 hover:text-rose-600 font-bold">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);
        dIndex++;
        recalcDeliv();
    }

    function recalcDeliv() {
        let grandTotal = 0;
        document.querySelectorAll('.deliv-row').forEach(row => {
            let rowQty = 0;
            row.querySelectorAll('.deliv-size').forEach(input => {
                rowQty += parseInt(input.value) || 0;
            });
            row.querySelector('.row-deliv-qty').innerText = rowQty;
            row.querySelector('.deliv-qty-hidden').value = rowQty;
            grandTotal += rowQty;
        });
        document.getElementById('totalDelivPieces').innerText = grandTotal + ' pcs';
    }

    document.addEventListener('DOMContentLoaded', recalcDeliv);
</script>
@endpush
@endsection
