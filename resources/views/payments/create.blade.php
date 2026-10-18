@extends('layouts.app')

@section('title', 'Record Customer Payment')
@section('page_title', 'Record Payment')

@section('header_action')
    <a href="{{ route('payments.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
        &larr; Back to Receipts
    </a>
@endsection

@section('content')
@php
    $openInvoices = ($selectedCustomer?->invoices ?? collect())->where('balance_due', '>', 0);
    $paymentInvoice = $openInvoices->firstWhere('id', old('invoice_id', $selectedInvoice?->id));
    $openBalance = $openInvoices->sum(fn ($invoice) => (int) round((float) $invoice->balance_due * 100)) / 100;
    $paymentBalance = $paymentInvoice ? (float) $paymentInvoice->balance_due : $openBalance;
@endphp
<div class="ui-page pt-2 max-w-4xl mx-auto">

    <form id="paymentForm" action="{{ route('payments.store') }}" method="POST" class="ui-page" data-customer-balance="{{ number_format($openBalance, 2, '.', '') }}">
        @csrf

        <!-- Payment Logging Card -->
        <div class="ui-section ui-page bg-white rounded-xl border border-slate-200/90 shadow-xs">
            <div class="ui-form-back no-print">@yield('header_action')</div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <h2 class="ui-section-heading text-slate-900">Payment details</h2>
                </div>
                <div class="ui-help text-slate-500">
                    Currency: <strong class="text-slate-900">QAR (Qatari Riyal)</strong>
                </div>
            </div>

            <!-- Customer Selection & Live Outstanding Balance Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label for="{{ $customerLocked ? 'selectedCustomerName' : 'customerSelect' }}" class="ui-field-label block text-slate-700">Customer *</label>
                        @if($customerLocked)
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span>Selected customer</span>
                            </span>
                        @else
                            @section('has_customer_add_action', '1')
                            <button type="button" onclick="openQuickCustomerModal()" class="text-[11px] font-bold text-[#0a192f] hover:text-[#d4af37] underline">
                                + Quick Add Customer
                            </button>
                        @endif
                    </div>
                    @if($customerLocked)
                        <div class="relative">
                            <input type="text" id="selectedCustomerName" readonly value="{{ $selectedCustomer->name }} {{ $selectedCustomer->company_name ? "({$selectedCustomer->company_name})" : '' }} [{{ $selectedCustomer->customer_code }}]" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg bg-slate-100 text-slate-800 font-bold cursor-not-allowed focus:outline-none shadow-xs">
                            <input type="hidden" name="customer_id" id="customerSelect" value="{{ $selectedCustomer->id }}">
                        </div>
                    @else
                        <select name="customer_id" id="customerSelect" required class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                            <option value="">-- Choose Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('customer_id', $selectedCustomer?->id) == $c->id) data-balance="{{ $c->balance }}">
                                    {{ $c->name }} {{ $c->company_name ? "({$c->company_name})" : '' }} [{{ $c->customer_code }}]
                                </option>
                            @endforeach
                        </select>
                    @endif
                    @error('customer_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Live Balance Badge -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex flex-col justify-center" role="status" aria-live="polite" aria-atomic="true">
                    <span class="ui-field-label text-slate-500" id="paymentBalanceLabel">{{ $paymentInvoice ? 'Selected invoice balance' : 'Total open invoice balance' }}</span>
                    <div class="text-xl font-extrabold text-rose-700 font-mono-num mt-1" id="customerBalanceDisplay">
                        QAR {{ number_format($paymentBalance, 2) }}
                    </div>
                </div>
            </div>

            <!-- Unpaid Invoices Auto-Load Box -->
            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                <label for="invoiceSelect" class="ui-field-label block text-slate-800 mb-1">Invoice (optional)</label>
                <select name="invoice_id" id="invoiceSelect" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                    <option value="">-- Auto-Allocate against oldest open balance --</option>
                    @if(isset($selectedCustomer))
                        @foreach($openInvoices as $inv)
                            <option value="{{ $inv->id }}" @selected(old('invoice_id', $selectedInvoice?->id) == $inv->id) data-due="{{ $inv->balance_due }}">
                                {{ $inv->invoice_number }} (Due: QAR {{ number_format($inv->balance_due, 2) }} of {{ number_format($inv->grand_total, 2) }}) — Date: {{ $inv->invoice_date->format('d M Y') }}
                            </option>
                        @endforeach
                    @endif
                </select>
                <p class="ui-help text-slate-500 mt-1">Leave blank to pay the oldest open invoices. The amount cannot exceed the available balance.</p>
                <p id="invoiceLoadError" role="alert" class="hidden text-xs text-rose-600 mt-1"></p>
                @error('invoice_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <!-- Receipt Info Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                <!-- Receipt Number -->
                <div>
                    <label for="receiptNumber" class="ui-field-label block text-slate-700 mb-1">Receipt Number *</label>
                    <input type="text" name="receipt_number" id="receiptNumber" value="{{ old('receipt_number', $suggestedNumber) }}" maxlength="40" required class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono-num font-bold text-[#0a192f] bg-slate-50 focus:outline-none">
                    @error('receipt_number')<p class="text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Amount Received -->
                <div>
                    <label for="amountReceived" class="ui-field-label block text-slate-700 mb-1">Amount Received (QAR) *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 font-bold text-slate-400">QAR</span>
                        <input type="number" step="0.01" min="0.01" max="{{ number_format($paymentBalance, 2, '.', '') }}" name="amount_received" id="amountReceived" value="{{ old('amount_received', $paymentInvoice?->balance_due) }}" required placeholder="0.00" class="w-full pl-12 pr-3 py-2 border border-slate-300 rounded-lg font-mono-num font-extrabold text-emerald-800 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                    </div>
                    @error('amount_received')<p class="text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Payment Date -->
                <div>
                    <label for="paymentDate" class="ui-field-label block text-slate-700 mb-1">Payment Date *</label>
                    <input type="date" name="payment_date" id="paymentDate" value="{{ old('payment_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>

                <!-- Payment Method -->
                <div>
                    <label for="paymentMethod" class="ui-field-label block text-slate-700 mb-1">Payment Method *</label>
                    <select name="payment_method" id="paymentMethod" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                        <option value="Cash" @selected(old('payment_method', 'Cash') === 'Cash')>Cash</option>
                        <option value="Card" @selected(old('payment_method') === 'Card')>Card</option>
                        <option value="Bank Transfer" @selected(old('payment_method') === 'Bank Transfer')>Bank Transfer</option>
                        <option value="Cheque" @selected(old('payment_method') === 'Cheque')>Cheque</option>
                    </select>
                </div>

                <!-- Reference / Transaction No -->
                <div class="sm:col-span-2">
                    <label for="paymentReference" class="ui-field-label block text-slate-700 mb-1">Reference / Transaction / Cheque #</label>
                    <input type="text" name="reference_number" id="paymentReference" value="{{ old('reference_number') }}" maxlength="100" placeholder="Transaction reference or cheque number" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="paymentNotes" class="ui-field-label block text-slate-700 mb-1">Payment notes</label>
                <textarea name="notes" id="paymentNotes" rows="2" placeholder="Payment details or collection instructions" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 border-t border-slate-200 flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('payments.index') }}" class="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-lg text-xs font-extrabold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Record Payment & Generate Receipt</span>
                </button>
            </div>

        </div>

    </form>

</div>

@endsection
