@extends('layouts.app')

@section('title', 'Create Sales Invoice')
@section('page_title', 'Create Sales Invoice')

@section('header_action')
    <a href="{{ route('invoices.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
        &larr; Back to Invoices
    </a>
@endsection

@section('content')
<div class="space-y-6 pt-2">

    <form id="invoiceForm" action="{{ route('invoices.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Top Header & Customer Selection Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <h2 class="font-bold text-sm text-slate-900">Direct Sales Invoice Header</h2>
                </div>
                <div class="text-xs text-slate-500">
                    Currency: <strong class="text-slate-900">QAR (Qatari Riyal)</strong> • Timezone: <strong class="text-slate-900">Asia/Qatar</strong>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <!-- Customer Selector + Quick Add -->
                <div class="lg:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-bold text-slate-700">Customer / Organization *</label>
                        <button type="button" onclick="openQuickCustomerModal()" class="text-[11px] font-bold text-[#0a192f] hover:text-[#d4af37] underline">
                            + Quick Add Customer
                        </button>
                    </div>
                    <select name="customer_id" id="customer_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                        <option value="">-- Select Customer / Client --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ (request('customer_id') == $c->id || old('customer_id') == $c->id) ? 'selected' : '' }}>
                                {{ $c->name }} {{ $c->company_name ? "({$c->company_name})" : '' }} [{{ $c->customer_code }}]
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Invoice Number -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Invoice Number *</label>
                    <input type="text" name="invoice_number" value="{{ old('invoice_number', $suggestedNumber) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none font-mono-num font-bold text-[#0a192f] bg-slate-50">
                </div>

                <!-- Invoice Date -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Invoice Date *</label>
                    <input type="date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>

                <!-- Payment Due Date -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Payment Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+15 days'))) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Product Line Items with Size Matrix Table -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="font-bold text-sm text-slate-900">Itemized Uniform Size Breakdown</h3>
                    <p class="text-xs text-slate-500">Enter sizes and quantities for this order. Production order will be automatically created on Kanban.</p>
                </div>

                <div class="flex items-center gap-2">
                    <select id="catalogPresetSelect" onchange="addPresetItem(this.value)" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 focus:outline-none bg-slate-50">
                        <option value="">+ Add from Catalog Preset...</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->name }}|{{ $prod->unit_price }}">{{ $prod->name }} (QAR {{ number_format($prod->unit_price, 2) }})</option>
                        @endforeach
                    </select>

                    <button type="button" onclick="addNewMatrixRow()" class="bg-[#0a192f] hover:bg-[#132a4a] text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Row</span>
                    </button>
                </div>
            </div>

            <!-- Size Matrix Grid Table Container -->
            <div class="overflow-x-auto">
                <table id="matrixTable" class="w-full text-left text-xs border border-slate-200 rounded-lg">
                    <thead class="bg-slate-100/90 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2.5 w-60">Uniform / Product Description</th>
                            <th class="px-1 py-2.5 text-center">XS</th>
                            <th class="px-1 py-2.5 text-center">S</th>
                            <th class="px-1 py-2.5 text-center">M</th>
                            <th class="px-1 py-2.5 text-center">L</th>
                            <th class="px-1 py-2.5 text-center">XL</th>
                            <th class="px-1 py-2.5 text-center">2XL</th>
                            <th class="px-1 py-2.5 text-center">3XL</th>
                            <th class="px-1 py-2.5 text-center">Custom</th>
                            <th class="px-2 py-2.5 text-center bg-slate-200/70 font-mono-num">Total Qty</th>
                            <th class="px-2 py-2.5 text-right">Unit Price</th>
                            <th class="px-2 py-2.5 text-right">Line Disc</th>
                            <th class="px-2 py-2.5 text-right">Setup/Emb</th>
                            <th class="px-2 py-2.5 text-right">Print/DTF</th>
                            <th class="px-3 py-2.5 text-right bg-slate-200/70 font-mono-num">Total (QAR)</th>
                            <th class="px-2 py-2.5 text-center w-8"></th>
                        </tr>
                    </thead>
                    <tbody id="matrixTbody" class="divide-y divide-slate-200 bg-white">
                        <tr class="matrix-row hover:bg-slate-50/60" data-row="0">
                            <td class="p-2">
                                <input type="text" name="items[0][product_name]" value="Oxford Button-Down Corporate Shirt" required placeholder="e.g. Polo Shirt / Trousers" class="w-full text-xs px-2.5 py-1.5 border border-slate-300 rounded font-semibold focus:ring-1 focus:ring-[#0a192f] focus:outline-none">
                                <input type="text" name="items[0][custom_size_notes]" placeholder="Custom size notes / measurements..." class="w-full text-[11px] text-slate-500 mt-1 px-2 py-1 border border-slate-200 rounded focus:outline-none">
                            </td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_xs]" value="5" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_s]" value="10" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_m]" value="20" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_l]" value="15" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_xl]" value="10" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_2xl]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_3xl]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_custom]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>

                            <td class="p-2 text-center bg-slate-50 font-bold font-mono-num text-sm text-[#0a192f]">
                                <span class="row-total-qty">60</span>
                                <input type="hidden" name="items[0][total_qty]" value="60" class="row-qty-hidden">
                            </td>

                            <td class="p-2">
                                <input type="number" step="0.01" min="0" name="items[0][unit_price]" value="85.00" oninput="calculateMatrix()" class="w-20 text-right font-mono-num font-bold text-xs p-1.5 border border-slate-300 rounded unit-price-input">
                            </td>
                            <td class="p-2">
                                <input type="number" step="0.01" min="0" name="items[0][line_discount]" value="0.00" oninput="calculateMatrix()" class="w-16 text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded line-discount-input">
                            </td>
                            <td class="p-2">
                                <input type="number" step="0.01" min="0" name="items[0][setup_charges]" value="0.00" oninput="calculateMatrix()" class="w-16 text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded setup-charges-input">
                            </td>
                            <td class="p-2">
                                <input type="number" step="0.01" min="0" name="items[0][print_charges]" value="0.00" oninput="calculateMatrix()" class="w-16 text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded print-charges-input">
                            </td>

                            <td class="p-2 text-right bg-slate-50 font-extrabold font-mono-num text-xs text-slate-900">
                                <span class="row-total-amt">5,100.00</span>
                            </td>

                            <td class="p-2 text-center">
                                <button type="button" onclick="removeMatrixRow(this)" class="text-slate-400 hover:text-rose-600 font-bold p-1">&times;</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notes and Financial Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-3">
                <label class="block text-xs font-bold text-slate-700">Invoice Terms & Notes</label>
                <textarea name="notes" rows="4" placeholder="e.g. Official tax invoice for uniform supply. Bank transfer to Qatar National Bank account." class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none"></textarea>
            </div>

            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-xs text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 mb-3">
                        Invoice Total Due (QAR)
                    </h3>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Total Units Across All Sizes:</span>
                            <span id="grandTotalUnits" class="font-mono-num font-bold text-slate-900 text-sm">60 pcs</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Subtotal:</span>
                            <span id="summarySubtotal" class="font-mono-num font-bold text-slate-900">QAR 5,100.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Overall Invoice Discount (QAR):</span>
                            <div class="w-32">
                                <input type="number" step="0.01" min="0" name="discount" id="overallDiscount" value="0.00" oninput="calculateMatrix()" class="w-full text-right font-mono-num font-bold text-xs px-2 py-1 border border-slate-300 rounded focus:ring-1 focus:ring-[#0a192f] focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-200">
                    <div class="bg-[#0a192f] text-white p-4 rounded-xl flex items-center justify-between shadow-md">
                        <div>
                            <span class="text-[10px] text-[#d4af37] font-bold uppercase tracking-wider block">Grand Total Invoiced</span>
                            <div class="text-xs text-slate-300">Balance due upon issue</div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-[#d4af37]">QAR</span>
                            <span id="grandTotalDisplay" class="text-2xl sm:text-3xl font-extrabold font-mono-num text-white">5,100.00</span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-3">
                        <button type="submit" class="px-5 py-2.5 rounded-lg text-xs font-extrabold text-slate-950 bg-[#d4af37] hover:bg-[#c59b27] shadow-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Generate Official Sales Invoice</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>

</div>

@push('scripts')
<script>
    let rowIndex = 1;

    function addNewMatrixRow(name = '', price = 85.00) {
        const tbody = document.getElementById('matrixTbody');
        const tr = document.createElement('tr');
        tr.className = 'matrix-row hover:bg-slate-50/60';
        tr.dataset.row = rowIndex;

        tr.innerHTML = `
            <td class="p-2">
                <input type="text" name="items[${rowIndex}][product_name]" value="${name}" required placeholder="Uniform or Product Name" class="w-full text-xs px-2.5 py-1.5 border border-slate-300 rounded font-semibold focus:ring-1 focus:ring-[#0a192f] focus:outline-none">
                <input type="text" name="items[${rowIndex}][custom_size_notes]" placeholder="Custom size notes / measurements..." class="w-full text-[11px] text-slate-500 mt-1 px-2 py-1 border border-slate-200 rounded focus:outline-none">
            </td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${rowIndex}][size_xs]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${rowIndex}][size_s]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${rowIndex}][size_m]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${rowIndex}][size_l]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${rowIndex}][size_xl]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${rowIndex}][size_2xl]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${rowIndex}][size_3xl]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
            <td class="p-1 text-center"><input type="number" min="0" name="items[${rowIndex}][size_custom]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>

            <td class="p-2 text-center bg-slate-50 font-bold font-mono-num text-sm text-[#0a192f]">
                <span class="row-total-qty">0</span>
                <input type="hidden" name="items[${rowIndex}][total_qty]" value="0" class="row-qty-hidden">
            </td>

            <td class="p-2">
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_price]" value="${parseFloat(price).toFixed(2)}" oninput="calculateMatrix()" class="w-20 text-right font-mono-num font-bold text-xs p-1.5 border border-slate-300 rounded unit-price-input">
            </td>
            <td class="p-2">
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][line_discount]" value="0.00" oninput="calculateMatrix()" class="w-16 text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded line-discount-input">
            </td>
            <td class="p-2">
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][setup_charges]" value="0.00" oninput="calculateMatrix()" class="w-16 text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded setup-charges-input">
            </td>
            <td class="p-2">
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][print_charges]" value="0.00" oninput="calculateMatrix()" class="w-16 text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded print-charges-input">
            </td>

            <td class="p-2 text-right bg-slate-50 font-extrabold font-mono-num text-xs text-slate-900">
                <span class="row-total-amt">0.00</span>
            </td>

            <td class="p-2 text-center">
                <button type="button" onclick="removeMatrixRow(this)" class="text-slate-400 hover:text-rose-600 font-bold p-1">&times;</button>
            </td>
        `;

        tbody.appendChild(tr);
        rowIndex++;
        calculateMatrix();
    }

    function addPresetItem(val) {
        if (!val) return;
        const [name, price] = val.split('|');
        addNewMatrixRow(name, price);
        document.getElementById('catalogPresetSelect').value = '';
    }

    function removeMatrixRow(btn) {
        const tbody = document.getElementById('matrixTbody');
        if (tbody.querySelectorAll('.matrix-row').length > 1) {
            btn.closest('tr').remove();
            calculateMatrix();
        } else {
            alert('An invoice must contain at least one line item.');
        }
    }

    function calculateMatrix() {
        let totalPieces = 0;
        let subtotalAmt = 0;

        document.querySelectorAll('.matrix-row').forEach(row => {
            let rowQty = 0;
            row.querySelectorAll('.size-input').forEach(input => {
                const val = parseInt(input.value) || 0;
                rowQty += val;
            });

            row.querySelector('.row-total-qty').innerText = rowQty;
            row.querySelector('.row-qty-hidden').value = rowQty;
            totalPieces += rowQty;

            const unitPrice = parseFloat(row.querySelector('.unit-price-input').value) || 0;
            const lineDisc = parseFloat(row.querySelector('.line-discount-input').value) || 0;
            const setup = parseFloat(row.querySelector('.setup-charges-input').value) || 0;
            const print = parseFloat(row.querySelector('.print-charges-input').value) || 0;

            const rowLineTotal = Math.max(0, (rowQty * unitPrice) - lineDisc + setup + print);
            row.querySelector('.row-total-amt').innerText = rowLineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            subtotalAmt += rowLineTotal;
        });

        const overallDisc = parseFloat(document.getElementById('overallDiscount').value) || 0;
        const grandTotal = Math.max(0, subtotalAmt - overallDisc);

        document.getElementById('grandTotalUnits').innerText = totalPieces + ' pcs';
        document.getElementById('summarySubtotal').innerText = 'QAR ' + subtotalAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('grandTotalDisplay').innerText = grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.addEventListener('DOMContentLoaded', () => {
        calculateMatrix();
    });
</script>
@endpush
@endsection
