@extends('layouts.app')

@section('title', "Invoice {$invoice->invoice_number}")
@section('page_title', "Invoice {$invoice->invoice_number}")

@section('header_action')
    <div class="flex items-center gap-2">
        <a href="{{ route('invoices.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
            &larr; All Invoices
        </a>

        <!-- Thermal Receipt (80mm) -->
        <a href="{{ route('pdf.invoice', ['invoice' => $invoice, 'thermal' => 1]) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-300">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Thermal Slip (80mm)</span>
        </a>

        <!-- Standard Print View (A4) -->
        <a href="{{ route('pdf.invoice', ['invoice' => $invoice, 'print' => 1]) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-300">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print A4 Invoice</span>
        </a>

        <!-- PDF Download -->
        <a href="{{ route('pdf.invoice', $invoice) }}" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs">
            <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Download PDF</span>
        </a>

        <!-- Fast Record Payment -->
        @if($invoice->balance_due > 0)
            <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>+ Record Payment</span>
            </a>
        @endif

        <!-- Fast Create Delivery Note -->
        <a href="{{ route('delivery.create', ['invoice_id' => $invoice->id]) }}" class="inline-flex items-center gap-1.5 bg-[#0a192f] hover:bg-[#132a4a] text-[#d4af37] px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
            <span>+ Dispatch Delivery Note</span>
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6 pt-2 max-w-5xl mx-auto">

    <!-- Payment Status Alert Banner -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            @php
                $statusColor = match($invoice->status) {
                    'Paid' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                    'Partially Paid' => 'bg-amber-50 text-amber-800 border-amber-300',
                    'Unpaid' => 'bg-rose-50 text-rose-800 border-rose-300',
                    default => 'bg-slate-50 text-slate-700 border-slate-300'
                };
            @endphp
            <div class="px-3 py-1 rounded-lg text-xs font-extrabold border {{ $statusColor }}">
                Payment Status: {{ strtoupper($invoice->status) }}
            </div>
            <div class="text-xs text-slate-500">
                Invoice Total: <strong class="text-slate-900 font-mono-num">QAR {{ number_format($invoice->grand_total, 2) }}</strong>
            </div>
        </div>

        <div class="flex items-center gap-4 text-xs font-mono-num">
            <div>Paid: <strong class="text-emerald-700">QAR {{ number_format($invoice->paid_amount, 2) }}</strong></div>
            <div>Remaining Due: <strong class="{{ $invoice->balance_due > 0 ? 'text-rose-700 font-extrabold text-sm' : 'text-slate-500' }}">QAR {{ number_format($invoice->balance_due, 2) }}</strong></div>
        </div>
    </div>

    <!-- Official Invoice Document -->
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
                    <p class="text-[11px] text-slate-400">C.R. No: 128492 | Tel: +974 4455 1234 | billing@qualitiesqatar.com</p>
                </div>
            </div>

            <div class="text-left sm:text-right">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">OFFICIAL TAX INVOICE</div>
                <div class="text-2xl font-extrabold text-[#0a192f] font-mono-num">{{ $invoice->invoice_number }}</div>
                <div class="text-xs text-slate-500 font-mono-num mt-1">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Billed To & Payment Terms Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50/80 p-4 rounded-xl border border-slate-200/80 text-xs">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">BILLED TO CLIENT:</span>
                <div class="font-bold text-sm text-slate-900">{{ $invoice->customer->name }}</div>
                @if($invoice->customer->company_name)
                    <div class="font-semibold text-slate-700">{{ $invoice->customer->company_name }}</div>
                @endif
                <div class="text-slate-600 mt-1">Qatar Phone: <strong class="font-mono-num">{{ $invoice->customer->phone ?? '—' }}</strong></div>
                <div class="text-slate-600">Email: {{ $invoice->customer->email ?? '—' }}</div>
                <div class="text-slate-600">Location: {{ $invoice->customer->address ?? 'Doha, Qatar' }}</div>
            </div>

            <div class="sm:text-right space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">PAYMENT & ORDER REFERENCE:</span>
                <div>Invoice Date: <strong class="font-mono-num text-slate-900">{{ $invoice->invoice_date->format('d M Y') }}</strong></div>
                <div>Payment Due Date: <strong class="font-mono-num text-slate-900">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'Due on Handover' }}</strong></div>
                @if($invoice->quotation)
                    <div>Origin Quotation: <a href="{{ route('quotations.show', $invoice->quotation) }}" class="text-[#0a192f] font-mono-num font-bold hover:underline">{{ $invoice->quotation->quotation_number }}</a></div>
                @endif
                <div>Payment Mode: <span class="font-semibold text-slate-700">Bank Transfer / Card POS / Cash Counter</span></div>
            </div>
        </div>

        <!-- Itemized Size Breakdown Matrix -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-bold text-xs text-slate-900 uppercase tracking-wider">Itemized Uniform Size Breakdown Matrix</h3>
                <span class="text-xs text-slate-500 font-mono-num">Total Batch Units: <strong>{{ $invoice->total_qty }} pcs</strong></span>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2.5">Item Description</th>
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
                        @foreach($invoice->items as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2.5 font-sans">
                                    <div class="font-bold text-slate-900">{{ $item->product_name }}</div>
                                    @if($item->custom_size_notes)
                                        <div class="text-[11px] text-slate-500 font-normal">Custom spec: {{ $item->custom_size_notes }}</div>
                                    @endif
                                </td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_xs > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_xs }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_s > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_s }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_m > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_m }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_l > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_l }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_xl > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_xl }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_2xl > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_2xl }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_3xl > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_3xl }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_custom > 0 ? 'font-bold text-purple-700 bg-purple-50' : 'text-slate-300' }}">{{ $item->size_custom }}</td>
                                <td class="px-2 py-2.5 text-center bg-slate-50 font-bold text-slate-900">{{ $item->total_qty }}</td>
                                <td class="px-2 py-2.5 text-right">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-3 py-2.5 text-right font-extrabold text-slate-900 bg-slate-50">{{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notes and Financial Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
            <!-- Left: Bank & Wire Details -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs space-y-2">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Bank Transfer Information (Qatar)</span>
                <div><strong>Bank Name:</strong> Qatar National Bank (QNB) - Salwa Branch</div>
                <div><strong>Account Name:</strong> QUALITIES UNIFORMS & GIFTS W.L.L</div>
                <div><strong>IBAN:</strong> QA92 QNBA 0000 0000 1284 9200 11</div>
                <div><strong>SWIFT / BIC:</strong> QNBAQAQA</div>
                <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-200">Please quote invoice number <strong>{{ $invoice->invoice_number }}</strong> on wire transfers.</div>
            </div>

            <!-- Right: Totals, Paid & Balance Due -->
            <div class="bg-slate-50 rounded-xl border border-slate-200 p-5 space-y-2.5 text-xs font-mono-num">
                <div class="flex items-center justify-between text-slate-600 font-sans">
                    <span>Subtotal:</span>
                    <span class="font-mono-num font-bold text-slate-900">QAR {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->discount > 0)
                    <div class="flex items-center justify-between text-emerald-700 font-sans">
                        <span>Discount:</span>
                        <span class="font-mono-num font-bold text-emerald-700">- QAR {{ number_format($invoice->discount, 2) }}</span>
                    </div>
                @endif
                <div class="border-t border-slate-200 pt-2 flex items-center justify-between font-sans">
                    <span class="font-bold text-slate-900">Grand Total:</span>
                    <span class="text-base font-extrabold text-slate-900 font-mono-num">QAR {{ number_format($invoice->grand_total, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-emerald-700 font-sans">
                    <span>Amount Paid to Date:</span>
                    <span class="font-mono-num font-bold text-emerald-700">QAR {{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="border-t-2 border-slate-300 pt-2 flex items-center justify-between font-sans">
                    <span class="font-black text-slate-900 text-sm">Balance Due (QAR):</span>
                    <span class="text-xl font-black {{ $invoice->balance_due > 0 ? 'text-rose-700' : 'text-emerald-600' }} font-mono-num">
                        QAR {{ number_format($invoice->balance_due, 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Linked Payment Receipts Table -->
        @if($invoice->receipts->count() > 0)
            <div class="pt-4 border-t border-slate-200">
                <h4 class="font-bold text-xs text-slate-900 uppercase tracking-wider mb-2">Recorded Payment Receipts for this Invoice</h4>
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                            <tr>
                                <th class="px-3 py-2">Receipt #</th>
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Payment Method</th>
                                <th class="px-3 py-2">Reference / Notes</th>
                                <th class="px-3 py-2 text-right">Amount (QAR)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono-num">
                            @foreach($invoice->receipts as $r)
                                <tr>
                                    <td class="px-3 py-2 font-bold text-[#0a192f]">
                                        <a href="{{ route('payments.show', $r) }}" class="hover:underline">{{ $r->receipt_number }}</a>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $r->payment_date->format('d M Y') }}</td>
                                    <td class="px-3 py-2 font-sans font-semibold">{{ $r->payment_method }}</td>
                                    <td class="px-3 py-2 font-sans text-slate-500 text-[11px]">{{ $r->reference_number ?? $r->notes ?? 'Counter payment' }}</td>
                                    <td class="px-3 py-2 text-right font-extrabold text-emerald-700">QAR {{ number_format($r->amount_received, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>

</div>
@endsection
