@extends('layouts.app')

@section('title', "Invoice {$invoice->invoice_number}")
@section('page_title', "Invoice {$invoice->invoice_number}")

@section('header_action')
    <div class="flex flex-wrap items-center gap-2">
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
                <span>Record Payment</span>
            </a>
        @endif

        <!-- Fast Create Delivery Note -->
        <a href="{{ route('delivery.create', ['invoice_id' => $invoice->id]) }}" class="inline-flex items-center gap-1.5 bg-[#0a192f] hover:bg-[#132a4a] text-[#d4af37] px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
            <span>Dispatch Delivery Note</span>
        </a>
    </div>
@endsection

@section('content')
<div class="ui-page pt-2 max-w-5xl mx-auto">

    <!-- Payment Status Alert Banner -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
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

        <div class="flex flex-wrap items-center gap-4 text-xs font-mono-num">
            <div>Paid: <strong class="text-emerald-700">QAR {{ number_format($invoice->paid_amount, 2) }}</strong></div>
            <div>Remaining Due: <strong class="{{ $invoice->balance_due > 0 ? 'text-rose-700 font-extrabold text-sm' : 'text-slate-500' }}">QAR {{ number_format($invoice->balance_due, 2) }}</strong></div>
        </div>
    </div>

    <!-- Official Invoice Document -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm ui-section ui-page">
        <div class="ui-record-actions no-print">@yield('header_action')</div>

        <!-- Branded Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            @include('documents.company')


            <div class="text-left sm:text-right">
                <div class="ui-field-label text-slate-500">Sales invoice</div>
                <div class="text-2xl font-extrabold text-[#0a192f] font-mono-num">{{ $invoice->invoice_number }}</div>
                <div class="text-xs text-slate-500 font-mono-num mt-1">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Billed To & Payment Terms Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50/80 p-4 rounded-xl border border-slate-200/80 text-xs">
            <div>
                <span class="ui-field-label text-slate-500 block mb-1">Billed to</span>
                <div class="font-bold text-sm text-slate-900">{{ $invoice->customer->name }}</div>
                @if($invoice->customer->company_name)
                    <div class="font-semibold text-slate-700">{{ $invoice->customer->company_name }}</div>
                @endif
                <div class="text-slate-600 mt-1">Qatar Phone: <strong class="font-mono-num">{{ $invoice->customer->phone ?? '—' }}</strong></div>
                <div class="text-slate-600">Email: {{ $invoice->customer->email ?? '—' }}</div>
                <div class="text-slate-600">Location: {{ $invoice->customer->address ?? 'Doha, Qatar' }}</div>
            </div>

            <div class="sm:text-right space-y-1">
                <span class="ui-field-label text-slate-500 block mb-1">Payment & order details</span>
                <div>Invoice Date: <strong class="font-mono-num text-slate-900">{{ $invoice->invoice_date->format('d M Y') }}</strong></div>
                <div>Payment Due Date: <strong class="font-mono-num text-slate-900">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'Not specified' }}</strong></div>
                @if($invoice->quotation)
                    <div>Origin Quotation: <a href="{{ route('quotations.show', $invoice->quotation) }}" class="text-[#0a192f] font-mono-num font-bold hover:underline">{{ $invoice->quotation->quotation_number }}</a></div>
                @endif
                @if($invoice->productionOrder)
                    <div>Production: <a class="font-semibold underline" href="{{ route('production.index', ['search' => $invoice->productionOrder->order_number]) }}">{{ $invoice->productionOrder->stage_label }}</a></div>
                @endif
            </div>
        </div>

        <!-- Itemized Size Breakdown Matrix -->
        <div>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <h3 class="ui-section-heading text-slate-900">Items & sizes</h3>
                <span class="ui-help text-slate-500 font-mono-num">Total quantity: <strong>{{ $invoice->total_qty }} units</strong></span>
            </div>

            <div data-paginated-table-region class="border border-slate-200 rounded-lg">
                @include('documents.items', ['items' => $invoice->items])
            </div>
        </div>

        <!-- Notes and Financial Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
            <!-- Agreed notes and terms -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs space-y-2">
                <span class="ui-field-label text-slate-500 block">Notes & terms</span>
                @if($invoice->notes)<p class="whitespace-pre-line break-words">{{ $invoice->notes }}</p>@endif
                @if(config('company.invoice_terms'))<p class="whitespace-pre-line break-words">{{ config('company.invoice_terms') }}</p>@endif
                <p class="text-slate-500">Payment reference: <strong>{{ $invoice->invoice_number }}</strong></p>
            </div>

            <!-- Right: Totals, Paid & Balance Due -->
            <div class="ui-section bg-slate-50 rounded-xl border border-slate-200 space-y-2.5 text-xs font-mono-num">
                <div class="flex items-center justify-between text-slate-600 font-sans">
                    <span>Subtotal:</span>
                    <span class="font-mono-num font-bold text-slate-900">QAR {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->setup_charges > 0)
                    <div class="flex items-center justify-between"><span>Additional Setup:</span><span>QAR {{ number_format($invoice->setup_charges, 2) }}</span></div>
                @endif
                @if($invoice->print_charges > 0)
                    <div class="flex items-center justify-between"><span>Additional Printing:</span><span>QAR {{ number_format($invoice->print_charges, 2) }}</span></div>
                @endif
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
                <div class="ui-total-row border-t-2 border-slate-300 pt-2 font-sans">
                    <span class="ui-total-label text-slate-900">Balance Due:</span>
                    <span class="ui-total-value {{ $invoice->balance_due > 0 ? 'text-rose-700' : 'text-emerald-600' }} font-mono-num">
                        QAR {{ number_format($invoice->balance_due, 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Linked Payment Receipts Table -->
        @if($invoice->receipts->count() > 0)
            <div class="pt-4 border-t border-slate-200">
                <h4 class="ui-section-heading text-slate-900 mb-2">Payment receipts</h4>
                <div data-paginated-table-region class="border border-slate-200 rounded-lg">
                    <div class="overflow-x-auto">
                    <table id="invoiceReceiptsTable" data-paginate-table data-pagination-label="payment receipts" class="ui-table w-full min-w-[600px] text-left">
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
            </div>
        @endif

    </div>

</div>
@endsection
