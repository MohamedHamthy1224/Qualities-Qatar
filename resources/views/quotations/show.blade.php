@extends('layouts.app')

@section('title', "Quotation {$quotation->quotation_number}")
@section('page_title', "Quotation {$quotation->quotation_number}")

@section('header_action')
    <div class="flex items-center gap-2">
        <a href="{{ route('quotations.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
            &larr; All Quotes
        </a>

        <!-- Print View -->
        <a href="{{ route('pdf.quotation', ['quotation' => $quotation, 'print' => 1]) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-300">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print View (A4)</span>
        </a>

        <!-- PDF Download -->
        <a href="{{ route('pdf.quotation', $quotation) }}" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs">
            <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Download PDF</span>
        </a>

        <!-- 1-Click Convert to Invoice Button -->
        @if($quotation->invoice)
            <a href="{{ route('invoices.show', $quotation->invoice) }}" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs">
                <span>View Invoice: {{ $quotation->invoice->invoice_number }}</span>
            </a>
        @else
            <form action="{{ route('quotations.convert', $quotation) }}" method="POST" class="inline">
                @csrf
                <button type="submit" onclick="return confirm('Convert this quotation into an official Sales Invoice and queue it to the Production Kanban Pipeline?')" class="inline-flex items-center gap-1.5 bg-[#d4af37] hover:bg-[#c59b27] text-slate-950 px-3.5 py-1.5 rounded-lg text-xs font-extrabold shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Convert to Sales Invoice (1-Click)</span>
                </button>
            </form>
        @endif
    </div>
@endsection

@section('content')
<div class="space-y-6 pt-2 max-w-5xl mx-auto">

    <!-- Converted Notice Banner -->
    @if($quotation->invoice)
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl flex items-center justify-between text-xs text-emerald-900">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Quotation Converted:</strong> This quotation has been converted into official Sales Invoice <strong>{{ $quotation->invoice->invoice_number }}</strong>.</span>
            </div>
            <a href="{{ route('invoices.show', $quotation->invoice) }}" class="font-bold underline hover:text-emerald-700">Open Sales Invoice &rarr;</a>
        </div>
    @endif

    <!-- Document Card -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-6">

        <!-- Branded Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-[#0a192f] text-white flex items-center justify-center font-extrabold text-2xl border-2 border-[#d4af37] shadow-md">
                    Q
                </div>
                <div>
                    <h2 class="font-extrabold text-slate-900 text-lg tracking-tight">QUALITIES UNIFORMS & GIFTS SOLUTIONS</h2>
                    <p class="text-xs text-slate-500">Salwa Road, Near Wholesale Market, Zone 56, Doha, State of Qatar</p>
                    <p class="text-[11px] text-slate-400">Tel: +974 4455 1234 | WhatsApp: +974 5588 9922 | sales@qualitiesqatar.com</p>
                </div>
            </div>

            <div class="text-left sm:text-right">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">OFFICIAL QUOTATION</div>
                <div class="text-2xl font-extrabold text-[#0a192f] font-mono-num">{{ $quotation->quotation_number }}</div>
                <div class="mt-1">
                    @php
                        $badgeStyle = match($quotation->status) {
                            'Invoiced' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                            'Approved' => 'bg-sky-100 text-sky-800 border-sky-300',
                            'Sent' => 'bg-amber-100 text-amber-800 border-amber-300',
                            'Draft' => 'bg-slate-100 text-slate-700 border-slate-300',
                            default => 'bg-slate-100 text-slate-700'
                        };
                    @endphp
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeStyle }}">
                        Status: {{ $quotation->status }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Client & Quotation Meta Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50/80 p-4 rounded-xl border border-slate-200/80 text-xs">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">PROPOSAL PREPARED FOR:</span>
                <div class="font-bold text-sm text-slate-900">{{ $quotation->customer->name }}</div>
                @if($quotation->customer->company_name)
                    <div class="font-semibold text-slate-700">{{ $quotation->customer->company_name }}</div>
                @endif
                <div class="text-slate-600 mt-1">Qatar Phone: <strong class="font-mono-num">{{ $quotation->customer->phone ?? '—' }}</strong></div>
                <div class="text-slate-600">Email: {{ $quotation->customer->email ?? '—' }}</div>
                <div class="text-slate-600">Location: {{ $quotation->customer->address ?? 'Doha, Qatar' }}</div>
            </div>

            <div class="sm:text-right space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">PROPOSAL DETAILS:</span>
                <div>Quotation Date: <strong class="font-mono-num text-slate-900">{{ $quotation->quotation_date->format('d M Y') }}</strong></div>
                <div>Target Delivery: <strong class="font-mono-num text-slate-900">{{ $quotation->delivery_date ? $quotation->delivery_date->format('d M Y') : 'Standard 7 Days' }}</strong></div>
                <div>Category: <span class="font-semibold text-[#0a192f]">{{ $quotation->category }}</span></div>
                <div>Payment Terms: <span class="font-semibold text-slate-700">50% Advance Upon Approval, Balance on Delivery</span></div>
            </div>
        </div>

        <!-- Itemized Size Matrix Table -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-xs text-slate-900 uppercase tracking-wider">Itemized Uniform Size Breakdown Matrix</h3>
                <span class="text-xs text-slate-500 font-mono-num">Total Batch Units: <strong>{{ $quotation->total_qty }} pcs</strong></span>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2.5 min-w-[200px]">Item Description & Category</th>
                            <th class="px-1.5 py-2.5 text-center">XS</th>
                            <th class="px-1.5 py-2.5 text-center">S</th>
                            <th class="px-1.5 py-2.5 text-center">M</th>
                            <th class="px-1.5 py-2.5 text-center">L</th>
                            <th class="px-1.5 py-2.5 text-center">XL</th>
                            <th class="px-1.5 py-2.5 text-center">2XL</th>
                            <th class="px-1.5 py-2.5 text-center">3XL</th>
                            <th class="px-1.5 py-2.5 text-center">Custom</th>
                            <th class="px-2 py-2.5 text-center bg-slate-200/60 font-mono-num">Qty</th>
                            <th class="px-2 py-2.5 text-right font-mono-num">Unit Price</th>
                            <th class="px-3 py-2.5 text-right bg-slate-200/60 font-mono-num">Line Total (QAR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-mono-num">
                        @foreach($quotation->items as $item)
                            @php
                                $itemCatClass = match($item->category) {
                                    'School Uniforms' => 'bg-sky-100 text-sky-800 border-sky-300',
                                    'Corporate Uniforms' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                    'Sportswear' => 'bg-amber-100 text-amber-800 border-amber-300',
                                    'Computerized Embroidery' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                    'DTF Printing' => 'bg-cyan-100 text-cyan-800 border-cyan-300',
                                    'Promotional Gifts' => 'bg-purple-100 text-purple-800 border-purple-300',
                                    default => 'bg-slate-100 text-slate-700 border-slate-300',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2.5 font-sans">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span class="inline-block text-[9px] px-2 py-0.5 rounded font-bold uppercase tracking-wider border {{ $itemCatClass }}">
                                            {{ $item->category }}
                                        </span>
                                        @if($item->input_mode !== 'garment_matrix')
                                            <span class="text-[10px] text-slate-400 font-medium capitalize">({{ str_replace('_', ' ', $item->input_mode) }})</span>
                                        @endif
                                    </div>
                                    <div class="font-bold text-slate-900 text-xs">{{ $item->product_name }}</div>
                                    @if($item->custom_size_notes)
                                        <div class="text-[11px] text-slate-500 font-normal mt-0.5">Spec: {{ $item->custom_size_notes }}</div>
                                    @endif
                                </td>

                                @if($item->input_mode === 'garment_matrix')
                                    <td class="px-1.5 py-2.5 text-center {{ $item->size_xs > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_xs }}</td>
                                    <td class="px-1.5 py-2.5 text-center {{ $item->size_s > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_s }}</td>
                                    <td class="px-1.5 py-2.5 text-center {{ $item->size_m > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_m }}</td>
                                    <td class="px-1.5 py-2.5 text-center {{ $item->size_l > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_l }}</td>
                                    <td class="px-1.5 py-2.5 text-center {{ $item->size_xl > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_xl }}</td>
                                    <td class="px-1.5 py-2.5 text-center {{ $item->size_2xl > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_2xl }}</td>
                                    <td class="px-1.5 py-2.5 text-center {{ $item->size_3xl > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_3xl }}</td>
                                    <td class="px-1.5 py-2.5 text-center {{ $item->size_custom > 0 ? 'font-bold text-purple-700 bg-purple-50' : 'text-slate-300' }}">{{ $item->size_custom }}</td>
                                @else
                                    <td colspan="8" class="px-2 py-2.5 text-center text-slate-500 italic font-sans bg-slate-50/50">
                                        Flat Quantity: <strong>{{ $item->total_qty }} {{ $item->pricing_unit ?? 'units' }}</strong>
                                    </td>
                                @endif

                                <td class="px-2 py-2.5 text-center bg-slate-50 font-bold text-slate-900">{{ $item->total_qty }}</td>
                                <td class="px-2 py-2.5 text-right">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-3 py-2.5 text-right font-extrabold text-slate-900 bg-slate-50">{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Artwork Attachment & Financial Totals -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
            <!-- Left: Artwork & Notes -->
            <div class="space-y-4">
                @if($quotation->artwork_file)
                    <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50/60">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-2">Attached Client Artwork / WhatsApp Reference</span>
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('storage/' . $quotation->artwork_file) }}" alt="Artwork" class="w-24 h-24 object-contain rounded-lg border border-slate-300 bg-white shadow-xs">
                            <div class="text-xs">
                                <div class="font-bold text-slate-800">WhatsApp / Artwork Attachment</div>
                                <a href="{{ asset('storage/' . $quotation->artwork_file) }}" target="_blank" class="text-sky-600 hover:underline font-semibold text-[11px] block mt-1">Open Original File &rarr;</a>
                            </div>
                        </div>
                    </div>
                @endif

                @if($quotation->notes)
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Production Notes:</span>
                        <p class="text-slate-700 whitespace-pre-line">{{ $quotation->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Right: Financial Breakdown -->
            <div class="bg-slate-50 rounded-xl border border-slate-200 p-5 space-y-2.5 text-xs font-mono-num">
                <div class="flex items-center justify-between text-slate-600 font-sans">
                    <span>Subtotal:</span>
                    <span class="font-mono-num font-bold text-slate-900">QAR {{ number_format($quotation->subtotal, 2) }}</span>
                </div>
                @if($quotation->setup_charges > 0)
                    <div class="flex items-center justify-between text-slate-600 font-sans">
                        <span>Embroidery Setup / Screen Fee:</span>
                        <span class="font-mono-num font-bold text-slate-800">QAR {{ number_format($quotation->setup_charges, 2) }}</span>
                    </div>
                @endif
                @if($quotation->print_charges > 0)
                    <div class="flex items-center justify-between text-slate-600 font-sans">
                        <span>Printing / DTF Heat Transfer:</span>
                        <span class="font-mono-num font-bold text-slate-800">QAR {{ number_format($quotation->print_charges, 2) }}</span>
                    </div>
                @endif
                @if($quotation->discount > 0)
                    <div class="flex items-center justify-between text-emerald-700 font-sans">
                        <span>Special Discount:</span>
                        <span class="font-mono-num font-bold text-emerald-700">- QAR {{ number_format($quotation->discount, 2) }}</span>
                    </div>
                @endif

                <div class="border-t border-slate-200 pt-3 flex items-center justify-between font-sans">
                    <div>
                        <span class="font-extrabold text-sm text-slate-900 block">Grand Total (QAR)</span>
                        <span class="text-[10px] text-slate-500">Tax/VAT inclusive where applicable</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-[#0a192f] font-mono-num">
                        QAR {{ number_format($quotation->grand_total, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Sign-off Note -->
        <div class="pt-6 border-t border-slate-200 text-xs text-slate-500 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <div>Validity: <strong>30 days from date of issuance</strong></div>
                <div>Authorized Signature: <strong>QUALITIES Uniforms & Gifts Solutions</strong></div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Status updater form -->
                <form action="{{ route('quotations.update-status', $quotation) }}" method="POST" class="inline-flex items-center gap-1.5">
                    @csrf
                    @method('PATCH')
                    <label class="text-[11px] font-bold text-slate-500">Change Status:</label>
                    <select name="status" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded px-2 py-1 font-semibold focus:outline-none">
                        <option value="Draft" {{ $quotation->status === 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Sent" {{ $quotation->status === 'Sent' ? 'selected' : '' }}>Sent</option>
                        <option value="Approved" {{ $quotation->status === 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Invoiced" {{ $quotation->status === 'Invoiced' ? 'selected' : '' }}>Invoiced</option>
                        <option value="Cancelled" {{ $quotation->status === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
