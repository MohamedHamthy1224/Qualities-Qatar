@extends('layouts.app')

@section('title', 'Sales Invoices')
@section('page_title', 'Sales Invoices')

@section('header_action')
    <a href="{{ route('invoices.create') }}" class="inline-flex items-center gap-1.5 bg-[#0a192f] hover:bg-[#132a4a] text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs transition-all">
        <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Direct Invoice</span>
    </a>
@endsection

@section('content')
<div class="ui-page pt-2">

    <!-- Top Summary Banner -->
    <div class="ui-metric-grid gap-4">
        <div class="ui-metric-card min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="ui-metric-label text-slate-500">Total Invoiced (QAR)</span>
                <div class="ui-metric-value text-slate-900 font-mono-num [overflow-wrap:anywhere]">QAR {{ number_format($totalInvoiced, 2) }}</div>
            </div>
            <div class="ui-metric-icon w-10 h-10 shrink-0 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="ui-metric-card min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="ui-metric-label text-slate-500">Outstanding Balance (QAR)</span>
                <div class="ui-metric-value text-rose-700 font-mono-num [overflow-wrap:anywhere]">QAR {{ number_format($totalOutstanding, 2) }}</div>
            </div>
            <div class="ui-metric-icon w-10 h-10 shrink-0 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="ui-metric-card min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="ui-metric-label text-slate-500">Unpaid Invoices</span>
                <div class="ui-metric-value text-amber-700 font-mono-num [overflow-wrap:anywhere]">{{ $unpaidCount }}</div>
            </div>
            <div class="ui-metric-icon w-10 h-10 shrink-0 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="ui-filter-bar ui-filter-row bg-white rounded-xl border border-slate-200/90 shadow-xs">
        <form method="GET" action="{{ route('invoices.index') }}" class="ui-filter-form">
            <input type="hidden" name="per_page" value="{{ $invoices->perPage() }}">
            <div class="ui-filter-search relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoice #, customer name..." class="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="status" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 focus:outline-none">
                <option value="">All Payment Statuses</option>
                <option value="Paid" {{ request('status') === 'Paid' ? 'selected' : '' }}>Paid</option>
                <option value="Partially Paid" {{ request('status') === 'Partially Paid' ? 'selected' : '' }}>Partially Paid</option>
                <option value="Unpaid" {{ request('status') === 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
            </select>

            <button type="submit" class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('invoices.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            @endif
        </form>

        <a href="{{ route('payments.create') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center gap-1">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Record Payment Counter</span>
        </a>
    </div>

    <!-- Invoices List Table -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="ui-register-header no-print">
            <div class="ui-register-heading">
                <h2 class="ui-section-heading" id="invoices-register-title">Invoices</h2>
                <span class="ui-register-count">{{ $invoices->total() }} invoices</span>
            </div>
            <div class="ui-register-actions ui-page-actions">@yield('header_action')</div>
        </div>
        <div class="overflow-x-auto">
            <table class="ui-table w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3">Invoice #</th>
                        <th class="px-4 py-3">Customer / Organization</th>
                        <th class="px-4 py-3">Invoice Date</th>
                        <th class="px-4 py-3 text-center">Total Qty</th>
                        <th class="px-4 py-3 text-right">Grand Total</th>
                        <th class="px-4 py-3 text-right">Balance Due (QAR)</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="ui-table-primary px-4 py-3 font-mono-num font-bold text-[#0a192f]">
                                <a href="{{ route('invoices.show', $inv) }}" class="hover:underline">
                                    {{ $inv->invoice_number }}
                                </a>
                                @if($inv->quotation)
                                    <div class="ui-table-secondary text-[10px] text-slate-400">From {{ $inv->quotation->quotation_number }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="{{ route('customers.show', $inv->customer_id) }}" class="ui-table-primary font-bold text-slate-900 hover:underline">
                                        {{ $inv->customer->name ?? 'Walk-in' }}
                                    </a>
                                    @if($inv->customer && $inv->customer->customer_code)
                                        <span class="ui-badge px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono-num font-bold text-[10px] border border-slate-200">
                                            {{ $inv->customer->customer_code }}
                                        </span>
                                    @endif
                                </div>
                                <div class="ui-table-secondary text-[11px] text-slate-500">{{ $inv->customer->phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 font-mono-num">
                                <div>{{ $inv->invoice_date->format('d M Y') }}</div>
                                @if($inv->due_date)
                                    <div class="ui-table-secondary text-[10px] {{ $inv->due_date < now() && $inv->balance_due > 0 ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                        Due: {{ $inv->due_date->format('d M Y') }}
                                    </div>
                                @endif
                            </td>
                            <td class="ui-table-primary px-4 py-3 text-center font-mono-num font-bold text-slate-800">
                                {{ $inv->total_qty }} units
                            </td>
                            <td class="ui-table-primary px-4 py-3 text-right font-mono-num font-bold text-slate-900">
                                QAR {{ number_format($inv->grand_total, 2) }}
                            </td>
                            <td class="ui-table-primary px-4 py-3 text-right font-mono-num font-extrabold {{ $inv->balance_due > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                QAR {{ number_format($inv->balance_due, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusPill = match($inv->status) {
                                        'Paid' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                        'Partially Paid' => 'bg-amber-100 text-amber-800 border-amber-300',
                                        'Unpaid' => 'bg-rose-100 text-rose-800 border-rose-300',
                                        default => 'bg-slate-100 text-slate-700'
                                    };
                                @endphp
                                <span class="ui-badge inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $statusPill }}">
                                    {{ $inv->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('invoices.show', $inv) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs">
                                        View
                                    </a>
                                    <a href="{{ route('pdf.invoice', ['invoice' => $inv, 'print' => 1]) }}" target="_blank" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs" title="Print A4 Invoice">
                                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>
                                    @if($inv->balance_due > 0)
                                        <a href="{{ route('payments.create', ['invoice_id' => $inv->id]) }}" class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs" title="Record payment">
                                            Pay
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="ui-help px-4 py-8 text-center text-slate-400">
                                No sales invoices found. Convert an approved quotation or create a new invoice.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-table-pagination :paginator="$invoices" label="invoices" per-page-name="per_page" />
    </div>

</div>
@endsection
