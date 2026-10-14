@extends('layouts.app')

@section('title', "Payment Receipt {$receipt->receipt_number}")
@section('page_title', "Payment Receipt {$receipt->receipt_number}")

@section('header_action')
    <div class="flex items-center gap-2">
        <a href="{{ route('payments.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
            &larr; All Receipts
        </a>

        <!-- Print View -->
        <a href="{{ route('pdf.receipt', ['receipt' => $receipt->id, 'print' => 1]) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-300">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print Receipt</span>
        </a>

        <!-- PDF Download -->
        <a href="{{ route('pdf.receipt', ['receipt' => $receipt->id]) }}" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs">
            <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Download PDF</span>
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6 pt-2 max-w-3xl mx-auto">

    <!-- Official Receipt Document -->
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
                    <p class="text-[11px] text-slate-400">Official Official Cashier Counter Voucher</p>
                </div>
            </div>

            <div class="text-left sm:text-right">
                <div class="text-xs font-bold text-emerald-700 uppercase tracking-widest">OFFICIAL PAYMENT RECEIPT</div>
                <div class="text-2xl font-extrabold text-[#0a192f] font-mono-num">{{ $receipt->receipt_number }}</div>
                <div class="text-xs text-slate-500 font-mono-num mt-1">Date: {{ $receipt->payment_date->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Payment Highlight Box -->
        <div class="bg-gradient-to-r from-emerald-50 via-teal-50/50 to-emerald-50 border border-emerald-200 rounded-xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">AMOUNT RECEIVED IN FULL CONFIRMATION</span>
                <div class="text-xs text-slate-600 mt-0.5">Method: <strong class="text-slate-900">{{ $receipt->payment_method }}</strong> @if($receipt->reference_number) (Ref: {{ $receipt->reference_number }}) @endif</div>
            </div>
            <div class="text-right">
                <span class="text-xs font-bold text-emerald-700">QAR</span>
                <span class="text-3xl sm:text-4xl font-extrabold font-mono-num text-emerald-800">
                    {{ number_format($receipt->amount_received, 2) }}
                </span>
            </div>
        </div>

        <!-- Receipt Details Table -->
        <div class="bg-slate-50/80 rounded-xl border border-slate-200/80 p-4 space-y-3 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Received From:</span>
                    <div class="font-bold text-sm text-slate-900">{{ $receipt->customer->name }}</div>
                    @if($receipt->customer->company_name)
                        <div class="text-slate-700">{{ $receipt->customer->company_name }}</div>
                    @endif
                    <div class="text-slate-500 text-[11px] mt-0.5">{{ $receipt->customer->phone }} • {{ $receipt->customer->address }}</div>
                </div>

                <div class="sm:text-right space-y-1">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Invoice Allocation:</span>
                    @if($receipt->invoice)
                        <div>Allocated Invoice: <a href="{{ route('invoices.show', $receipt->invoice_id) }}" class="font-mono-num font-bold text-[#0a192f] hover:underline">{{ $receipt->invoice->invoice_number }}</a></div>
                        <div>Original Invoice Total: <span class="font-mono-num">QAR {{ number_format($receipt->invoice->grand_total, 2) }}</span></div>
                        <div>Current Balance Remaining: <span class="font-mono-num font-extrabold {{ $receipt->invoice->balance_due > 0 ? 'text-rose-600' : 'text-emerald-600' }}">QAR {{ number_format($receipt->invoice->balance_due, 2) }}</span></div>
                    @else
                        <div class="text-slate-600">Auto-allocated to account balance</div>
                    @endif
                </div>
            </div>

            @if($receipt->notes)
                <div class="pt-3 border-t border-slate-200">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Counter Notes:</span>
                    <p class="text-slate-700">{{ $receipt->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Cashier Sign & Stamp -->
        <div class="pt-6 border-t border-slate-200 grid grid-cols-2 gap-8 text-xs text-slate-500">
            <div>
                <div>Issued By: <strong>QUALITIES Billing Cashier Desk</strong></div>
                <div class="text-[11px] text-slate-400 mt-1">Doha Salwa Road Counter</div>
            </div>
            <div class="text-right">
                <div class="inline-block border-2 border-emerald-600/60 rounded-lg px-4 py-2 text-emerald-800 font-extrabold text-[11px] tracking-wider uppercase rotate-[-2deg]">
                    PAYMENT RECEIVED • VERIFIED
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
