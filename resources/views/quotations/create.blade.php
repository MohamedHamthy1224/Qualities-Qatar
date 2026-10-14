@extends('layouts.app')

@section('title', 'Quotation Builder & Dynamic Size Matrix')
@section('page_title', 'Quotation Builder & Size Matrix')

@section('header_action')
    <a href="{{ route('quotations.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
        &larr; Back to Quotations
    </a>
@endsection

@section('content')
<div class="space-y-6 pt-2">

    <form id="quotationForm" action="{{ route('quotations.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Top Header & Customer Selection Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#d4af37]"></span>
                    <h2 class="font-bold text-sm text-slate-900">Quotation Proposal Header</h2>
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

                <!-- Quotation Number -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Quotation Number *</label>
                    <input type="text" name="quotation_number" value="{{ old('quotation_number', $suggestedNumber) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none font-mono-num font-bold text-[#0a192f] bg-slate-50">
                </div>

                <!-- Product Category -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Product Category *</label>
                    <select name="category" id="quoteCategory" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                        <option value="School Uniforms">School Uniforms</option>
                        <option value="Corporate Uniforms">Corporate Uniforms</option>
                        <option value="Sportswear">Sportswear</option>
                        <option value="Embroidery">Computerized Embroidery</option>
                        <option value="DTF Printing">DTF Printing</option>
                        <option value="Promotional Gifts">Promotional Gifts</option>
                    </select>
                </div>

                <!-- Quotation Date -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Quotation Date *</label>
                    <input type="date" name="quotation_date" value="{{ old('quotation_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>

                <!-- Delivery Due Date -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Target Delivery Date</label>
                    <input type="date" name="delivery_date" value="{{ old('delivery_date', date('Y-m-d', strtotime('+7 days'))) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Product Line Items with Interactive Excel-like Size Matrix Table -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-sm text-slate-900">Dynamic Size Breakdown Matrix</h3>
                        <span class="text-[10px] bg-slate-100 text-slate-700 font-bold px-2 py-0.5 rounded border border-slate-200">
                            Interactive Real-time Grid
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">Enter quantities per size (XS to 3XL + Custom). Total quantity and line amounts compute automatically.</p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Quick catalog preset selector -->
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
                        <!-- Default Row 1 -->
                        <tr class="matrix-row hover:bg-slate-50/60" data-row="0">
                            <td class="p-2">
                                <input type="text" name="items[0][product_name]" value="Custom Pique Polo Shirt (Embroidery Ready)" required placeholder="e.g. Polo Shirt / Trousers" class="w-full text-xs px-2.5 py-1.5 border border-slate-300 rounded font-semibold focus:ring-1 focus:ring-[#0a192f] focus:outline-none">
                                <input type="text" name="items[0][custom_size_notes]" placeholder="Custom size notes / measurements..." class="w-full text-[11px] text-slate-500 mt-1 px-2 py-1 border border-slate-200 rounded focus:outline-none">
                            </td>
                            <!-- Matrix Size Inputs -->
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_xs]" value="10" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_s]" value="20" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_m]" value="30" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_l]" value="25" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_xl]" value="15" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_2xl]" value="5" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_3xl]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>
                            <td class="p-1 text-center"><input type="number" min="0" name="items[0][size_custom]" value="0" oninput="calculateMatrix()" class="size-matrix-input size-input"></td>

                            <!-- Row Total Quantity (Auto-Calculated) -->
                            <td class="p-2 text-center bg-slate-50 font-bold font-mono-num text-sm text-[#0a192f]">
                                <span class="row-total-qty">105</span>
                                <input type="hidden" name="items[0][total_qty]" value="105" class="row-qty-hidden">
                            </td>

                            <!-- Financial Inputs -->
                            <td class="p-2">
                                <input type="number" step="0.01" min="0" name="items[0][unit_price]" value="65.00" oninput="calculateMatrix()" class="w-20 text-right font-mono-num font-bold text-xs p-1.5 border border-slate-300 rounded unit-price-input">
                            </td>
                            <td class="p-2">
                                <input type="number" step="0.01" min="0" name="items[0][line_discount]" value="0.00" oninput="calculateMatrix()" class="w-16 text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded line-discount-input">
                            </td>
                            <td class="p-2">
                                <input type="number" step="0.01" min="0" name="items[0][setup_charges]" value="150.00" oninput="calculateMatrix()" class="w-16 text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded setup-charges-input">
                            </td>
                            <td class="p-2">
                                <input type="number" step="0.01" min="0" name="items[0][print_charges]" value="50.00" oninput="calculateMatrix()" class="w-16 text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded print-charges-input">
                            </td>

                            <!-- Row Total Amount -->
                            <td class="p-2 text-right bg-slate-50 font-extrabold font-mono-num text-xs text-slate-900">
                                <span class="row-total-amt">7,025.00</span>
                            </td>

                            <!-- Delete Row -->
                            <td class="p-2 text-center">
                                <button type="button" onclick="removeMatrixRow(this)" class="text-slate-400 hover:text-rose-600 font-bold p-1">&times;</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer Logo / Artwork Attachment (Drag-and-Drop WhatsApp & Facebook References) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <h3 class="font-bold text-xs text-slate-900 uppercase tracking-wider">Logo / Artwork Reference (WhatsApp / Social)</h3>
                    </div>
                    <span class="text-[10px] text-slate-400">PNG, JPG, PDF, SVG</span>
                </div>

                <div id="dropZone" class="border-2 border-dashed border-slate-300 hover:border-[#0a192f] rounded-xl p-5 text-center cursor-pointer transition-colors bg-slate-50/50">
                    <input type="file" name="artwork_file" id="artworkFileInput" accept="image/*,.pdf,.svg" class="hidden" onchange="handleArtworkPreview(this)">
                    <div id="dropZonePrompt" class="space-y-1">
                        <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-600 mx-auto flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <div class="text-xs font-bold text-slate-800">Drag & Drop Client Artwork / WhatsApp Logo here</div>
                        <div class="text-[11px] text-slate-500">or click to browse files from your computer</div>
                    </div>

                    <!-- Preview container -->
                    <div id="artworkPreviewBox" class="hidden flex items-center justify-center gap-3">
                        <img id="artworkPreviewImg" src="" alt="Artwork Preview" class="w-20 h-20 object-contain rounded-lg border border-slate-300 shadow-xs bg-white">
                        <div class="text-left text-xs">
                            <div id="artworkFileName" class="font-bold text-slate-800"></div>
                            <button type="button" onclick="clearArtworkFile()" class="text-[11px] text-rose-600 hover:underline font-semibold mt-1">Remove file</button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Production Notes & Client Specifications</label>
                    <textarea name="notes" rows="3" placeholder="e.g. Left chest gold embroidery crest, 8.5cm wide. Heat transfer sponsor logo on back in white. Deliver to Lusail site." class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none"></textarea>
                </div>
            </div>

            <!-- Financial Calculation Summary Card -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-xs text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 mb-3">
                        Quotation Financial Summary (QAR)
                    </h3>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Total Units Across All Sizes:</span>
                            <span id="grandTotalUnits" class="font-mono-num font-bold text-slate-900 text-sm">105 pcs</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Line Items Subtotal:</span>
                            <span id="summarySubtotal" class="font-mono-num font-bold text-slate-900">QAR 6,825.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Embroidery Setup / Screen Fees:</span>
                            <span id="summarySetup" class="font-mono-num font-bold text-slate-800">QAR 150.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Printing / Heat Transfer Charges:</span>
                            <span id="summaryPrint" class="font-mono-num font-bold text-slate-800">QAR 50.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Overall Proposal Discount (QAR):</span>
                            <div class="w-32">
                                <input type="number" step="0.01" min="0" name="discount" id="overallDiscount" value="0.00" oninput="calculateMatrix()" class="w-full text-right font-mono-num font-bold text-xs px-2 py-1 border border-slate-300 rounded focus:ring-1 focus:ring-[#0a192f] focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grand Total Box -->
                <div class="mt-6 pt-4 border-t border-slate-200">
                    <div class="bg-[#0a192f] text-white p-4 rounded-xl flex items-center justify-between shadow-md">
                        <div>
                            <span class="text-[10px] text-[#d4af37] font-bold uppercase tracking-wider block">Grand Total Due</span>
                            <div class="text-xs text-slate-300">All sizes & charges included</div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-[#d4af37]">QAR</span>
                            <span id="grandTotalDisplay" class="text-2xl sm:text-3xl font-extrabold font-mono-num text-white">7,025.00</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex items-center justify-end gap-3">
                        <button type="submit" name="action" value="draft" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300">
                            Save as Draft
                        </button>
                        <button type="submit" name="action" value="send" class="px-5 py-2 rounded-lg text-xs font-extrabold text-slate-950 bg-[#d4af37] hover:bg-[#c59b27] shadow-sm flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            <span>Save & Generate Proposal</span>
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

    function addNewMatrixRow(name = '', price = 65.00) {
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
            alert('A quotation must contain at least one product row.');
        }
    }

    function calculateMatrix() {
        let totalPieces = 0;
        let subtotalAmt = 0;
        let totalSetup = 0;
        let totalPrint = 0;

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

            totalSetup += setup;
            totalPrint += print;

            const rowLineTotal = Math.max(0, (rowQty * unitPrice) - lineDisc + setup + print);
            row.querySelector('.row-total-amt').innerText = rowLineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            subtotalAmt += rowLineTotal;
        });

        const overallDisc = parseFloat(document.getElementById('overallDiscount').value) || 0;
        const grandTotal = Math.max(0, subtotalAmt - overallDisc);

        document.getElementById('grandTotalUnits').innerText = totalPieces + ' pcs';
        document.getElementById('summarySubtotal').innerText = 'QAR ' + subtotalAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summarySetup').innerText = 'QAR ' + totalSetup.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('summaryPrint').innerText = 'QAR ' + totalPrint.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('grandTotalDisplay').innerText = grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Drag and drop file upload handling
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('artworkFileInput');

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-[#0a192f]', 'bg-slate-100');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-[#0a192f]', 'bg-slate-100');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-[#0a192f]', 'bg-slate-100');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleArtworkPreview(fileInput);
        }
    });

    function handleArtworkPreview(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('artworkFileName').innerText = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('artworkPreviewImg').src = e.target.result;
                    document.getElementById('dropZonePrompt').classList.add('hidden');
                    document.getElementById('artworkPreviewBox').classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('artworkPreviewImg').src = 'https://placehold.co/100x100?text=PDF/Doc';
                document.getElementById('dropZonePrompt').classList.add('hidden');
                document.getElementById('artworkPreviewBox').classList.remove('hidden');
            }
        }
    }

    function clearArtworkFile() {
        fileInput.value = '';
        document.getElementById('artworkPreviewBox').classList.add('hidden');
        document.getElementById('dropZonePrompt').classList.remove('hidden');
    }

    // Initialize calculation
    document.addEventListener('DOMContentLoaded', () => {
        calculateMatrix();
    });
</script>
@endpush
@endsection
