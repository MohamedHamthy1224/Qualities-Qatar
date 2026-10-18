@extends('layouts.app')

@section('title', "Payment Receipt {$receipt->receipt_number}")
@section('page_title', "Payment Receipt {$receipt->receipt_number}")

@section('header_action')
    <div class="flex flex-wrap items-center gap-2">
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
@php
    $paymentGroup = $receipt->paymentGroup();
    $mainReceipt = $paymentGroup->first();
@endphp
<div class="ui-page pt-2 max-w-3xl mx-auto">

    <!-- Official Receipt Document -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm ui-section ui-page">
        <div class="ui-record-actions no-print">@yield('header_action')</div>

        <!-- Branded Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            @include('documents.company')


            <div class="text-left sm:text-right">
                <div class="ui-field-label text-emerald-700">Payment receipt</div>
                <div class="text-2xl font-extrabold text-[#0a192f] font-mono-num break-all">{{ $mainReceipt->receipt_number }}</div>
                <div class="text-xs text-slate-500 font-mono-num mt-1">Date: {{ $receipt->payment_date->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Payment Highlight Box -->
        <div class="ui-section bg-emerald-50 border border-emerald-200 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="min-w-0 sm:flex-1">
                <span class="ui-field-label text-emerald-800 block">Payment received</span>
                <div class="ui-help text-slate-600 mt-0.5 [overflow-wrap:anywhere]">Method: <strong class="text-slate-900">{{ $receipt->payment_method }}</strong> @if($receipt->reference_number) (Ref: {{ $receipt->reference_number }}) @endif</div>
            </div>
            <div class="min-w-0 max-w-full text-right">
                <span class="text-xs font-bold text-emerald-700">QAR</span>
                <span class="text-3xl sm:text-4xl font-extrabold font-mono-num text-emerald-800 [overflow-wrap:anywhere]">
                    {{ number_format($paymentGroup->sum('amount_received'), 2) }}
                </span>
            </div>
        </div>

        <!-- Receipt Details Table -->
        <div class="bg-slate-50/80 rounded-xl border border-slate-200/80 p-4 space-y-3 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="min-w-0 [overflow-wrap:anywhere]">
                    <span class="ui-field-label text-slate-500 block">Received from</span>
                    <div class="font-bold text-sm text-slate-900">{{ $receipt->customer->name }}</div>
                    @if($receipt->customer->company_name)
                        <div class="text-slate-700">{{ $receipt->customer->company_name }}</div>
                    @endif
                    <div class="ui-help text-slate-500 mt-0.5">{{ $receipt->customer->phone }} • {{ $receipt->customer->address }}</div>
                </div>

                <div class="min-w-0 sm:text-right space-y-1 [overflow-wrap:anywhere]">
                    <span class="ui-field-label text-slate-500 block">Invoice allocation</span>
                    @foreach($paymentGroup as $allocation)
                        <div class="py-1">
                            @if($allocation->invoice)
                                <a href="{{ route('invoices.show', $allocation->invoice_id) }}" class="font-mono-num font-bold text-[#0a192f] hover:underline">{{ $allocation->invoice->invoice_number }}</a>
                                <span class="font-mono-num">— QAR {{ number_format($allocation->amount_received, 2) }}</span>
                                <div class="ui-help text-slate-500">Invoice balance: QAR {{ number_format($allocation->invoice->balance_due, 2) }}</div>
                            @else
                                <div>Account payment: QAR {{ number_format($allocation->amount_received, 2) }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            @if($receipt->notes)
                <div class="pt-3 border-t border-slate-200">
                    <span class="ui-field-label text-slate-500 block mb-0.5">Payment notes</span>
                    <p class="text-slate-700 [overflow-wrap:anywhere]">{{ $receipt->notes }}</p>
                </div>
            @endif
        </div>

        <!-- Cashier Sign & Stamp -->
        <div class="pt-4 border-t border-slate-200 grid grid-cols-2 gap-8 text-xs text-slate-500">
            <div>
                <div>Issued By: <strong>{{ config('company.name') }}</strong></div>
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
