@extends('layouts.app')

@section('title', 'Operations Dashboard')
@section('page_title', 'Operations Dashboard')

@section('header_action')
    <div class="flex items-center gap-2">
        <a href="{{ route('quotations.create') }}" class="inline-flex items-center gap-1.5 bg-[#0a192f] hover:bg-[#132a4a] text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs transition-all">
            <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ New Quotation</span>
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6 pt-2">

    <!-- Top Operational Metrics Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Today's / Active Orders -->
        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -right-2 -bottom-2 text-slate-50 opacity-40 pointer-events-none">
                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Pipeline Orders</span>
                <span class="w-8 h-8 rounded-lg bg-sky-50 text-sky-700 flex items-center justify-center font-bold text-xs border border-sky-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-900 font-mono-num">{{ $todayOrdersCount }}</div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="text-emerald-600 font-semibold">Live in manufacturing</span> • Doha Workshop
                </div>
            </div>
        </div>

        <!-- 2. Pending Quotations -->
        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pending Quotations</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-xs border border-amber-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-900 font-mono-num">{{ $pendingQuotationsCount }}</div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="text-amber-600 font-semibold">Awaiting Client Approval</span>
                </div>
            </div>
        </div>

        <!-- 3. Ready Deliveries -->
        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs hover:border-slate-300 transition-all flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ready Deliveries</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs border border-emerald-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-3xl font-extrabold text-slate-900 font-mono-num">{{ $readyDeliveriesCount }}</div>
                <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                    <span class="text-emerald-600 font-semibold">Packed & QC Approved</span>
                </div>
            </div>
        </div>

        <!-- 4. Overdue Payments (in QAR) -->
        <div class="bg-white p-5 rounded-xl border border-rose-200/80 shadow-xs hover:border-rose-300 transition-all flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Overdue Payments</span>
                <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-700 flex items-center justify-center font-bold text-xs border border-rose-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <div class="text-2xl lg:text-3xl font-extrabold text-rose-900 font-mono-num">
                    <span class="text-xs text-rose-600 font-semibold">QAR</span> {{ number_format($overduePaymentsQAR, 2) }}
                </div>
                <div class="text-xs text-rose-600 mt-1 font-medium">
                    Total Outstanding: QAR {{ number_format($totalReceivablesQAR, 2) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Launch Bar for Billing & Counter Operators -->
    <div class="bg-gradient-to-r from-[#0a192f] via-[#0e223d] to-[#132a4a] rounded-xl p-4 sm:p-5 text-white shadow-md flex flex-col sm:flex-row items-center justify-between gap-4 border border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#d4af37]/20 border border-[#d4af37]/40 flex items-center justify-center text-[#d4af37]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <h3 class="font-bold text-sm text-white">Counter Billing & Express Action Desk</h3>
                <p class="text-xs text-slate-300">Fast document generation and payment logging for non-technical counter operators.</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
            <!-- + New Quotation -->
            <a href="{{ route('quotations.create') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-[#d4af37] hover:bg-[#c59b27] text-slate-950 font-bold px-4 py-2 rounded-lg text-xs shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ New Quotation</span>
            </a>
            <!-- + New Invoice -->
            <a href="{{ route('invoices.create') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-white/10 hover:bg-white/20 text-white font-bold px-4 py-2 rounded-lg text-xs border border-white/20 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>+ New Invoice</span>
            </a>
            <!-- + Record Payment -->
            <a href="{{ route('payments.create') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-4 py-2 rounded-lg text-xs shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>+ Record Payment</span>
            </a>
        </div>
    </div>

    <!-- Main Active Orders Section (No Complex Charts, Clean List) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Active Orders (2 Columns) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900 text-sm">Recent Active Production Orders</h2>
                    <p class="text-xs text-slate-500">Live progress tracking across manufacturing stages</p>
                </div>
                <a href="{{ route('production.index') }}" class="text-xs font-bold text-[#0a192f] hover:text-[#d4af37] flex items-center gap-1">
                    <span>View Kanban Board</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3">Order ID</th>
                            <th class="px-4 py-3">Customer / Organization</th>
                            <th class="px-4 py-3">Uniform Item & Qty</th>
                            <th class="px-4 py-3">Current Stage</th>
                            <th class="px-4 py-3 text-right">Target Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentOrders as $order)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3 font-mono-num font-bold text-[#0a192f]">
                                    {{ $order->order_number }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900">{{ $order->customer->name ?? 'Walk-in' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $order->customer->phone ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $order->item_name }}</div>
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px] font-mono-num">
                                        {{ $order->total_qty }} pcs
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
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeColor }}">
                                        {{ $order->stage }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono-num">
                                    <span class="text-slate-600">{{ $order->target_date ? $order->target_date->format('d M') : '—' }}</span>
                                    @if($order->priority === 'Urgent')
                                        <span class="block text-[10px] text-rose-600 font-bold">URGENT</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                                    No active orders found in the pipeline.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Column: Recent Quotations & Quick Summary -->
        <div class="space-y-6">
            <!-- Recent Quotations Card -->
            <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-slate-900 text-sm">Recent Quotations</h2>
                        <p class="text-xs text-slate-500">Latest pricing proposals</p>
                    </div>
                    <a href="{{ route('quotations.index') }}" class="text-xs font-bold text-[#0a192f] hover:text-[#d4af37]">All Quotes</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentQuotations as $quote)
                        <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('quotations.show', $quote) }}" class="font-bold text-xs text-[#0a192f] hover:text-[#d4af37] font-mono-num block truncate">
                                    {{ $quote->quotation_number }}
                                </a>
                                <div class="text-[11px] text-slate-600 truncate">{{ $quote->customer->name ?? 'Walk-in' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $quote->category }} • {{ $quote->total_qty }} pcs</div>
                            </div>
                            <div class="text-right flex-shrink-0">
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
            <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 text-xs text-slate-600 space-y-2">
                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    QUALITIES Uniforms & Gifts Solutions
                </div>
                <div><strong>Location:</strong> Salwa Road, Zone 56, Doha, State of Qatar</div>
                <div><strong>Services:</strong> School Uniforms, Corporate Wear, Computerized Embroidery, DTF Printing & Promotional Gifts</div>
                <div><strong>Hotline:</strong> +974 4455 1234 / +974 5588 9922</div>
            </div>
        </div>
    </div>

</div>
@endsection
