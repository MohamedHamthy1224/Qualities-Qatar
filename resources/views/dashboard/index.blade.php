@extends('layouts.app')

@section('title', 'Operations Dashboard')
@section('page_title', 'Operations Dashboard')

@section('content')
<div class="erp-dashboard">

    <div class="future-dashboard-top">
        <div class="future-dashboard-intro">
            <div class="future-dashboard-intro-copy">
                <p class="future-dashboard-eyebrow">Operations &amp; billing</p>
                <h2>Workspace overview</h2>
                <p class="future-dashboard-subtitle">Production, quotations and receivables at a glance.</p>
            </div>
        </div>

    <!-- Top Operational Metrics Summary Cards -->
    <div class="dashboard-metrics" aria-label="Operations summary">
        <!-- 1. Ongoing production jobs -->
        <div data-metric="production" class="dashboard-metric ui-metric-card bg-white rounded-xl border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="ui-metric-label text-slate-500 hover:text-sky-700">Ongoing Production</a>
                <span class="ui-metric-icon w-8 h-8 rounded-lg bg-sky-50 text-sky-700 flex items-center justify-center font-bold text-xs border border-sky-100">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="ui-metric-value text-slate-900 font-mono-num">{{ $activeOrdersCount }}</div>
                <a href="{{ route('dashboard', ['production_status' => 'completed']) }}" class="ui-help inline-block text-emerald-700 mt-1 hover:underline">{{ $completedOrdersCount }} production complete</a>
            </div>
        </div>

        <!-- 2. Pending Quotations -->
        <div data-metric="quotations" class="dashboard-metric ui-metric-card bg-white rounded-xl border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="ui-metric-label text-slate-500">Pending Quotations</span>
                <span class="ui-metric-icon w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-xs border border-amber-100">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="ui-metric-value text-slate-900 font-mono-num">{{ $pendingQuotationsCount }}</div>
                <div class="ui-help text-slate-500 mt-1 flex items-center gap-1">
                    <span>Draft and sent quotations</span>
                </div>
            </div>
        </div>

        <!-- 3. Ready Deliveries -->
        <div data-metric="delivery" class="dashboard-metric ui-metric-card bg-white rounded-xl border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="ui-metric-label text-slate-500">Ready for Delivery</span>
                <span class="ui-metric-icon w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs border border-emerald-100">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="ui-metric-value text-slate-900 font-mono-num">{{ $readyDeliveriesCount }}</div>
                <div class="ui-help text-slate-500 mt-1 flex items-center gap-1">
                    <a href="{{ route('production.index', ['stage' => 'Ready for Delivery']) }}" class="text-emerald-600 font-semibold hover:underline">Ready to dispatch</a>
                </div>
            </div>
        </div>

        <!-- 4. Overdue Payments (in QAR) -->
        <div data-metric="receivables" class="dashboard-metric ui-metric-card bg-white rounded-xl border border-rose-200/80 shadow-xs hover:border-rose-300 transition-all flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="ui-metric-label text-rose-700">Overdue Payments</span>
                <span class="ui-metric-icon w-8 h-8 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center font-bold text-xs border border-rose-100">
                    <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="ui-metric-value text-rose-900 font-mono-num">
                    <span class="dashboard-currency">QAR</span><span>{{ number_format($overduePaymentsQAR, 2) }}</span>
                </div>
                <div class="ui-help text-rose-700 mt-1">
                    Total Outstanding: QAR {{ number_format($totalReceivablesQAR, 2) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Launch Bar for Billing & Counter Operators -->
    <section id="dashboardQuickActions" aria-labelledby="counter-billing-title" class="dashboard-actions">
        <div class="dashboard-action-label flex items-center gap-2.5 shrink-0">
            <div class="w-8 h-8 shrink-0 rounded-lg bg-[#d4af37]/20 border border-[#d4af37]/40 flex items-center justify-center text-[#d4af37]">
                <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <h2 id="counter-billing-title" class="ui-section-heading text-white">Counter Billing</h2>
        </div>
        <div class="dashboard-action-grid">
            <!-- New Quotation -->
            <a href="{{ route('quotations.create') }}" class="dashboard-action dashboard-action-primary">
                <svg aria-hidden="true" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Quotation</span>
            </a>
            <!-- New Invoice -->
            <a href="{{ route('invoices.create') }}" class="dashboard-action dashboard-action-secondary">
                <svg aria-hidden="true" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>New Invoice</span>
            </a>
            <!-- Record Payment -->
            <a href="{{ route('payments.create') }}" class="dashboard-action dashboard-action-secondary">
                <svg aria-hidden="true" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Record Payment</span>
            </a>
        </div>
    </section>
    </div>

    @include('dashboard.partials.production-charts')

    <!-- Production jobs, with explicit ongoing/completed views -->
    <div class="dashboard-content-grid">
        <!-- Recent Active Orders (2 Columns) -->
        <div class="dashboard-jobs dashboard-panel min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs">
            <div class="ui-panel-header border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="ui-section-heading text-slate-900">{{ $productionStatus === 'completed' ? 'Completed Production Jobs' : 'Ongoing Production Jobs' }}</h2>
                    <p class="ui-help text-slate-500">Delivery and payments are tracked separately.</p>
                </div>
                <a href="{{ route('production.index', ['status' => $productionStatus]) }}" class="text-xs font-bold text-[#0a192f] hover:text-[#d4af37] flex items-center gap-1">
                    <span>Production Board</span>
                    <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <nav aria-label="Production job status" class="dashboard-status-tabs flex gap-2 px-4 py-3 border-b border-slate-100">
                <a href="{{ route('dashboard') }}" @if($productionStatus === 'ongoing') aria-current="page" @endif class="dashboard-status-tab rounded-lg px-3 py-1.5 text-xs font-bold {{ $productionStatus === 'ongoing' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">Ongoing ({{ $activeOrdersCount }})</a>
                <a href="{{ route('dashboard', ['production_status' => 'completed']) }}" @if($productionStatus === 'completed') aria-current="page" @endif class="dashboard-status-tab rounded-lg px-3 py-1.5 text-xs font-bold {{ $productionStatus === 'completed' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">Production complete ({{ $completedOrdersCount }})</a>
            </nav>

            <div class="dashboard-table-wrap">
                <table class="dashboard-jobs-table ui-table w-full text-left" role="table" aria-label="Production jobs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th scope="col" class="px-4 py-3">Order ID</th>
                            <th scope="col" class="px-4 py-3">Customer / Organization</th>
                            <th scope="col" class="px-4 py-3">Items & Quantity</th>
                            <th scope="col" class="px-4 py-3">Current Stage</th>
                            <th scope="col" class="px-4 py-3 text-right">Target Due</th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup" class="divide-y divide-slate-100">
                        @forelse($recentOrders as $order)
                            <tr role="row" class="dashboard-job-row hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3 font-mono-num font-bold text-[#0a192f]">
                                    @if($order->invoice)
                                        <a href="{{ route('invoices.show', $order->invoice) }}" class="hover:underline">{{ $order->order_number }}</a>
                                        <span class="block text-[10px] text-slate-500 font-normal">{{ $order->invoice->invoice_number }}</span>
                                    @elseif($order->quotation)
                                        <a href="{{ route('quotations.show', $order->quotation) }}" class="hover:underline">{{ $order->order_number }}</a>
                                    @else
                                        {{ $order->order_number }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="ui-table-primary text-slate-900">{{ $order->customer->name ?? 'Walk-in' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $order->customer->phone ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="ui-table-secondary text-slate-600 break-words">{{ $order->job_summary }}</div>
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px] font-mono-num">
                                        {{ $order->quantity_summary }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $badgeColor = match($order->stage) {
                                            'Confirmed' => 'bg-slate-100 text-slate-800 border-slate-200',
                                            'Cutting & Stitching' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'Embroidery / DTF Printing' => 'bg-purple-50 text-purple-800 border-purple-200',
                                            'Quality Check' => 'bg-sky-50 text-sky-800 border-sky-200',
                                            'Ready for Delivery' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                            'Completed' => 'bg-green-100 text-green-900 border-green-200',
                                            default => 'bg-slate-100 text-slate-800 border-slate-200',
                                        };
                                    @endphp
                                    <span class="ui-badge inline-flex items-center px-2 py-0.5 rounded-full border {{ $badgeColor }}">
                                        {{ $order->stage_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono-num">
                                    <span class="dashboard-mobile-label" aria-hidden="true">Target due</span>
                                    <span class="{{ $order->stage !== 'Completed' && $order->target_date?->isPast() && !$order->target_date?->isToday() ? 'text-rose-700 font-bold' : 'text-slate-600' }}">{{ $order->target_date ? $order->target_date->format('d M Y') : 'Not set' }}</span>
                                    @if($order->priority === 'Urgent')
                                        <span class="block text-[10px] text-rose-600 font-bold">URGENT</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                    {{ $productionStatus === 'completed' ? 'No completed production jobs yet.' : 'No ongoing production jobs.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-table-pagination :paginator="$recentOrders" label="production jobs" per-page-name="per_page" :page-sizes="[8, 10, 15, 20, 25, 50]" />
        </div>

        <!-- Right Column: Recent Quotations & Quick Summary -->
        <div class="dashboard-secondary ui-secondary-stack min-w-0">
            <!-- Recent Quotations Card -->
            <div class="dashboard-quotes dashboard-panel bg-white rounded-xl border border-slate-200/90 shadow-xs">
                <div class="ui-panel-header border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="ui-section-heading text-slate-900">Recent Quotations</h2>
                        <p class="ui-help text-slate-500">Latest pricing proposals</p>
                    </div>
                    <a href="{{ route('quotations.index') }}" class="text-xs font-bold text-[#0a192f] hover:text-[#d4af37]">All Quotes</a>
                </div>
                <div class="dashboard-quote-list divide-y divide-slate-100">
                    @forelse($recentQuotations as $quote)
                        <div class="dashboard-quote p-3.5 hover:bg-slate-50 transition-colors flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('quotations.show', $quote) }}" class="font-bold text-xs text-[#0a192f] hover:text-[#d4af37] font-mono-num block break-words">
                                    {{ $quote->quotation_number }}
                                </a>
                                <div class="ui-table-secondary text-slate-600 break-words">{{ $quote->customer->name ?? 'Walk-in' }}</div>
                                <div class="ui-table-secondary text-slate-500">{{ $quote->category }} • {{ $quote->total_qty }} units</div>
                            </div>
                            <div class="dashboard-quote-total text-right flex-shrink-0">
                                <div class="font-mono-num font-extrabold text-xs text-slate-900">
                                    <span class="text-[10px] text-slate-400 font-normal">QAR</span> {{ number_format($quote->grand_total, 2) }}
                                </div>
                                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold {{ $quote->status === 'Invoiced' ? 'bg-emerald-100 text-emerald-800' : ($quote->status === 'Approved' ? 'bg-sky-100 text-sky-800' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $quote->status }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-slate-400">
                            No recent quotations recorded.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Shop Quick Info Card -->
            <div class="dashboard-company ui-help bg-slate-50 rounded-xl border border-slate-200 p-4 text-slate-600 space-y-2">
                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    {{ config('company.name') }}
                </div>
                @if(config('company.address'))
                    <div><strong>Location:</strong> {{ config('company.address') }}</div>
                @endif
                <div><strong>Services:</strong> School Uniforms, Corporate Wear, Computerized Embroidery, DTF Printing & Promotional Gifts</div>
                <div><strong>Contact:</strong> {{ config('company.phone') }}</div>
                <div class="break-words">{{ config('company.email') }}</div>
            </div>
        </div>
    </div>

</div>
@endsection
