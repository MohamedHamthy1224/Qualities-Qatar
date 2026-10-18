@extends('layouts.app')

@section('title', 'Quotations')
@section('page_title', 'Quotations')

@section('header_action')
    <a href="{{ route('quotations.create') }}" class="inline-flex items-center gap-1.5 bg-[#0a192f] hover:bg-[#132a4a] text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs transition-all">
        <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Quotation</span>
    </a>
@endsection

@section('content')
<div class="ui-page pt-2">

    <!-- Top Stats Bar -->
    <div class="ui-metric-grid gap-4">
        <div class="ui-metric-card min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="ui-metric-label text-slate-500">Total Proposals</span>
                <div class="ui-metric-value text-slate-900 font-mono-num [overflow-wrap:anywhere]">{{ $totalQuotesCount }}</div>
            </div>
            <div class="ui-metric-icon w-10 h-10 shrink-0 rounded-lg bg-sky-50 text-sky-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
        </div>

        <div class="ui-metric-card min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="ui-metric-label text-slate-500">Approved / Invoiced</span>
                <div class="ui-metric-value text-emerald-700 font-mono-num [overflow-wrap:anywhere]">{{ $approvedQuotesCount }}</div>
            </div>
            <div class="ui-metric-icon w-10 h-10 shrink-0 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        <div class="ui-metric-card min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="ui-metric-label text-slate-500">Automatic invoicing</span>
                <div class="ui-help text-slate-600 mt-1">Approval creates a sales invoice and queues production automatically.</div>
            </div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="ui-filter-bar bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-x-auto">
        <form method="GET" action="{{ route('quotations.index') }}" class="flex flex-nowrap items-center gap-2 w-full min-w-[27rem]">
            <input type="hidden" name="per_page" value="{{ $quotations->perPage() }}">
            <div class="relative min-w-32 flex-1 sm:max-w-72">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search quotation #, customer..." class="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="status" aria-label="Filter quotations by status" onchange="this.form.submit()" class="min-w-0 flex-[0_1_10rem] text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#0a192f]">
                <option value="">All Statuses</option>
                <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
                <option value="Sent" {{ request('status') === 'Sent' ? 'selected' : '' }}>Sent to customer</option>
                <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                <option value="Invoiced" {{ request('status') === 'Invoiced' ? 'selected' : '' }}>Invoiced</option>
                <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <select name="category" onchange="this.form.submit()" class="min-w-0 flex-[0_1_12rem] text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 focus:outline-none">
                <option value="">All Categories</option>
                <option value="Multi-Category" {{ request('category') === 'Multi-Category' ? 'selected' : '' }}>Multi-Category</option>
                <option value="School Uniforms" {{ request('category') === 'School Uniforms' ? 'selected' : '' }}>School Uniforms</option>
                <option value="Corporate Uniforms" {{ request('category') === 'Corporate Uniforms' ? 'selected' : '' }}>Corporate Uniforms</option>
                <option value="Sportswear" {{ request('category') === 'Sportswear' ? 'selected' : '' }}>Sportswear</option>
                <option value="Computerized Embroidery" {{ request('category') === 'Computerized Embroidery' ? 'selected' : '' }}>Computerized Embroidery</option>
                <option value="DTF Printing" {{ request('category') === 'DTF Printing' ? 'selected' : '' }}>DTF Printing</option>
                <option value="Promotional Gifts" {{ request('category') === 'Promotional Gifts' ? 'selected' : '' }}>Promotional Gifts</option>
            </select>

            <button type="submit" class="shrink-0 whitespace-nowrap bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'category']))
                <a href="{{ route('quotations.index') }}" class="shrink-0 whitespace-nowrap text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            @endif
        </form>

    </div>

    <!-- Quotations Table -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="ui-register-header no-print">
            <div class="ui-register-heading">
                <h2 class="ui-section-heading" id="quotations-register-title">Quotations</h2>
                <span class="ui-register-count">{{ $quotations->total() }} quotations</span>
            </div>
            <div class="ui-register-actions ui-page-actions">@yield('header_action')</div>
        </div>
        <div class="overflow-x-auto">
            <table class="ui-table w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3">Quotation #</th>
                        <th class="px-4 py-3">Customer / Organization</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-center">Total Quantity</th>
                        <th class="px-4 py-3 text-right">Grand Total (QAR)</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3">Date / Due</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($quotations as $quote)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="ui-table-primary px-4 py-3 font-mono-num font-bold text-[#0a192f]">
                                <a href="{{ route('quotations.show', $quote) }}" class="hover:underline flex items-center gap-1.5">
                                    <span>{{ $quote->quotation_number }}</span>
                                    @if($quote->artwork_file)
                                        <span title="Artwork Attached" class="text-sky-500 text-xs">
                                            <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </span>
                                    @endif
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="{{ route('customers.show', $quote->customer_id) }}" class="ui-table-primary font-bold text-slate-900 hover:underline">
                                        {{ $quote->customer->name ?? 'Walk-in Customer' }}
                                    </a>
                                    @if($quote->customer && $quote->customer->customer_code)
                                        <span class="ui-badge px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono-num font-bold text-[10px] border border-slate-200">
                                            {{ $quote->customer->customer_code }}
                                        </span>
                                    @endif
                                </div>
                                <div class="ui-table-secondary text-[11px] text-slate-500">{{ $quote->customer->company_name ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeClass = match($quote->category) {
                                        'Multi-Category' => 'bg-purple-100 text-purple-900 border-purple-300 font-extrabold',
                                        'School Uniforms' => 'bg-sky-100 text-sky-800 border-sky-300',
                                        'Corporate Uniforms' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                        'Sportswear' => 'bg-amber-100 text-amber-800 border-amber-300',
                                        'Computerized Embroidery' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                        'DTF Printing' => 'bg-cyan-100 text-cyan-800 border-cyan-300',
                                        'Promotional Gifts' => 'bg-fuchsia-100 text-fuchsia-800 border-fuchsia-300',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200'
                                    };
                                @endphp
                                <span class="ui-badge px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClass }}">
                                    {{ $quote->category }}
                                </span>
                            </td>
                            <td class="ui-table-primary px-4 py-3 text-center font-mono-num font-bold text-slate-800">
                                <span class="ui-badge bg-slate-100 px-2 py-0.5 rounded">{{ $quote->total_qty }} units</span>
                            </td>
                            <td class="ui-table-primary px-4 py-3 text-right font-mono-num font-extrabold text-slate-900">
                                <span class="ui-table-secondary text-[10px] text-slate-400 font-normal">QAR</span> {{ number_format($quote->grand_total, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusStyle = match($quote->status) {
                                        'Invoiced' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'Approved' => 'bg-sky-100 text-sky-800 border-sky-200',
                                        'Sent' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'Draft' => 'bg-slate-100 text-slate-700 border-slate-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200'
                                    };
                                @endphp
                                <span class="ui-badge inline-block px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusStyle }}">
                                    {{ $quote->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 font-mono-num text-[11px]">
                                <div>{{ $quote->quotation_date->format('d M Y') }}</div>
                                @if($quote->delivery_date)
                                    <div class="ui-table-secondary text-[10px] text-slate-400">Due: {{ $quote->delivery_date->format('d M') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('quotations.show', $quote) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs">
                                        View
                                    </a>
                                    <a href="{{ route('pdf.quotation', ['quotation' => $quote, 'print' => 1]) }}" target="_blank" class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs" title="Print Quotation">
                                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>
                                    @if($quote->invoice)
                                        <a href="{{ route('invoices.show', $quote->invoice) }}" class="px-2.5 py-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs whitespace-nowrap" title="{{ $quote->invoice->invoice_number }}">View invoice</a>
                                    @elseif(in_array($quote->status, ['Draft', 'Sent', 'Approved'], true))
                                        <form action="{{ route('quotations.update-status', $quote) }}" method="POST" class="inline" onsubmit="confirmQuotationApproval(event)">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="Approved">
                                            <button type="submit" class="px-2.5 py-1 rounded bg-[#0a192f] hover:bg-[#132a4a] text-white font-bold text-xs whitespace-nowrap" title="Approve quotation and automatically create its sales invoice">
                                                Approve quotation
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="ui-help px-4 py-8 text-center text-slate-400">
                                No quotations found matching your criteria. Click "New Quotation" to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-table-pagination :paginator="$quotations" label="quotations" per-page-name="per_page" />
    </div>

</div>
@endsection

@push('scripts')
<script>
    async function confirmQuotationApproval(event) {
        const form = event.currentTarget;
        if (form.dataset.approvalConfirmed === 'true') return;
        event.preventDefault();
        if (form.dataset.approvalPending === 'true') return;

        form.dataset.approvalPending = 'true';
        const submitter = event.submitter;
        try {
            const confirmed = await window.appConfirm({
                title: 'Approve quotation?',
                message: 'Approving automatically creates a sales invoice and queues this job for production.',
                confirmLabel: 'Approve quotation',
                cancelLabel: 'Cancel',
                destructive: false
            });
            if (confirmed) {
                form.dataset.approvalConfirmed = 'true';
                if (submitter) form.requestSubmit(submitter);
                else form.requestSubmit();
            }
        } finally {
            delete form.dataset.approvalPending;
            delete form.dataset.approvalConfirmed;
        }
    }
</script>
@endpush
