@extends('layouts.app')

@section('title', 'Delivery Notes & Dispatch')
@section('page_title', 'Delivery Notes')

@section('header_action')
    <a href="{{ route('delivery.create') }}" class="inline-flex items-center gap-1.5 bg-[#0a192f] hover:bg-[#132a4a] text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs transition-all">
        <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Delivery Note</span>
    </a>
@endsection

@section('content')
<div class="ui-page pt-2">

    <!-- Top Dispatch Status Banner -->
    <div class="ui-metric-grid gap-4">
        <div class="ui-metric-card min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="ui-metric-label text-slate-500">Pending Dispatch</span>
                <div class="ui-metric-value text-amber-700 font-mono-num [overflow-wrap:anywhere]">{{ $pendingCount }}</div>
            </div>
            <div class="ui-metric-icon w-10 h-10 shrink-0 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="ui-metric-card min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="ui-metric-label text-slate-500">Out for Delivery</span>
                <div class="ui-metric-value text-sky-700 font-mono-num [overflow-wrap:anywhere]">{{ $dispatchedCount }}</div>
            </div>
            <div class="ui-metric-icon w-10 h-10 shrink-0 rounded-lg bg-sky-50 text-sky-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
            </div>
        </div>

        <div class="ui-metric-card min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="ui-metric-label text-slate-500">Handed Over / Delivered</span>
                <div class="ui-metric-value text-emerald-700 font-mono-num [overflow-wrap:anywhere]">{{ $deliveredCount }}</div>
            </div>
            <div class="ui-metric-icon w-10 h-10 shrink-0 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="ui-filter-bar ui-filter-row bg-white rounded-xl border border-slate-200/90 shadow-xs">
        <form method="GET" action="{{ route('delivery.index') }}" class="ui-filter-form">
            <input type="hidden" name="per_page" value="{{ $deliveryNotes->perPage() }}">
            <div class="ui-filter-search relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search delivery #, recipient, customer..." class="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="status" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 focus:outline-none">
                <option value="">All Delivery Statuses</option>
                <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Dispatched" {{ request('status') === 'Dispatched' ? 'selected' : '' }}>Dispatched</option>
                <option value="Delivered" {{ request('status') === 'Delivered' ? 'selected' : '' }}>Delivered</option>
            </select>

            <button type="submit" class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('delivery.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            @endif
        </form>

    </div>

    <!-- Delivery Notes Table -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="ui-register-header no-print">
            <div class="ui-register-heading">
                <h2 class="ui-section-heading" id="delivery-register-title">Delivery notes</h2>
                <span class="ui-register-count">{{ $deliveryNotes->total() }} delivery notes</span>
            </div>
            <div class="ui-register-actions ui-page-actions">@yield('header_action')</div>
        </div>
        <div class="overflow-x-auto">
            <table class="ui-table w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3">Delivery Note #</th>
                        <th class="px-4 py-3">Customer / Organization</th>
                        <th class="px-4 py-3">Sales Invoice</th>
                        <th class="px-4 py-3">Dispatch Date</th>
                        <th class="px-4 py-3">Recipient & Driver</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($deliveryNotes as $dn)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="ui-table-primary px-4 py-3 font-mono-num font-bold text-[#0a192f]">
                                <a href="{{ route('delivery.show', $dn) }}" class="hover:underline">
                                    {{ $dn->delivery_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('customers.show', $dn->customer_id) }}" class="ui-table-primary font-bold text-slate-900 hover:underline">
                                    {{ $dn->customer->name ?? 'Walk-in' }}
                                </a>
                                <div class="ui-table-secondary text-[11px] text-slate-500">{{ $dn->recipient_phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono-num">
                                <a href="{{ route('invoices.show', $dn->invoice_id) }}" class="ui-table-primary text-[#0a192f] font-semibold hover:underline">
                                    {{ $dn->invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-slate-600 font-mono-num">
                                {{ $dn->dispatch_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="ui-table-primary font-bold text-slate-800">{{ $dn->recipient_name }}</div>
                                <div class="ui-table-secondary text-[11px] text-slate-500">Driver: {{ $dn->driver_name ?? 'Delivery Van' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $dnStyle = match($dn->status) {
                                        'Delivered' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                        'Dispatched' => 'bg-sky-100 text-sky-800 border-sky-300',
                                        'Pending' => 'bg-amber-100 text-amber-800 border-amber-300',
                                        default => 'bg-slate-100 text-slate-700'
                                    };
                                @endphp
                                <span class="ui-badge inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $dnStyle }}">
                                    {{ $dn->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('delivery.show', $dn) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs">
                                        View Note
                                    </a>
                                    <a href="{{ route('pdf.delivery', ['deliveryNote' => $dn, 'print' => 1]) }}" target="_blank" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs" title="Print Delivery Handover Sign-off Sheet">
                                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="ui-help px-4 py-8 text-center text-slate-400">
                                No delivery notes created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-table-pagination :paginator="$deliveryNotes" label="delivery notes" per-page-name="per_page" />
    </div>

</div>
@endsection
