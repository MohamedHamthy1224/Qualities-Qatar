@extends('layouts.app')

@section('title', 'Customer Payment Receipts')
@section('page_title', 'Customer Payment Receipts & Counter Collections')

@section('header_action')
    <a href="{{ route('payments.create') }}" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>+ Record Payment</span>
    </a>
@endsection

@section('content')
<div class="space-y-6 pt-2">

    <!-- Top Collections Summary Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Collected (QAR)</span>
                <div class="text-2xl font-extrabold text-emerald-700 font-mono-num">QAR {{ number_format($totalCollected, 2) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Collected Today</span>
                <div class="text-2xl font-extrabold text-slate-900 font-mono-num">QAR {{ number_format($todayCollected, 2) }}</div>
            </div>
            <div class="w-10 h-10 rounded-lg bg-sky-50 text-sky-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Accepted Payment Modes</span>
                <div class="text-xs text-slate-600 mt-1">Cash, Card POS (CBQ/QNB), Bank Wire & Cheque</div>
            </div>
            <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-2.5 py-1 rounded-md">
                Multi-Channel
            </span>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <form method="GET" action="{{ route('payments.index') }}" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <div class="relative flex-1 sm:w-72">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search receipt #, reference, customer..." class="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="method" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 focus:outline-none">
                <option value="">All Payment Methods</option>
                <option value="Cash" {{ request('method') === 'Cash' ? 'selected' : '' }}>Cash</option>
                <option value="Bank Transfer" {{ request('method') === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="Card" {{ request('method') === 'Card' ? 'selected' : '' }}>Card (POS Terminal)</option>
                <option value="Cheque" {{ request('method') === 'Cheque' ? 'selected' : '' }}>Cheque</option>
            </select>

            <button type="submit" class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800">
                Filter
            </button>
            @if(request()->hasAny(['search', 'method']))
                <a href="{{ route('payments.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            @endif
        </form>

        <a href="{{ route('payments.create') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
            <span>+ Log New Customer Payment</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
    </div>

    <!-- Receipts Table -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3">Receipt #</th>
                        <th class="px-4 py-3">Customer / Organization</th>
                        <th class="px-4 py-3">Allocated Invoice #</th>
                        <th class="px-4 py-3">Payment Date</th>
                        <th class="px-4 py-3">Method & Reference</th>
                        <th class="px-4 py-3 text-right">Amount Received (QAR)</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono-num">
                    @forelse($receipts as $receipt)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-bold text-[#0a192f]">
                                <a href="{{ route('payments.show', $receipt) }}" class="hover:underline">
                                    {{ $receipt->receipt_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-sans">
                                <a href="{{ route('customers.show', $receipt->customer_id) }}" class="font-bold text-slate-900 hover:underline">
                                    {{ $receipt->customer->name ?? 'Walk-in' }}
                                </a>
                                <div class="text-[11px] text-slate-500">{{ $receipt->customer->phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono-num">
                                @if($receipt->invoice)
                                    <a href="{{ route('invoices.show', $receipt->invoice_id) }}" class="text-[#0a192f] font-semibold hover:underline">
                                        {{ $receipt->invoice->invoice_number }}
                                    </a>
                                @else
                                    <span class="text-slate-400 font-sans">Auto-allocated</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 font-mono-num">
                                {{ $receipt->payment_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 font-sans">
                                <span class="font-bold text-slate-800">{{ $receipt->payment_method }}</span>
                                @if($receipt->reference_number)
                                    <div class="text-[11px] text-slate-500 font-mono-num">{{ $receipt->reference_number }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-extrabold text-emerald-700 text-sm">
                                QAR {{ number_format($receipt->amount_received, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-sans">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('payments.show', $receipt) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs">
                                        View Slip
                                    </a>
                                    <a href="{{ route('pdf.receipt', ['receipt' => $receipt, 'print' => 1]) }}" target="_blank" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs" title="Print Receipt">
                                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400 font-sans">
                                No payment receipts recorded yet. Click "+ Record Payment" to log counter collections.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
            <div class="p-4 border-t border-slate-100 font-sans">
                {{ $receipts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
