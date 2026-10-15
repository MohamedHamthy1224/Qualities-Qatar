@extends('layouts.app')

@section('title', 'Multi-Category Quotation Builder')
@section('page_title', 'Multi-Category Quotation Builder')

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
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-slate-100 pb-3 mb-4 gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#d4af37]"></span>
                    <h2 class="font-bold text-sm text-slate-900">Quotation Proposal Header</h2>
                    <span class="text-[11px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-semibold border border-slate-200">
                        Multi-Category Enabled
                    </span>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <div>Currency: <strong class="text-slate-900">QAR (Qatari Riyal)</strong></div>
                    <span>•</span>
                    <div>Timezone: <strong class="text-slate-900">Asia/Qatar</strong></div>
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

                <!-- Primary / Header Category (Backward Compatible) -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Quotation Category Classification</label>
                    <select name="category" id="headerCategory" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white font-medium">
                        <option value="Multi-Category" selected>Multi-Category (Mixed Items)</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
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

        <!-- Smart Product Catalog & Category Filter Selection Desk -->
        <div class="bg-gradient-to-r from-slate-900 via-[#0a192f] to-[#132a4a] text-white rounded-xl shadow-md p-5 border border-slate-800">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 pb-4 border-b border-slate-700/80">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#d4af37] animate-pulse"></span>
                        <h3 class="font-extrabold text-sm text-white tracking-wide uppercase">Smart Catalog & Item Selection Desk</h3>
                    </div>
                    <p class="text-xs text-slate-300 mt-0.5">Filter by category, select catalog presets, or add custom items across garments, embroidery, printing, and gifts.</p>
                </div>

                <!-- Multi-Category Demo Quick Loader -->
                <button type="button" onclick="loadMultiCategoryDemo()" class="bg-[#d4af37]/20 hover:bg-[#d4af37]/30 text-[#d4af37] border border-[#d4af37]/50 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5 self-start lg:self-auto">
                    <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>⚡ Load Multi-Category Demo (QT-2026-009)</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mt-4 text-xs">
                <!-- 1. Category Filter Selector -->
                <div class="md:col-span-4">
                    <label class="block font-bold text-slate-200 mb-1.5">
                        <span class="text-[#d4af37]">1.</span> Select a category to filter products:
                    </label>
                    <select id="categoryFilter" onchange="handleCategoryFilterChange(this.value)" class="w-full px-3 py-2.5 bg-slate-800/90 border border-slate-600 rounded-lg text-white font-semibold focus:ring-2 focus:ring-[#d4af37] focus:outline-none">
                        <option value="all">★ All Categories (Show All Active Products)</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Product Catalog Preset Selector -->
                <div class="md:col-span-5">
                    <label class="block font-bold text-slate-200 mb-1.5 flex items-center justify-between">
                        <span><span class="text-[#d4af37]">2.</span> Add a product or service from catalog:</span>
                        <span id="filteredCountBadge" class="text-[10px] text-slate-400 font-normal">({{ count($products) }} items available)</span>
                    </label>
                    <select id="catalogPresetSelect" onchange="handlePresetSelect(this)" class="w-full px-3 py-2.5 bg-slate-800/90 border border-slate-600 rounded-lg text-white font-semibold focus:ring-2 focus:ring-[#d4af37] focus:outline-none">
                        <option value="">Select a product or service...</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" data-category="{{ $prod->category }}">
                                {{ $prod->name }} — QAR {{ number_format($prod->unit_price, 2) }} / {{ $prod->pricing_unit }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Add Custom Item Button -->
                <div class="md:col-span-3 flex flex-col justify-end">
                    <button type="button" onclick="openAddCustomModal()" class="w-full bg-[#d4af37] hover:bg-[#c59b27] text-slate-950 px-4 py-2.5 rounded-lg text-xs font-extrabold shadow-md flex items-center justify-center gap-1.5 transition-all">
                        <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Add Custom Item</span>
                    </button>
                </div>
            </div>

            <!-- Empty category state message -->
            <div id="emptyCategoryNotice" class="hidden mt-3 p-3 bg-amber-950/40 border border-amber-800/60 rounded-lg text-amber-200 text-xs flex items-center justify-between">
                <span>No catalog products currently found in this category. You can add a custom item or add products to the catalog.</span>
                <button type="button" onclick="openAddCustomModal()" class="underline font-bold text-white ml-2">+ Add Custom Item</button>
            </div>
        </div>

        <!-- Product Line Items Table -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-sm text-slate-900">Quotation Line Items & Dynamic Quantity Breakdown</h3>
                        <span id="lineItemsCountBadge" class="text-[10px] bg-slate-100 text-slate-700 font-bold px-2 py-0.5 rounded border border-slate-200">
                            1 Item
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">Each item retains its own category, quantity mode (Garment Matrix, Flat Quantity, or Service), unit price, and line totals.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" onclick="openAddCustomModal()" class="text-xs text-[#0a192f] hover:text-[#d4af37] font-bold border border-slate-300 hover:border-slate-400 bg-slate-50 px-3 py-1.5 rounded-lg transition-colors">
                        + Add Custom Item
                    </button>
                </div>
            </div>

            <!-- Size Matrix & Item Table Container -->
            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table id="matrixTable" class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2.5 min-w-[220px]">Product Description & Category</th>
                            <th class="px-2 py-2.5 min-w-[340px]">Quantity Structure & Size Matrix Breakdown</th>
                            <th class="px-2 py-2.5 text-center bg-slate-200/70 font-mono-num w-20">Total Qty</th>
                            <th class="px-2 py-2.5 text-right w-24">Unit Price</th>
                            <th class="px-2 py-2.5 text-right w-20">Line Disc</th>
                            <th class="px-2 py-2.5 text-right w-20">Setup/Emb</th>
                            <th class="px-2 py-2.5 text-right w-20">Print/DTF</th>
                            <th class="px-3 py-2.5 text-right bg-slate-200/70 font-mono-num w-28">Total (QAR)</th>
                            <th class="px-2 py-2.5 text-center w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="matrixTbody" class="divide-y divide-slate-200 bg-white">
                        <!-- Default Initial Row (Custom Pique Polo Shirt) -->
                        <tr class="matrix-row hover:bg-slate-50/60" data-row="0" data-mode="garment_matrix">
                            <td class="p-3 align-top">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <span class="category-badge text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider bg-sky-100 text-sky-800 border border-sky-300">
                                        School Uniforms
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">Garment Matrix</span>
                                </div>
                                <input type="hidden" name="items[0][product_id]" value="1" class="item-product-id">
                                <input type="hidden" name="items[0][category]" value="School Uniforms" class="item-category">
                                <input type="hidden" name="items[0][input_mode]" value="garment_matrix" class="item-input-mode">
                                <input type="hidden" name="items[0][pricing_unit]" value="piece" class="item-pricing-unit">

                                <input type="text" name="items[0][product_name]" value="Custom Pique Polo Shirt" required placeholder="Product Description" class="w-full text-xs px-2.5 py-1.5 border border-slate-300 rounded font-bold text-slate-900 focus:ring-1 focus:ring-[#0a192f] focus:outline-none">
                                <input type="text" name="items[0][custom_size_notes]" placeholder="Custom sizing notes / specifications..." class="w-full text-[11px] text-slate-500 mt-1 px-2 py-1 border border-slate-200 rounded focus:outline-none">
                            </td>

                            <!-- Quantity Structure Column -->
                            <td class="p-3 align-top">
                                <!-- Garment Matrix Size Inputs -->
                                <div class="size-matrix-container">
                                    <div class="grid grid-cols-8 gap-1 text-center font-mono-num">
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">XS</span>
                                            <input type="number" min="0" name="items[0][size_xs]" value="10" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">S</span>
                                            <input type="number" min="0" name="items[0][size_s]" value="20" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">M</span>
                                            <input type="number" min="0" name="items[0][size_m]" value="30" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">L</span>
                                            <input type="number" min="0" name="items[0][size_l]" value="25" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">XL</span>
                                            <input type="number" min="0" name="items[0][size_xl]" value="15" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">2XL</span>
                                            <input type="number" min="0" name="items[0][size_2xl]" value="0" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">3XL</span>
                                            <input type="number" min="0" name="items[0][size_3xl]" value="0" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                                        </div>
                                        <div>
                                            <span class="text-[10px] font-bold text-purple-700 block mb-0.5">Cust</span>
                                            <input type="number" min="0" name="items[0][size_custom]" value="0" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-purple-300 bg-purple-50/50 rounded size-input">
                                        </div>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1 flex items-center justify-between">
                                        <span>Sizes XS to 3XL + Custom</span>
                                        <span class="font-mono-num font-bold text-slate-700">Sum = <span class="matrix-sum-preview">100</span> pcs</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Row Total Quantity (Auto-Calculated) -->
                            <td class="p-3 text-center bg-slate-50 font-bold font-mono-num text-sm text-[#0a192f] align-middle">
                                <span class="row-total-qty">100</span>
                                <input type="hidden" name="items[0][total_qty]" value="100" class="row-qty-hidden">
                            </td>

                            <!-- Financial Inputs -->
                            <td class="p-3 align-middle">
                                <input type="number" step="0.01" min="0" name="items[0][unit_price]" value="65.00" oninput="calculateMatrix()" class="w-full text-right font-mono-num font-bold text-xs p-1.5 border border-slate-300 rounded unit-price-input">
                            </td>
                            <td class="p-3 align-middle">
                                <input type="number" step="0.01" min="0" name="items[0][line_discount]" value="0.00" oninput="calculateMatrix()" class="w-full text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded line-discount-input">
                            </td>
                            <td class="p-3 align-middle">
                                <input type="number" step="0.01" min="0" name="items[0][setup_charges]" value="0.00" oninput="calculateMatrix()" class="w-full text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded setup-charges-input">
                            </td>
                            <td class="p-3 align-middle">
                                <input type="number" step="0.01" min="0" name="items[0][print_charges]" value="0.00" oninput="calculateMatrix()" class="w-full text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded print-charges-input">
                            </td>

                            <!-- Row Total Amount -->
                            <td class="p-3 text-right bg-slate-50 font-extrabold font-mono-num text-xs text-slate-900 align-middle">
                                <span class="row-total-amt">6,500.00</span>
                            </td>

                            <!-- Delete Row -->
                            <td class="p-3 text-center align-middle">
                                <button type="button" onclick="removeMatrixRow(this)" title="Remove item" class="text-slate-400 hover:text-rose-600 font-bold text-lg p-1 transition-colors">&times;</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer Logo / Artwork Attachment & Financial Calculation Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left: Artwork & Notes -->
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
                    <textarea name="notes" rows="3" placeholder="e.g. Multi-category order for school uniforms, logo embroidery on polo shirts, DTF back printing, and VIP gift sets for executives." class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none"></textarea>
                </div>
            </div>

            <!-- Right: Financial Calculation Summary Card -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 flex flex-col justify-between">
                <div>
                    <h3 class="font-bold text-xs text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 mb-3">
                        Quotation Financial Summary (QAR)
                    </h3>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Total Volume Across All Items & Sizes:</span>
                            <span id="grandTotalUnits" class="font-mono-num font-bold text-slate-900 text-sm">100 units</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Line Items Subtotal:</span>
                            <span id="summarySubtotal" class="font-mono-num font-bold text-slate-900">QAR 6,500.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Embroidery Setup / Screen Fees:</span>
                            <span id="summarySetup" class="font-mono-num font-bold text-slate-800">QAR 0.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Printing / Heat Transfer Charges:</span>
                            <span id="summaryPrint" class="font-mono-num font-bold text-slate-800">QAR 0.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Proposal-Level Discount (QAR):</span>
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
                            <div class="text-xs text-slate-300">All categories & charges included</div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-[#d4af37]">QAR</span>
                            <span id="grandTotalDisplay" class="text-2xl sm:text-3xl font-extrabold font-mono-num text-white">6,500.00</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex items-center justify-end gap-3">
                        <button type="submit" name="action" value="draft" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-colors">
                            Save as Draft
                        </button>
                        <button type="submit" name="action" value="send" class="px-5 py-2 rounded-lg text-xs font-extrabold text-slate-950 bg-[#d4af37] hover:bg-[#c59b27] shadow-sm flex items-center gap-1.5 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            <span>Save & Generate Proposal</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>

</div>

<!-- Modal: Add Custom Item (Unlisted Product / Custom Specification) -->
<div id="customItemModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#0a192f] text-[#d4af37] flex items-center justify-center font-bold text-sm">+</div>
                <h3 class="font-bold text-sm text-slate-900">Add Custom Product or Service</h3>
            </div>
            <button type="button" onclick="closeAddCustomModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <div class="space-y-3 text-xs">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Product or Service Description *</label>
                <input type="text" id="customModalName" placeholder="e.g. Custom Sublimated Team Banner / VIP Gift Pen" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-1 focus:ring-[#0a192f] focus:outline-none font-semibold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Product Category *</label>
                    <select id="customModalCategory" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Input / Quantity Mode *</label>
                    <select id="customModalMode" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none">
                        <option value="garment_matrix">Garment Size Matrix (XS-3XL)</option>
                        <option value="standard_qty">Standard Quantity (Flat Units)</option>
                        <option value="service_qty">Service Quantity (Services / Designs)</option>
                        <option value="custom">Configurable Custom Spec</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Default Unit Price (QAR) *</label>
                    <input type="number" step="0.01" min="0" id="customModalPrice" value="50.00" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none font-mono-num font-bold">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Pricing Unit</label>
                    <select id="customModalUnit" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none">
                        <option value="piece">piece</option>
                        <option value="set">set</option>
                        <option value="service">service</option>
                        <option value="design">design</option>
                        <option value="pair">pair</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Setup / Screen Fee (QAR)</label>
                    <input type="number" step="0.01" min="0" id="customModalSetup" value="0.00" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none font-mono-num">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Print / DTF Charge (QAR)</label>
                    <input type="number" step="0.01" min="0" id="customModalPrint" value="0.00" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none font-mono-num">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Specification / Sizing Notes</label>
                <input type="text" id="customModalNotes" placeholder="e.g. Dimensions 120cm x 60cm, gold embroidery thread" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none text-[11px]">
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" onclick="closeAddCustomModal()" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
            <button type="button" onclick="submitCustomModalItem()" class="px-4 py-1.5 rounded-lg text-xs font-extrabold bg-[#0a192f] text-white hover:bg-[#132a4a]">
                Insert Item to Quotation
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Active products passed from server for zero-latency instant category filtering
    const catalogProducts = @json($products);
    let rowIndex = 1;

    // Category styling map
    const categoryStyles = {
        'School Uniforms': 'bg-sky-100 text-sky-800 border-sky-300',
        'Corporate Uniforms': 'bg-indigo-100 text-indigo-800 border-indigo-300',
        'Sportswear': 'bg-amber-100 text-amber-800 border-amber-300',
        'Computerized Embroidery': 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'DTF Printing': 'bg-cyan-100 text-cyan-800 border-cyan-300',
        'Promotional Gifts': 'bg-purple-100 text-purple-800 border-purple-300',
    };

    /**
     * 1. Category Filter Change: Filters the preset dropdown WITHOUT touching existing line items.
     */
    function handleCategoryFilterChange(selectedCategory) {
        const select = document.getElementById('catalogPresetSelect');
        const emptyNotice = document.getElementById('emptyCategoryNotice');
        const countBadge = document.getElementById('filteredCountBadge');

        // Reset dropdown
        select.innerHTML = '<option value="">Select a product or service...</option>';

        let matchingProducts = [];
        if (selectedCategory === 'all' || selectedCategory === 'All Categories') {
            matchingProducts = catalogProducts;
        } else {
            matchingProducts = catalogProducts.filter(p => p.category === selectedCategory);
        }

        countBadge.textContent = `(${matchingProducts.length} items available)`;

        if (matchingProducts.length === 0) {
            emptyNotice.classList.remove('hidden');
        } else {
            emptyNotice.classList.add('hidden');
            matchingProducts.forEach(prod => {
                const opt = document.createElement('option');
                opt.value = prod.id;
                opt.dataset.category = prod.category;
                opt.textContent = `${prod.name} — QAR ${parseFloat(prod.unit_price).toFixed(2)} / ${prod.pricing_unit || 'piece'}`;
                select.appendChild(opt);
            });
        }
    }

    /**
     * 2. Preset Select: Adds product as a new line item and clears selector immediately.
     */
    function handlePresetSelect(selectElement) {
        const prodId = selectElement.value;
        if (!prodId) return;

        const product = catalogProducts.find(p => p.id == prodId);
        if (!product) return;

        // Add line item based on product attributes
        addLineItemRow({
            product_id: product.id,
            product_name: product.name,
            category: product.category,
            input_mode: product.input_mode || 'garment_matrix',
            pricing_unit: product.pricing_unit || 'piece',
            unit_price: parseFloat(product.unit_price) || 0,
            setup_charges: parseFloat(product.default_setup_charges) || 0,
            print_charges: parseFloat(product.default_print_charges) || 0,
            line_discount: 0,
            total_qty: product.input_mode === 'garment_matrix' ? 50 : 20,
            // default matrix sizes if garment
            size_xs: product.input_mode === 'garment_matrix' ? 5 : 0,
            size_s: product.input_mode === 'garment_matrix' ? 10 : 0,
            size_m: product.input_mode === 'garment_matrix' ? 15 : 0,
            size_l: product.input_mode === 'garment_matrix' ? 15 : 0,
            size_xl: product.input_mode === 'garment_matrix' ? 5 : 0,
            size_2xl: 0,
            size_3xl: 0,
            size_custom: 0,
            custom_size_notes: ''
        });

        // Reset dropdown to placeholder so another item can be added immediately
        selectElement.value = '';
    }

    /**
     * 3. Core Line Item Factory: Appends a responsive row with product-specific input mode.
     */
    function addLineItemRow(data) {
        const tbody = document.getElementById('matrixTbody');
        const tr = document.createElement('tr');
        tr.className = 'matrix-row hover:bg-slate-50/60';
        tr.dataset.row = rowIndex;
        tr.dataset.mode = data.input_mode || 'garment_matrix';

        const catClass = categoryStyles[data.category] || 'bg-slate-100 text-slate-800 border-slate-300';
        const modeLabel = data.input_mode === 'garment_matrix' ? 'Garment Matrix'
            : (data.input_mode === 'service_qty' ? 'Service Qty' : 'Flat Quantity');

        let quantityCellHtml = '';

        if (data.input_mode === 'garment_matrix') {
            // Mode A: Garment size matrix
            quantityCellHtml = `
                <div class="size-matrix-container">
                    <div class="grid grid-cols-8 gap-1 text-center font-mono-num">
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">XS</span>
                            <input type="number" min="0" name="items[${rowIndex}][size_xs]" value="${data.size_xs || 0}" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">S</span>
                            <input type="number" min="0" name="items[${rowIndex}][size_s]" value="${data.size_s || 0}" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">M</span>
                            <input type="number" min="0" name="items[${rowIndex}][size_m]" value="${data.size_m || 0}" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">L</span>
                            <input type="number" min="0" name="items[${rowIndex}][size_l]" value="${data.size_l || 0}" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">XL</span>
                            <input type="number" min="0" name="items[${rowIndex}][size_xl]" value="${data.size_xl || 0}" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">2XL</span>
                            <input type="number" min="0" name="items[${rowIndex}][size_2xl]" value="${data.size_2xl || 0}" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-500 block mb-0.5">3XL</span>
                            <input type="number" min="0" name="items[${rowIndex}][size_3xl]" value="${data.size_3xl || 0}" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-slate-300 rounded size-input">
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-purple-700 block mb-0.5">Cust</span>
                            <input type="number" min="0" name="items[${rowIndex}][size_custom]" value="${data.size_custom || 0}" oninput="calculateMatrix()" class="w-full text-center text-xs p-1 border border-purple-300 bg-purple-50/50 rounded size-input">
                        </div>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-1 flex items-center justify-between">
                        <span>Sizes XS to 3XL + Custom</span>
                        <span class="font-mono-num font-bold text-slate-700">Sum = <span class="matrix-sum-preview">${data.total_qty || 0}</span> pcs</span>
                    </div>
                </div>
            `;
        } else {
            // Mode B / C: Standard or Service Quantity (clean, no irrelevant garment fields)
            const unitLabel = data.pricing_unit || (data.input_mode === 'service_qty' ? 'services' : 'units');
            quantityCellHtml = `
                <div class="py-1">
                    <div class="flex items-center gap-3">
                        <div class="w-36">
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Quantity (${unitLabel}):</label>
                            <input type="number" min="1" name="items[${rowIndex}][total_qty]" value="${data.total_qty || 1}" oninput="calculateMatrix()" class="w-full text-center text-sm font-bold font-mono-num p-1.5 border border-slate-300 rounded focus:ring-1 focus:ring-[#0a192f] focus:outline-none standard-qty-input">
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5 pt-3">
                            <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-700 font-semibold border border-slate-200">
                                Pricing per ${unitLabel}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        }

        tr.innerHTML = `
            <td class="p-3 align-top">
                <div class="flex items-center gap-1.5 mb-1.5">
                    <span class="category-badge text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider ${catClass}">
                        ${data.category}
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">${modeLabel}</span>
                </div>
                <input type="hidden" name="items[${rowIndex}][product_id]" value="${data.product_id || ''}" class="item-product-id">
                <input type="hidden" name="items[${rowIndex}][category]" value="${data.category}" class="item-category">
                <input type="hidden" name="items[${rowIndex}][input_mode]" value="${data.input_mode}" class="item-input-mode">
                <input type="hidden" name="items[${rowIndex}][pricing_unit]" value="${data.pricing_unit}" class="item-pricing-unit">

                <input type="text" name="items[${rowIndex}][product_name]" value="${escapeHtml(data.product_name)}" required placeholder="Product Description" class="w-full text-xs px-2.5 py-1.5 border border-slate-300 rounded font-bold text-slate-900 focus:ring-1 focus:ring-[#0a192f] focus:outline-none">
                <input type="text" name="items[${rowIndex}][custom_size_notes]" value="${escapeHtml(data.custom_size_notes || '')}" placeholder="Custom sizing notes / specifications..." class="w-full text-[11px] text-slate-500 mt-1 px-2 py-1 border border-slate-200 rounded focus:outline-none">
            </td>

            <td class="p-3 align-top">
                ${quantityCellHtml}
            </td>

            <!-- Row Total Quantity (Auto-Calculated) -->
            <td class="p-3 text-center bg-slate-50 font-bold font-mono-num text-sm text-[#0a192f] align-middle">
                <span class="row-total-qty">${data.total_qty || 0}</span>
                <input type="hidden" name="items[${rowIndex}][total_qty]" value="${data.total_qty || 0}" class="row-qty-hidden">
            </td>

            <!-- Financial Inputs -->
            <td class="p-3 align-middle">
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_price]" value="${parseFloat(data.unit_price).toFixed(2)}" oninput="calculateMatrix()" class="w-full text-right font-mono-num font-bold text-xs p-1.5 border border-slate-300 rounded unit-price-input">
            </td>
            <td class="p-3 align-middle">
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][line_discount]" value="${parseFloat(data.line_discount || 0).toFixed(2)}" oninput="calculateMatrix()" class="w-full text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded line-discount-input">
            </td>
            <td class="p-3 align-middle">
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][setup_charges]" value="${parseFloat(data.setup_charges || 0).toFixed(2)}" oninput="calculateMatrix()" class="w-full text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded setup-charges-input">
            </td>
            <td class="p-3 align-middle">
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][print_charges]" value="${parseFloat(data.print_charges || 0).toFixed(2)}" oninput="calculateMatrix()" class="w-full text-right font-mono-num text-xs p-1.5 border border-slate-200 rounded print-charges-input">
            </td>

            <!-- Row Total Amount -->
            <td class="p-3 text-right bg-slate-50 font-extrabold font-mono-num text-xs text-slate-900 align-middle">
                <span class="row-total-amt">0.00</span>
            </td>

            <!-- Delete Row -->
            <td class="p-3 text-center align-middle">
                <button type="button" onclick="removeMatrixRow(this)" title="Remove item" class="text-slate-400 hover:text-rose-600 font-bold text-lg p-1 transition-colors">&times;</button>
            </td>
        `;

        tbody.appendChild(tr);
        rowIndex++;
        calculateMatrix();
        updateLineItemsCount();
    }

    /**
     * 4. Remove a line item with confirmation if populated.
     */
    function removeMatrixRow(btn) {
        const row = btn.closest('.matrix-row');
        const rows = document.querySelectorAll('.matrix-row');
        if (rows.length <= 1) {
            alert('A quotation must contain at least one line item.');
            return;
        }

        const nameInput = row.querySelector('input[name*="[product_name]"]');
        const itemName = nameInput ? nameInput.value : 'this item';

        if (confirm(`Remove "${itemName}" from this quotation?`)) {
            row.remove();
            calculateMatrix();
            updateLineItemsCount();
        }
    }

    /**
     * 5. Real-Time Calculation Engine across all items.
     */
    function calculateMatrix() {
        let grandTotalUnits = 0;
        let summarySubtotal = 0;
        let summarySetup = 0;
        let summaryPrint = 0;

        const rows = document.querySelectorAll('.matrix-row');
        rows.forEach(row => {
            const mode = row.dataset.mode;
            let rowQty = 0;

            if (mode === 'garment_matrix') {
                const sizes = row.querySelectorAll('.size-input');
                sizes.forEach(input => {
                    rowQty += parseInt(input.value) || 0;
                });
                const sumPreview = row.querySelector('.matrix-sum-preview');
                if (sumPreview) sumPreview.textContent = rowQty;
            } else {
                const qtyInput = row.querySelector('.standard-qty-input');
                rowQty = qtyInput ? (parseInt(qtyInput.value) || 0) : 0;
            }

            // Update row total quantity
            const totalQtySpan = row.querySelector('.row-total-qty');
            const totalQtyHidden = row.querySelector('.row-qty-hidden');
            if (totalQtySpan) totalQtySpan.textContent = rowQty;
            if (totalQtyHidden) totalQtyHidden.value = rowQty;

            // Financial inputs
            const unitPrice = parseFloat(row.querySelector('.unit-price-input')?.value) || 0;
            const lineDisc = parseFloat(row.querySelector('.line-discount-input')?.value) || 0;
            const setup = parseFloat(row.querySelector('.setup-charges-input')?.value) || 0;
            const print = parseFloat(row.querySelector('.print-charges-input')?.value) || 0;

            const baseAmount = rowQty * unitPrice;
            const lineTotal = Math.max(0, baseAmount + setup + print - lineDisc);

            const amtSpan = row.querySelector('.row-total-amt');
            if (amtSpan) {
                amtSpan.textContent = formatMoney(lineTotal);
            }

            grandTotalUnits += rowQty;
            summarySubtotal += lineTotal;
            summarySetup += setup;
            summaryPrint += print;
        });

        const overallDiscount = parseFloat(document.getElementById('overallDiscount')?.value) || 0;
        const grandTotal = Math.max(0, summarySubtotal - overallDiscount);

        // Update summary display
        const unitsEl = document.getElementById('grandTotalUnits');
        if (unitsEl) unitsEl.textContent = grandTotalUnits.toLocaleString() + ' units';

        const subtotalEl = document.getElementById('summarySubtotal');
        if (subtotalEl) subtotalEl.textContent = 'QAR ' + formatMoney(summarySubtotal);

        const setupEl = document.getElementById('summarySetup');
        if (setupEl) setupEl.textContent = 'QAR ' + formatMoney(summarySetup);

        const printEl = document.getElementById('summaryPrint');
        if (printEl) printEl.textContent = 'QAR ' + formatMoney(summaryPrint);

        const grandEl = document.getElementById('grandTotalDisplay');
        if (grandEl) grandEl.textContent = formatMoney(grandTotal);
    }

    function formatMoney(num) {
        return (num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag));
    }

    function updateLineItemsCount() {
        const rows = document.querySelectorAll('.matrix-row');
        const badge = document.getElementById('lineItemsCountBadge');
        if (badge) {
            badge.textContent = rows.length === 1 ? '1 Item' : `${rows.length} Items`;
        }
    }

    /**
     * 6. Custom Item Modal controls
     */
    function openAddCustomModal() {
        document.getElementById('customItemModal').classList.remove('hidden');
        document.getElementById('customModalName').focus();
    }

    function closeAddCustomModal() {
        document.getElementById('customItemModal').classList.add('hidden');
    }

    function submitCustomModalItem() {
        const name = document.getElementById('customModalName').value.trim();
        if (!name) {
            alert('Please enter a product or service description.');
            document.getElementById('customModalName').focus();
            return;
        }

        const category = document.getElementById('customModalCategory').value;
        const mode = document.getElementById('customModalMode').value;
        const price = parseFloat(document.getElementById('customModalPrice').value) || 0;
        const unit = document.getElementById('customModalUnit').value;
        const setup = parseFloat(document.getElementById('customModalSetup').value) || 0;
        const print = parseFloat(document.getElementById('customModalPrint').value) || 0;
        const notes = document.getElementById('customModalNotes').value.trim();

        addLineItemRow({
            product_id: null, // Unlisted custom item
            product_name: name,
            category: category,
            input_mode: mode,
            pricing_unit: unit,
            unit_price: price,
            setup_charges: setup,
            print_charges: print,
            line_discount: 0,
            total_qty: mode === 'garment_matrix' ? 30 : 10,
            size_xs: mode === 'garment_matrix' ? 5 : 0,
            size_s: mode === 'garment_matrix' ? 10 : 0,
            size_m: mode === 'garment_matrix' ? 10 : 0,
            size_l: mode === 'garment_matrix' ? 5 : 0,
            size_xl: 0,
            size_2xl: 0,
            size_3xl: 0,
            size_custom: 0,
            custom_size_notes: notes
        });

        // Reset and close
        document.getElementById('customModalName').value = '';
        document.getElementById('customModalNotes').value = '';
        closeAddCustomModal();
    }

    /**
     * 7. Multi-Category Quotation Demo Loader (QT-2026-009)
     * Loads Section 9 Master Prompt requirement:
     * 1. School Uniforms - Custom Pique Polo Shirt (100 @ 65 = 6500)
     * 2. School Uniforms - School Trousers (50 @ 95 = 4750)
     * 3. Computerized Embroidery - Logo Embroidery (150 @ 5 = 750)
     * 4. DTF Printing - Printed Design (100 @ 8 = 800)
     * 5. Promotional Gifts - Executive Gift Set (20 @ 125 = 2500)
     * Subtotal: QAR 15,300
     */
    function loadMultiCategoryDemo() {
        if (!confirm('Load the official Multi-Category Demo items (School Uniforms, Embroidery, DTF Printing, and Gifts)? This will replace current items.')) {
            return;
        }

        const tbody = document.getElementById('matrixTbody');
        tbody.innerHTML = '';
        rowIndex = 0;

        const demoItems = [
            {
                product_id: 1,
                product_name: 'Custom Pique Polo Shirt',
                category: 'School Uniforms',
                input_mode: 'garment_matrix',
                pricing_unit: 'piece',
                unit_price: 65.00,
                total_qty: 100,
                size_xs: 10,
                size_s: 20,
                size_m: 30,
                size_l: 25,
                size_xl: 15,
                size_2xl: 0,
                size_3xl: 0,
                size_custom: 0,
                custom_size_notes: 'White breathable pique with navy collar tipping',
                setup_charges: 0,
                print_charges: 0,
                line_discount: 0
            },
            {
                product_id: 2,
                product_name: 'Formal School Trousers',
                category: 'School Uniforms',
                input_mode: 'garment_matrix',
                pricing_unit: 'piece',
                unit_price: 95.00,
                total_qty: 50,
                size_xs: 0,
                size_s: 10,
                size_m: 20,
                size_l: 15,
                size_xl: 5,
                size_2xl: 0,
                size_3xl: 0,
                size_custom: 0,
                custom_size_notes: 'Charcoal grey poly-viscose',
                setup_charges: 0,
                print_charges: 0,
                line_discount: 0
            },
            {
                product_id: 10,
                product_name: 'Logo Embroidery',
                category: 'Computerized Embroidery',
                input_mode: 'service_qty',
                pricing_unit: 'service',
                unit_price: 5.00,
                total_qty: 150,
                setup_charges: 0,
                print_charges: 0,
                line_discount: 0,
                custom_size_notes: 'Chest crest embroidery up to 8,000 stitches'
            },
            {
                product_id: 13,
                product_name: 'DTF Printed Design',
                category: 'DTF Printing',
                input_mode: 'service_qty',
                pricing_unit: 'design',
                unit_price: 8.00,
                total_qty: 100,
                setup_charges: 0,
                print_charges: 0,
                line_discount: 0,
                custom_size_notes: 'Full color back print 25cm x 15cm'
            },
            {
                product_id: 16,
                product_name: 'Executive Gift Set',
                category: 'Promotional Gifts',
                input_mode: 'standard_qty',
                pricing_unit: 'set',
                unit_price: 125.00,
                total_qty: 20,
                setup_charges: 0,
                print_charges: 0,
                line_discount: 0,
                custom_size_notes: 'VIP presentation box with laser-engraved pen & journal'
            }
        ];

        demoItems.forEach(item => {
            addLineItemRow(item);
        });

        // Set customer if DBIS is present
        const customerSelect = document.getElementById('customer_id');
        if (customerSelect) {
            for (let i = 0; i < customerSelect.options.length; i++) {
                if (customerSelect.options[i].text.includes('DBIS') || customerSelect.options[i].text.includes('Doha British')) {
                    customerSelect.selectedIndex = i;
                    break;
                }
            }
        }
    }

    /**
     * Artwork upload preview
     */
    function handleArtworkPreview(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('dropZonePrompt').classList.add('hidden');
                document.getElementById('artworkPreviewBox').classList.remove('hidden');
                document.getElementById('artworkFileName').textContent = file.name;
                if (file.type.startsWith('image/')) {
                    document.getElementById('artworkPreviewImg').src = e.target.result;
                    document.getElementById('artworkPreviewImg').classList.remove('hidden');
                } else {
                    document.getElementById('artworkPreviewImg').classList.add('hidden');
                }
            };
            reader.readAsDataURL(file);
        }
    }

    function clearArtworkFile() {
        document.getElementById('artworkFileInput').value = '';
        document.getElementById('dropZonePrompt').classList.remove('hidden');
        document.getElementById('artworkPreviewBox').classList.add('hidden');
    }

    // Initialize drag and drop
    const dropZone = document.getElementById('dropZone');
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('border-[#0a192f]', 'bg-slate-100/60'), false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-[#0a192f]', 'bg-slate-100/60'), false);
    });
    dropZone.addEventListener('drop', e => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            document.getElementById('artworkFileInput').files = files;
            handleArtworkPreview(document.getElementById('artworkFileInput'));
        }
    });
    dropZone.addEventListener('click', () => {
        document.getElementById('artworkFileInput').click();
    });

    // Run initial calculation on page load
    document.addEventListener('DOMContentLoaded', () => {
        calculateMatrix();
        updateLineItemsCount();
    });
</script>
@endpush
@endsection
