@extends('layouts.app')

@php
    $isInvoice = ($documentType ?? 'quotation') === 'invoice';
    $documentLabel = $isInvoice ? 'Invoice' : 'Quotation';
    $documentRoutes = $isInvoice ? 'invoices' : 'quotations';
    $numberField = $isInvoice ? 'invoice_number' : 'quotation_number';
    $dateField = $isInvoice ? 'invoice_date' : 'quotation_date';
    $targetField = $isInvoice ? 'due_date' : 'delivery_date';
    $restoredItems = old('items', []);
@endphp

@section('title', 'Create '.$documentLabel)
@section('page_title', 'Create '.$documentLabel)

@section('header_action')
    <a href="{{ route($documentRoutes.'.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
        &larr; Back to {{ $isInvoice ? 'Invoices' : 'Quotations' }}
    </a>
@endsection

@push('styles')
<style>
    /* Smart Catalog Dropdown & Option Visibility Fix */
    .catalog-select {
        color-scheme: dark !important;
        background-color: #0b1c36 !important;
        color: #ffffff !important;
        border: 1.5px solid #64748b !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23d4af37' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
        background-position: right 0.75rem center !important;
        background-repeat: no-repeat !important;
        background-size: 1.25rem 1.25rem !important;
        padding-right: 2.5rem !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
    }
    .catalog-select:hover {
        border-color: #94a3b8 !important;
        background-color: #0e223d !important;
    }
    .catalog-select:focus {
        border-color: #d4af37 !important;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.25) !important;
        outline: none !important;
    }
    /* Ensure native <option> elements in opened popup are dark navy with crisp white/gold text */
    .catalog-select option {
        background-color: #0b1c36 !important;
        color: #ffffff !important;
        padding: 8px 12px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
    }
    .catalog-select option:checked {
        background-color: #173559 !important;
        color: #ffd700 !important;
        font-weight: 700 !important;
    }
    .catalog-select option:hover {
        background-color: #132a4a !important;
        color: #ffffff !important;
    }
    .catalog-select option[value=""] {
        color: #cbd5e1 !important;
        font-style: italic !important;
    }
</style>
@endpush

@section('content')
<div class="ui-page pt-2">

    <form id="documentForm" action="{{ route($documentRoutes.'.store') }}" method="POST" enctype="multipart/form-data" class="ui-page">
        @csrf

        <!-- Top Header & Customer Selection Card -->
        <div class="ui-section bg-white rounded-xl border border-slate-200/90 shadow-xs">
            <div class="ui-form-back no-print">@yield('header_action')</div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b border-slate-100 pb-3 mb-4 gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#d4af37]"></span>
                    <h2 class="ui-section-heading text-slate-900">{{ $documentLabel }} details</h2>
                    <span class="text-[11px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded font-semibold border border-slate-200">
                        Products & Services
                    </span>
                </div>
                <div class="ui-help flex flex-wrap items-center gap-2 text-slate-500">
                    <div>Currency: <strong class="text-slate-900">QAR (Qatari Riyal)</strong></div>
                    <span>•</span>
                    <div>Timezone: <strong class="text-slate-900">Asia/Qatar</strong></div>
                </div>
            </div>

            <div class="space-y-4 text-xs">
                @php
                    $targetCustomerId = request('customer_id');
                    $lockedCustomer = $targetCustomerId ? $customers->firstWhere('id', $targetCustomerId) : null;
                @endphp

                <!-- Hidden Category for backward compatibility & controller defaults -->
                <input type="hidden" name="category" value="{{ old('category', 'Multi-Category') }}">

                <!-- Customer Selector + Quick Add -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="{{ $lockedCustomer ? 'selectedCustomerName' : 'customer_id' }}" class="ui-field-label block text-slate-700">Customer / Organization *</label>
                        @if($lockedCustomer)
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span>Selected Customer</span>
                            </span>
                        @else
                            @section('has_customer_add_action', '1')
                            <button type="button" onclick="openQuickCustomerModal()" class="text-[11px] font-bold text-[#0a192f] hover:text-[#d4af37] underline">
                                + Quick Add Customer
                            </button>
                        @endif
                    </div>
                    @if($lockedCustomer)
                        <div class="relative">
                            <input type="text" id="selectedCustomerName" aria-label="Selected customer" readonly value="{{ $lockedCustomer->name }} {{ $lockedCustomer->company_name ? "({$lockedCustomer->company_name})" : '' }} [{{ $lockedCustomer->customer_code }}]" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-slate-100 text-slate-800 font-bold text-xs cursor-not-allowed focus:outline-none shadow-xs">
                            <input type="hidden" name="customer_id" id="customer_id" value="{{ $lockedCustomer->id }}">
                        </div>
                    @else
                        <select name="customer_id" id="customer_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white font-medium">
                            <option value="">-- Select Customer / Client --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ (request('customer_id') == $c->id || old('customer_id') == $c->id) ? 'selected' : '' }}>
                                    {{ $c->name }} {{ $c->company_name ? "({$c->company_name})" : '' }} [{{ $c->customer_code }}]
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <!-- Quotation Number -->
                    <div class="min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <label for="documentNumber" class="ui-field-label block text-slate-700">{{ $documentLabel }} Number *</label>
                            <span class="ui-help text-slate-500">Auto-generated</span>
                        </div>
                        <input type="text" name="{{ $numberField }}" id="documentNumber" value="{{ $errors->has($numberField) ? $suggestedNumber : old($numberField, $suggestedNumber) }}" readonly required class="w-full min-w-0 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none font-mono-num font-bold text-[#0a192f] bg-slate-100 cursor-not-allowed">
                    </div>

                    <!-- Quotation Date -->
                    <div class="min-w-0">
                        <label for="documentDate" class="ui-field-label block text-slate-700 mb-1">{{ $documentLabel }} Date *</label>
                        <input type="date" name="{{ $dateField }}" id="documentDate" value="{{ old($dateField, date('Y-m-d')) }}" required class="w-full min-w-0 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                    </div>

                    <!-- Delivery Due Date -->
                    <div class="min-w-0">
                        <label for="documentTargetDate" class="ui-field-label block text-slate-700 mb-1">{{ $isInvoice ? 'Payment Due Date' : 'Target Delivery Date' }}</label>
                        <input type="date" name="{{ $targetField }}" id="documentTargetDate" value="{{ old($targetField, date('Y-m-d', strtotime($isInvoice ? '+15 days' : '+7 days'))) }}" class="w-full min-w-0 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        <!-- Smart Product Catalog & Category Filter Selection Desk -->
        <div class="ui-section bg-gradient-to-r from-slate-900 via-[#0a192f] to-[#132a4a] text-white rounded-xl shadow-xs border border-slate-700">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3 pb-3 border-b border-slate-700">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#d4af37]"></span>
                        <h3 class="ui-section-heading text-white">Add products & services</h3>
                    </div>
                    <p class="ui-help text-slate-200 mt-1">Choose an item, then click Add Item. For unlisted items, use Add Custom Item.</p>
                </div>
            </div>

            <div class="document-catalog-controls grid grid-cols-1 lg:grid-cols-12 items-end gap-4 mt-4 text-xs">
                <!-- 1. Category Filter Selector -->
                <div class="min-w-0 lg:col-span-4">
                    <label for="categoryFilter" class="document-catalog-label ui-field-label text-slate-100">
                        <span class="flex items-center">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[#d4af37] text-slate-950 text-[10px] font-extrabold mr-1.5 shadow-2xs">1</span>
                            <span>Category</span>
                        </span>
                    </label>
                    <select id="categoryFilter" onchange="handleCategoryFilterChange(this.value)" class="w-full px-3 py-2.5 bg-[#0b1c36] border border-slate-500 rounded-lg text-white font-semibold text-xs focus:ring-2 focus:ring-[#d4af37] focus:border-[#d4af37] focus:outline-none cursor-pointer shadow-inner catalog-select">
                        <option value="all" class="bg-[#0b1c36] text-white">★ All Categories (Show All Active Products)</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" class="bg-[#0b1c36] text-white">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Product Catalog Preset Selector -->
                <div class="min-w-0 lg:col-span-5">
                    <label for="catalogPresetSelect" class="document-catalog-label ui-field-label text-slate-100">
                        <span class="flex items-center">
                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[#d4af37] text-slate-950 text-[10px] font-extrabold mr-1.5 shadow-2xs">2</span>
                            <span>Catalog item</span>
                        </span>
                        <span id="filteredCountBadge" class="text-[11px] font-semibold text-slate-200 bg-slate-800/90 px-2 py-0.5 rounded-md border border-slate-600 shadow-2xs">({{ count($products) }} items available)</span>
                    </label>
                    <select id="catalogPresetSelect" onchange="updateCatalogAddButton()" class="w-full px-3 py-2.5 bg-[#0b1c36] border border-slate-500 rounded-lg text-white font-semibold text-xs focus:ring-2 focus:ring-[#d4af37] focus:border-[#d4af37] focus:outline-none cursor-pointer shadow-inner catalog-select">
                        <option value="" class="bg-[#0b1c36] text-slate-300 italic">Select a product or service...</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}" data-category="{{ $prod->category }}" class="bg-[#0b1c36] text-white">
                                {{ $prod->name }} — QAR {{ number_format($prod->unit_price, 2) }} / {{ $prod->pricing_unit }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Explicit add action; selecting a catalog option never adds a row. -->
                <div class="min-w-0 lg:col-span-3 flex flex-col justify-end">
                    <button type="button" id="addCatalogItemButton" onclick="addSelectedItem()" class="w-full bg-[#d4af37] hover:bg-[#c59b27] text-slate-950 border border-[#e5c358] px-4 py-2.5 rounded-lg text-xs font-extrabold shadow-md flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                        <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span id="addCatalogItemLabel">Add Custom Item</span>
                    </button>
                </div>
            </div>

            <!-- Empty category state message -->
            <div id="emptyCategoryNotice" class="hidden mt-3 p-3 bg-amber-950/60 border border-amber-700/80 rounded-lg text-amber-200 text-xs flex items-center justify-between">
                <span>No catalog products currently found in this category. You can add a custom item or add products to the catalog.</span>
            </div>
        </div>

        <!-- Product Line Items Table -->
        <div class="ui-section bg-white rounded-xl border border-slate-200/90 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="ui-section-heading text-slate-900">Items & quantities</h3>
                        <span id="lineItemsCountBadge" class="text-[10px] bg-slate-100 text-slate-700 font-bold px-2 py-0.5 rounded border border-slate-200">
                            0 Items
                        </span>
                    </div>
                    <p class="ui-help text-slate-500 mt-1">Enter sizes or quantity for each item, then review prices and charges.</p>
                </div>

            </div>

            <!-- Size Matrix & Item Table Container -->
            <div data-paginated-table-region class="border border-slate-200 rounded-xl">
                <div class="overflow-x-auto">
                <table id="matrixTable" data-paginate-table data-pagination-label="items" data-pagination-row-selector=".matrix-row" class="ui-table w-full text-left">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2.5 min-w-[220px]">Product / service</th>
                            <th class="px-2 py-2.5 min-w-[340px]">Quantity & sizes</th>
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
                        <tr id="emptyLineItems" data-pagination-empty><td colspan="9" class="p-6 text-center text-slate-500">Select a catalog product or add a custom item to start.</td></tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Customer Logo / Artwork Attachment & Financial Calculation Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Left: Artwork & Notes -->
            <div class="ui-section bg-white rounded-xl border border-slate-200/90 shadow-xs space-y-3">
                @unless($isInvoice)
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <h3 class="ui-section-heading text-slate-900">Artwork reference</h3>
                    </div>
                    <span class="text-[10px] text-slate-400">PNG, JPG, PDF, SVG</span>
                </div>

                <div id="dropZone" class="border-2 border-dashed border-slate-300 hover:border-[#0a192f] rounded-xl p-5 text-center cursor-pointer transition-colors bg-slate-50/50">
                    <input type="file" name="artwork_file" id="artworkFileInput" accept="image/*,.pdf,.svg" class="hidden" onchange="handleArtworkPreview(this)">
                    <div id="dropZonePrompt" class="space-y-1">
                        <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-600 mx-auto flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        </div>
                        <div class="text-xs font-semibold text-slate-800">Drop customer artwork here</div>
                        <div class="ui-help text-slate-500">or click to browse files</div>
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

                @endunless
                <div>
                    <label for="documentNotes" class="ui-field-label block text-slate-700 mb-1">Production notes & specifications</label>
                    <textarea name="notes" id="documentNotes" rows="3" placeholder="Enter the agreed specifications, delivery instructions, and payment terms." class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Right: Financial Calculation Summary Card -->
            <div class="ui-section bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-col justify-between">
                <div>
                    <h3 class="ui-section-heading text-slate-900 border-b border-slate-100 pb-2 mb-3">
                        {{ $documentLabel }} summary
                    </h3>
                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Total quantity</span>
                            <span id="grandTotalUnits" class="font-mono-num font-bold text-slate-900 text-sm">0 units</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Items subtotal</span>
                            <span id="summarySubtotal" class="font-mono-num font-bold text-slate-900">QAR 0.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Setup fees <span class="ui-help">(included)</span></span>
                            <span id="summarySetup" class="font-mono-num font-bold text-slate-800">QAR 0.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span>Printing fees <span class="ui-help">(included)</span></span>
                            <span id="summaryPrint" class="font-mono-num font-bold text-slate-800">QAR 0.00</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <label for="overallSetup" class="ui-field-label">Additional setup (QAR)</label>
                            <div class="w-32"><input type="number" step="0.01" min="0" name="setup_charges" id="overallSetup" value="{{ old('setup_charges', '0.00') }}" oninput="calculateMatrix()" class="w-full text-right font-mono-num text-xs px-2 py-1 border border-slate-300 rounded"></div>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <label for="overallPrint" class="ui-field-label">Additional printing (QAR)</label>
                            <div class="w-32"><input type="number" step="0.01" min="0" name="print_charges" id="overallPrint" value="{{ old('print_charges', '0.00') }}" oninput="calculateMatrix()" class="w-full text-right font-mono-num text-xs px-2 py-1 border border-slate-300 rounded"></div>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <label for="overallDiscount" class="ui-field-label">Order discount (QAR)</label>
                            <div class="w-32">
                                <input type="number" step="0.01" min="0" name="discount" id="overallDiscount" value="{{ old('discount', '0.00') }}" oninput="calculateMatrix()" class="w-full text-right font-mono-num font-bold text-xs px-2 py-1 border border-slate-300 rounded focus:ring-1 focus:ring-[#0a192f] focus:outline-none">
                            </div>
                        </div>
                        <p id="overallDiscountNote" class="text-[11px] text-amber-700" aria-live="polite"></p>
                    </div>
                </div>

                <!-- Grand Total Box -->
                <div class="mt-5 pt-4 border-t border-slate-200">
                    <div class="bg-[#0a192f] text-white p-4 rounded-xl flex flex-wrap items-center justify-between gap-3 shadow-md">
                        <div>
                            <span class="ui-field-label text-[#d4af37] block">Grand total</span>
                            <div class="ui-help text-slate-300">All items & charges included</div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-semibold text-[#d4af37]">QAR</span>
                            <span id="grandTotalDisplay" class="text-2xl sm:text-3xl font-extrabold font-mono-num text-white">0.00</span>
                        </div>
                    </div>

                    <p class="ui-help text-slate-500 mt-3">Discounts apply to item prices. Setup and printing fees remain payable.</p>
                    <!-- Actions -->
                    <div class="mt-4 flex flex-wrap items-center justify-end gap-3">
                        @unless($isInvoice)
                        <button type="submit" name="action" value="draft" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition-colors">
                            Save as Draft
                        </button>
                        @endunless
                        <button type="submit" name="action" value="send" class="px-5 py-2 rounded-lg text-xs font-extrabold text-slate-950 bg-[#d4af37] hover:bg-[#c59b27] shadow-sm flex items-center gap-1.5 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            <span>{{ $isInvoice ? 'Create Invoice & Queue Production' : 'Save & Generate Proposal' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>

</div>

<!-- Modal: Add Custom Item (Unlisted Product / Custom Specification) -->
<div id="customItemModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-start justify-center p-4">
    <div class="ui-section bg-white rounded-xl shadow-2xl max-w-lg w-full my-auto shrink-0 space-y-4 border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#0a192f] text-[#d4af37] flex items-center justify-center font-bold text-sm">+</div>
                <h3 class="ui-section-heading text-slate-900">Add custom item</h3>
            </div>
            <button type="button" onclick="closeAddCustomModal()" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <div class="space-y-3 text-xs">
            <div>
                <label for="customModalName" class="ui-field-label block text-slate-700 mb-1">Product or Service Description *</label>
                <input type="text" id="customModalName" placeholder="e.g. Custom Sublimated Team Banner / VIP Gift Pen" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-1 focus:ring-[#0a192f] focus:outline-none font-semibold">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="customModalCategory" class="ui-field-label block text-slate-700 mb-1">Product Category *</label>
                    <select id="customModalCategory" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="customModalMode" class="ui-field-label block text-slate-700 mb-1">Input / Quantity Mode *</label>
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
                    <label for="customModalPrice" class="ui-field-label block text-slate-700 mb-1">Default Unit Price (QAR) *</label>
                    <input type="number" step="0.01" min="0" id="customModalPrice" value="0.00" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none font-mono-num font-bold">
                </div>
                <div>
                    <label for="customModalUnit" class="ui-field-label block text-slate-700 mb-1">Pricing Unit</label>
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
                    <label for="customModalSetup" class="ui-field-label block text-slate-700 mb-1">Setup / Screen Fee (QAR)</label>
                    <input type="number" step="0.01" min="0" id="customModalSetup" value="0.00" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none font-mono-num">
                </div>
                <div>
                    <label for="customModalPrint" class="ui-field-label block text-slate-700 mb-1">Print / DTF Charge (QAR)</label>
                    <input type="number" step="0.01" min="0" id="customModalPrint" value="0.00" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none font-mono-num">
                </div>
            </div>

            <div>
                <label for="customModalNotes" class="ui-field-label block text-slate-700 mb-1">Specification / Sizing Notes</label>
                <input type="text" id="customModalNotes" placeholder="e.g. Dimensions 120cm x 60cm, gold embroidery thread" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none text-[11px]">
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" onclick="closeAddCustomModal()" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100">Cancel</button>
            <button type="button" onclick="submitCustomModalItem()" class="px-4 py-1.5 rounded-lg text-xs font-extrabold bg-[#0a192f] text-white hover:bg-[#132a4a]">
                Add Item
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Active products passed from server for zero-latency instant category filtering
    const catalogProducts = @json($products);
    let rowIndex = 0;
    const restoredItems = @json($restoredItems);

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

        // Reset dropdown — apply explicit inline styles for cross-browser option visibility
        const placeholderOpt = document.createElement('option');
        placeholderOpt.value = '';
        placeholderOpt.textContent = 'Select a product or service...';
        placeholderOpt.style.backgroundColor = '#0b1c36';
        placeholderOpt.style.color = '#cbd5e1';
        placeholderOpt.style.fontStyle = 'italic';
        select.innerHTML = '';
        select.appendChild(placeholderOpt);

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
                opt.style.backgroundColor = '#0b1c36';
                opt.style.color = '#ffffff';
                select.appendChild(opt);
            });
        }
        updateCatalogAddButton();
    }

    function updateCatalogAddButton() {
        const selected = document.getElementById('catalogPresetSelect').value;
        document.getElementById('addCatalogItemLabel').textContent = selected ? 'Add Item' : 'Add Custom Item';
    }

    /** Add the pending catalog choice only on explicit button activation. */
    function addSelectedItem() {
        const selectElement = document.getElementById('catalogPresetSelect');
        const prodId = selectElement.value;
        if (!prodId) {
            openAddCustomModal();
            return;
        }

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
            total_qty: 0,
            // default matrix sizes if garment
            size_xs: 0,
            size_s: 0,
            size_m: 0,
            size_l: 0,
            size_xl: 0,
            size_2xl: 0,
            size_3xl: 0,
            size_custom: 0,
            custom_size_notes: ''
        });

        // Reset dropdown to placeholder so another item can be added immediately
        selectElement.value = '';
        updateCatalogAddButton();
    }

    /**
     * 3. Core Line Item Factory: Appends a responsive row with product-specific input mode.
     */
    function addLineItemRow(data) {
        const tbody = document.getElementById('matrixTbody');
        document.getElementById('emptyLineItems')?.remove();
        data = {...data};
        ['size_xs','size_s','size_m','size_l','size_xl','size_2xl','size_3xl','size_custom','total_qty','unit_price','line_discount','setup_charges','print_charges'].forEach(key => {
            const value = Number(data[key] ?? 0);
            data[key] = Number.isFinite(value) ? value : 0;
        });
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
            const unitLabel = escapeHtml(data.pricing_unit || (data.input_mode === 'service_qty' ? 'services' : 'units'));
            quantityCellHtml = `
                <div class="py-1">
                    <div class="flex items-center gap-3">
                        <div class="w-36">
                            <label class="block text-[10px] font-bold text-slate-500 mb-0.5">Quantity (${unitLabel}):</label>
                            <input type="number" min="1" name="items[${rowIndex}][total_qty]" value="${data.total_qty || 0}" oninput="calculateMatrix()" class="w-full text-center text-sm font-bold font-mono-num p-1.5 border border-slate-300 rounded focus:ring-1 focus:ring-[#0a192f] focus:outline-none standard-qty-input">
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
                        ${escapeHtml(data.category)}
                    </span>
                    <span class="text-[10px] text-slate-400 font-medium">${modeLabel}</span>
                </div>
                <input type="hidden" name="items[${rowIndex}][product_id]" value="${escapeHtml(data.product_id || '')}" class="item-product-id">
                <input type="hidden" name="items[${rowIndex}][category]" value="${escapeHtml(data.category)}" class="item-category">
                <input type="hidden" name="items[${rowIndex}][input_mode]" value="${escapeHtml(data.input_mode)}" class="item-input-mode">
                <input type="hidden" name="items[${rowIndex}][pricing_unit]" value="${escapeHtml(data.pricing_unit)}" class="item-pricing-unit">

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
    async function removeMatrixRow(btn) {
        const row = btn.closest('.matrix-row');
        const rows = document.querySelectorAll('.matrix-row');
        if (rows.length <= 1) {
            row.remove();
            document.getElementById('matrixTbody').innerHTML = '<tr id="emptyLineItems"><td colspan="9" class="p-6 text-center text-slate-500">Select a catalog product or add a custom item to start.</td></tr>';
            calculateMatrix();
            updateLineItemsCount();
            return;
        }

        const nameInput = row.querySelector('input[name*="[product_name]"]');
        const itemName = nameInput ? nameInput.value : 'this item';

        if (await window.appConfirm({
            title: 'Remove item?',
            message: `Remove "${itemName}" from this document?`,
            confirmLabel: 'Remove item',
            cancelLabel: 'Cancel',
            destructive: true
        })) {
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
        const lineTotals = [];

        const rows = document.querySelectorAll('.matrix-row');
        rows.forEach(row => {
            const mode = row.dataset.mode;
            let rowQty = 0;

            if (mode === 'garment_matrix') {
                const sizes = row.querySelectorAll('.size-input');
                sizes.forEach(input => {
                    rowQty += readQuantity(input);
                });
                const sumPreview = row.querySelector('.matrix-sum-preview');
                if (sumPreview) sumPreview.textContent = rowQty;
            } else {
                const qtyInput = row.querySelector('.standard-qty-input');
                rowQty = qtyInput ? readQuantity(qtyInput) : 0;
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

            const {lineTotal, lineDiscount} = window.documentPricing.calculateLine({
                quantity: rowQty, unitPrice, lineDiscount: lineDisc, setupCharges: setup, printCharges: print
            });
            lineTotals.push(lineTotal);
            let discountNote = row.querySelector('.discount-note');
            if (!discountNote) {
                discountNote = document.createElement('span');
                discountNote.className = 'discount-note block text-[10px] text-amber-700 mt-1';
                row.querySelector('.line-discount-input').after(discountNote);
            }
            discountNote.textContent = lineDisc > lineDiscount ? `Applied: ${formatMoney(lineDiscount)}` : '';

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
        const overallSetup = parseFloat(document.getElementById('overallSetup')?.value) || 0;
        const overallPrint = parseFloat(document.getElementById('overallPrint')?.value) || 0;
        const totals = window.documentPricing.calculateTotal({lineTotals, discount: overallDiscount, setupCharges: overallSetup, printCharges: overallPrint});
        const grandTotal = totals.grandTotal;
        document.getElementById('overallDiscountNote').textContent = overallDiscount > totals.discount ? `Applied discount: QAR ${formatMoney(totals.discount)}` : '';

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

    function readQuantity(input) {
        const value = Number(input.value);
        const valid = input.value === '' || (Number.isSafeInteger(value) && value >= 0 && value <= 1000000);
        input.setCustomValidity(valid ? '' : 'Enter a whole quantity between 0 and 1,000,000.');
        if (valid && input.value !== '') input.value = String(value);
        return valid ? value : 0;
    }

    function formatMoney(num) {
        return (num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, tag => ({
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
            window.appNotify({
                type: 'error',
                title: 'Description required',
                message: 'Please enter a product or service description.'
            });
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
            total_qty: 0,
            size_xs: 0,
            size_s: 0,
            size_m: 0,
            size_l: 0,
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
    if (dropZone) {
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

    }

    // Restore submitted rows before applying validation highlights.
    document.addEventListener('DOMContentLoaded', () => {
        Object.entries(restoredItems || {}).forEach(([index, item]) => {
            rowIndex = Number(index);
            addLineItemRow(item);
        });
        document.getElementById('documentForm').addEventListener('submit', event => {
            if (!document.querySelector('.matrix-row')) {
                event.preventDefault();
                window.appNotify({
                    type: 'error',
                    title: 'Add an item',
                    message: 'Add at least one item and enter its quantity.'
                });
                document.getElementById('catalogPresetSelect').focus();
            }
        });
    });

    // Run initial calculation on page load
    document.addEventListener('DOMContentLoaded', () => {
        updateCatalogAddButton();
        calculateMatrix();
        updateLineItemsCount();
    });
</script>
@endpush
@endsection
