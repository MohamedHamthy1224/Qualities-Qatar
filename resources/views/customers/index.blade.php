@extends('layouts.app')

@section('title', 'Customer Management')
@section('page_title', 'Customer Management')

@section('header_action')
    <button type="button" onclick="openQuickCustomerModal()" class="inline-flex items-center gap-1.5 bg-[#0a192f] hover:bg-[#132a4a] text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs transition-all">
        <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>+ New Customer</span>
    </button>
@endsection

@section('content')
<div class="space-y-6 pt-2">

    <!-- Top Summary Banner -->
    <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200/90 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="font-bold text-slate-900 text-sm sm:text-base">Registered Corporate & Institutional Clients</h2>
            <p class="text-xs text-slate-500">Schools, hotels, sports clubs, contracting firms & retail accounts across Qatar.</p>
        </div>
        <div class="flex items-center gap-4 bg-slate-50 border border-slate-200/80 px-4 py-2.5 rounded-lg">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Customer Outstanding:</span>
            <span class="text-base font-extrabold text-rose-700 font-mono-num">
                QAR {{ number_format($totalOutstanding, 2) }}
            </span>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <div class="relative flex-1 sm:w-80">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, company, Qatar phone (+974)..." class="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <button type="submit" class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800">
                Search
            </button>

            @if(request('search') || request('filter'))
                <a href="{{ route('customers.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">Clear</a>
            @endif
        </form>

        <div class="flex items-center gap-1.5 w-full md:w-auto justify-end">
            <a href="{{ route('customers.index') }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ !request('filter') ? 'bg-[#0a192f] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All Accounts ({{ \App\Models\Customer::count() }})
            </a>
            <a href="{{ route('customers.index', ['filter' => 'balance']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ request('filter') === 'balance' ? 'bg-rose-700 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' }}">
                With Balance Due
            </a>
        </div>
    </div>

    <!-- Customers List Table -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Customer / Organization</th>
                        <th class="px-4 py-3">Company / Group</th>
                        <th class="px-4 py-3">Phone & Location</th>
                        <th class="px-4 py-3 text-center">Orders & Quotes</th>
                        <th class="px-4 py-3 text-right">Balance Due (QAR)</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-mono-num font-bold text-[#0a192f]">
                                {{ $customer->customer_code }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('customers.show', $customer) }}" class="font-bold text-slate-900 hover:text-[#0a192f] text-sm block">
                                    {{ $customer->name }}
                                </a>
                                <span class="text-[11px] text-slate-500">{{ $customer->email ?? 'No email recorded' }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 font-medium">
                                {{ $customer->company_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-mono-num text-slate-800 font-semibold">{{ $customer->phone ?? '—' }}</div>
                                <div class="text-[11px] text-slate-500 truncate max-w-xs">{{ $customer->address ?? 'Doha, Qatar' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="inline-flex items-center gap-1.5 text-[11px] text-slate-600 font-mono-num">
                                    <span title="Quotations" class="px-1.5 py-0.5 rounded bg-slate-100 font-bold">{{ $customer->quotations_count }} Q</span>
                                    <span title="Invoices" class="px-1.5 py-0.5 rounded bg-slate-100 font-bold">{{ $customer->invoices_count }} Inv</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-mono-num">
                                @if($customer->balance > 0)
                                    <span class="inline-block px-2 py-0.5 rounded bg-rose-50 border border-rose-200 text-rose-700 font-extrabold text-xs">
                                        QAR {{ number_format($customer->balance, 2) }}
                                    </span>
                                @else
                                    <span class="text-emerald-600 font-bold text-xs">
                                        Cleared (0.00)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('customers.show', $customer) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs transition-colors">
                                        History & Profile
                                    </a>
                                    <a href="{{ route('quotations.create', ['customer_id' => $customer->id]) }}" class="px-2 py-1 rounded bg-[#0a192f] hover:bg-[#132a4a] text-[#d4af37] font-bold text-xs" title="New Quotation for this client">
                                        + Quote
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                No customer records found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
