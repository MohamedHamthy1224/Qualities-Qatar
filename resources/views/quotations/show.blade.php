@extends('layouts.app')

@section('title', "Quotation {$quotation->quotation_number}")
@section('page_title', "Quotation {$quotation->quotation_number}")

@section('header_action')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('quotations.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
            &larr; All Quotes
        </a>

        <!-- Print View -->
        <a href="{{ route('pdf.quotation', ['quotation' => $quotation, 'print' => 1]) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-300">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print View (A4)</span>
        </a>

        <!-- PDF Download -->
        <a href="{{ route('pdf.quotation', $quotation) }}" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs">
            <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Download PDF</span>
        </a>

        <!-- Approval creates the linked invoice automatically. -->
        @if($quotation->invoice)
            <a href="{{ route('invoices.show', $quotation->invoice) }}" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs">
                <span>View Invoice: {{ $quotation->invoice->invoice_number }}</span>
            </a>
        @elseif(in_array($quotation->status, ['Draft', 'Sent', 'Approved'], true))
            <form action="{{ route('quotations.update-status', $quotation) }}" method="POST" class="inline" onsubmit="confirmQuotationApproval(event)">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="Approved">
                <button type="submit" class="inline-flex items-center gap-1.5 bg-[#d4af37] hover:bg-[#c59b27] text-slate-950 px-3.5 py-1.5 rounded-lg text-xs font-extrabold shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Approve quotation</span>
                </button>
            </form>
        @else
            <span class="text-xs text-slate-500">Change the status below to approve this quotation.</span>
        @endif
    </div>
@endsection

@section('content')
<div class="ui-page pt-2 max-w-5xl mx-auto">

    <!-- Linked Invoice Notice -->
    @if($quotation->invoice)
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl flex flex-wrap items-center justify-between gap-3 text-xs text-emerald-900">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Approved &amp; invoiced:</strong> Linked to sales invoice <strong>{{ $quotation->invoice->invoice_number }}</strong>.</span>
            </div>
            <a href="{{ route('invoices.show', $quotation->invoice) }}" class="font-bold underline hover:text-emerald-700">Open Sales Invoice &rarr;</a>
        </div>
    @endif

    <!-- Document Card -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm ui-section ui-page">
        <div class="ui-record-actions no-print">@yield('header_action')</div>

        <!-- Branded Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            @include('documents.company')


            <div class="text-left sm:text-right">
                <div class="ui-field-label text-slate-500">Quotation</div>
                <div class="text-2xl font-extrabold text-[#0a192f] font-mono-num">{{ $quotation->quotation_number }}</div>
                <div class="mt-1">
                    @php
                        $badgeStyle = match($quotation->status) {
                            'Invoiced' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                            'Approved' => 'bg-sky-100 text-sky-800 border-sky-300',
                            'Sent' => 'bg-amber-100 text-amber-800 border-amber-300',
                            'Draft' => 'bg-slate-100 text-slate-700 border-slate-300',
                            default => 'bg-slate-100 text-slate-700'
                        };
                    @endphp
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeStyle }}">
                        Status: {{ $quotation->status }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Client & Quotation Meta Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50/80 p-4 rounded-xl border border-slate-200/80 text-xs">
            <div>
                <span class="ui-field-label text-slate-500 block mb-1">Prepared for</span>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="font-bold text-sm text-slate-900">{{ $quotation->customer->name }}</div>
                    @if($quotation->customer && $quotation->customer->customer_code)
                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono-num font-bold text-xs border border-slate-200">
                            {{ $quotation->customer->customer_code }}
                        </span>
                    @endif
                </div>
                @if($quotation->customer->company_name)
                    <div class="font-semibold text-slate-700">{{ $quotation->customer->company_name }}</div>
                @endif
                <div class="text-slate-600 mt-1">Qatar Phone: <strong class="font-mono-num">{{ $quotation->customer->phone ?? '—' }}</strong></div>
                <div class="text-slate-600">Email: {{ $quotation->customer->email ?? '—' }}</div>
                <div class="text-slate-600">Location: {{ $quotation->customer->address ?? 'Doha, Qatar' }}</div>
            </div>

            <div class="sm:text-right space-y-1">
                <span class="ui-field-label text-slate-500 block mb-1">Quotation details</span>
                <div>Quotation Date: <strong class="font-mono-num text-slate-900">{{ $quotation->quotation_date->format('d M Y') }}</strong></div>
                <div>Target Delivery: <strong class="font-mono-num text-slate-900">{{ $quotation->delivery_date ? $quotation->delivery_date->format('d M Y') : 'Standard 7 Days' }}</strong></div>
                <div>Category: <span class="font-semibold text-[#0a192f]">{{ $quotation->category }}</span></div>
                <div>Payment Terms: <span class="font-semibold text-slate-700">50% Advance Upon Approval, Balance on Delivery</span></div>
            </div>
        </div>

        <!-- Itemized Size Matrix Table -->
        <div>
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <h3 class="ui-section-heading text-slate-900">Items & sizes</h3>
                <span class="ui-help text-slate-500 font-mono-num">Total quantity: <strong>{{ $quotation->total_qty }} units</strong></span>
            </div>

            <div data-paginated-table-region class="border border-slate-200 rounded-lg">
                @include('documents.items', ['items' => $quotation->items])
            </div>
        </div>

        <!-- Artwork Attachment & Financial Totals -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
            <!-- Left: Artwork & Notes -->
            <div class="space-y-4">
                @if($quotation->artwork_file)
                    <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50/60">
                        <span class="ui-field-label text-slate-500 block mb-2">Customer artwork</span>
                        <div class="flex flex-wrap items-center gap-3">
                            <img src="{{ asset('storage/' . $quotation->artwork_file) }}" alt="Artwork" class="w-24 h-24 object-contain rounded-lg border border-slate-300 bg-white shadow-xs">
                            <div class="text-xs">
                                <div class="font-semibold text-slate-800">Artwork attachment</div>
                                <a href="{{ asset('storage/' . $quotation->artwork_file) }}" target="_blank" class="text-sky-600 hover:underline font-semibold text-[11px] block mt-1">Open Original File &rarr;</a>
                            </div>
                        </div>
                    </div>
                @endif

                @if($quotation->notes)
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs">
                        <span class="ui-field-label text-slate-500 block mb-1">Production notes</span>
                        <p class="text-slate-700 whitespace-pre-line">{{ $quotation->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Right: Financial Breakdown -->
            <div class="ui-section bg-slate-50 rounded-xl border border-slate-200 space-y-2.5 text-xs font-mono-num">
                <div class="flex items-center justify-between text-slate-600 font-sans">
                    <span>Subtotal:</span>
                    <span class="font-mono-num font-bold text-slate-900">QAR {{ number_format($quotation->subtotal, 2) }}</span>
                </div>
                @if($quotation->setup_charges > 0)
                    <div class="flex items-center justify-between text-slate-600 font-sans">
                        <span>Embroidery Setup / Screen Fee:</span>
                        <span class="font-mono-num font-bold text-slate-800">QAR {{ number_format($quotation->setup_charges, 2) }}</span>
                    </div>
                @endif
                @if($quotation->print_charges > 0)
                    <div class="flex items-center justify-between text-slate-600 font-sans">
                        <span>Printing / DTF Heat Transfer:</span>
                        <span class="font-mono-num font-bold text-slate-800">QAR {{ number_format($quotation->print_charges, 2) }}</span>
                    </div>
                @endif
                @if($quotation->discount > 0)
                    <div class="flex items-center justify-between text-emerald-700 font-sans">
                        <span>Special Discount:</span>
                        <span class="font-mono-num font-bold text-emerald-700">- QAR {{ number_format($quotation->discount, 2) }}</span>
                    </div>
                @endif

                <div class="ui-total-row border-t border-slate-200 pt-3 font-sans">
                    <span class="ui-total-label text-slate-900">Grand Total</span>
                    <div class="ui-total-value text-[#0a192f] font-mono-num">
                        QAR {{ number_format($quotation->grand_total, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Sign-off Note -->
        <div class="pt-4 border-t border-slate-200 text-xs text-slate-500 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                @if(config('company.quotation_terms'))<div class="whitespace-pre-line">{{ config('company.quotation_terms') }}</div>@endif
                <div>Prepared by: <strong>{{ config('company.name') }}</strong></div>
            </div>
            <div class="flex items-center gap-2">
                <!-- Status updater form -->
                @unless($quotation->invoice)
                <form action="{{ route('quotations.update-status', $quotation) }}" method="POST" class="inline-flex flex-wrap items-center gap-1.5" onsubmit="confirmQuotationApproval(event)">
                    @csrf
                    @method('PATCH')
                    <label for="quotation-status" class="ui-field-label text-slate-500">Change Status:</label>
                    <select id="quotation-status" name="status" aria-describedby="quotation-status-help" data-original-status="{{ $quotation->status }}" onchange="this.form.requestSubmit()" class="text-xs border border-slate-300 rounded px-2 py-1 font-semibold focus:outline-none focus:ring-2 focus:ring-[#0a192f]">
                        <option value="Draft" {{ $quotation->status === 'Draft' ? 'selected' : '' }}>Draft — not sent</option>
                        <option value="Sent" {{ $quotation->status === 'Sent' ? 'selected' : '' }}>Sent — sent to customer</option>
                        <option value="Approved" {{ $quotation->status === 'Approved' ? 'selected' : '' }}>Approved — create invoice</option>
                        <option value="Cancelled" {{ $quotation->status === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </form>
                @endunless
            </div>
        </div>
        @unless($quotation->invoice)
            <p id="quotation-status-help" class="ui-help text-slate-500 no-print">Draft means not sent. Mark Sent after sending the quotation to the customer; this does not send a message automatically. Approved confirms customer acceptance, creates a sales invoice and queues production. Cancelled means no longer proceeding. Selecting a status saves immediately.</p>
        @endunless

    </div>

</div>
@endsection

@push('scripts')
<script>
    async function confirmQuotationApproval(event) {
        const form = event.currentTarget;
        if (form.elements.status.value !== 'Approved' || form.dataset.approvalConfirmed === 'true') return;
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
            } else if (form.elements.status.tagName === 'SELECT') {
                form.elements.status.value = form.elements.status.dataset.originalStatus;
            }
        } finally {
            delete form.dataset.approvalPending;
            delete form.dataset.approvalConfirmed;
        }
    }
</script>
@endpush
