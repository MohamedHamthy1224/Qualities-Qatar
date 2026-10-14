@extends('layouts.app')

@section('title', 'Record Customer Payment')
@section('page_title', 'Record Customer Payment Receipt')

@section('header_action')
    <a href="{{ route('payments.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
        &larr; Back to Receipts
    </a>
@endsection

@section('content')
<div class="space-y-6 pt-2 max-w-4xl mx-auto">

    <form id="paymentForm" action="{{ route('payments.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Payment Logging Card -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-6 space-y-6">

            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <h2 class="font-bold text-sm text-slate-900">Payment Collection Details</h2>
                </div>
                <div class="text-xs text-slate-500">
                    Currency: <strong class="text-slate-900">QAR (Qatari Riyal)</strong>
                </div>
            </div>

            <!-- Customer Selection & Live Outstanding Balance Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700">Select Customer *</label>
                        <button type="button" onclick="openQuickCustomerModal()" class="text-[11px] font-bold text-[#0a192f] hover:text-[#d4af37] underline">
                            + Quick Add Customer
                        </button>
                    </div>
                    <select name="customer_id" id="customerSelect" required onchange="loadCustomerInvoices(this.value)" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                        <option value="">-- Choose Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ (isset($selectedCustomer) && $selectedCustomer->id == $c->id) ? 'selected' : '' }} data-balance="{{ $c->balance }}">
                                {{ $c->name }} {{ $c->company_name ? "({$c->company_name})" : '' }} [{{ $c->customer_code }}]
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Live Balance Badge -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 flex flex-col justify-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Outstanding Balance</span>
                    <div class="text-xl font-extrabold text-rose-700 font-mono-num mt-1" id="customerBalanceDisplay">
                        @if(isset($selectedCustomer))
                            QAR {{ number_format($selectedCustomer->balance, 2) }}
                        @else
                            QAR 0.00
                        @endif
                    </div>
                </div>
            </div>

            <!-- Unpaid Invoices Auto-Load Box -->
            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200">
                <label class="block text-xs font-bold text-slate-800 mb-1">Apply Payment Against Specific Invoice (Optional):</label>
                <select name="invoice_id" id="invoiceSelect" onchange="autoFillInvoiceAmount(this)" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                    <option value="">-- Auto-Allocate against oldest open balance --</option>
                    @if(isset($selectedCustomer))
                        @foreach($selectedCustomer->invoices->where('balance_due', '>', 0) as $inv)
                            <option value="{{ $inv->id }}" {{ (isset($selectedInvoice) && $selectedInvoice->id == $inv->id) ? 'selected' : '' }} data-due="{{ $inv->balance_due }}">
                                {{ $inv->invoice_number }} (Due: QAR {{ number_format($inv->balance_due, 2) }} of {{ number_format($inv->grand_total, 2) }}) — Date: {{ $inv->invoice_date->format('d M Y') }}
                            </option>
                        @endforeach
                    @endif
                </select>
                <p class="text-[11px] text-slate-500 mt-1">If no specific invoice is selected, amount will be allocated automatically to the oldest pending invoices.</p>
            </div>

            <!-- Receipt Info Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                <!-- Receipt Number -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Receipt Number *</label>
                    <input type="text" name="receipt_number" value="{{ old('receipt_number', $suggestedNumber) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono-num font-bold text-[#0a192f] bg-slate-50 focus:outline-none">
                </div>

                <!-- Amount Received -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Amount Received (QAR) *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 font-bold text-slate-400">QAR</span>
                        <input type="number" step="0.01" min="0.01" name="amount_received" id="amountReceived" value="{{ isset($selectedInvoice) ? $selectedInvoice->balance_due : '' }}" required placeholder="0.00" class="w-full pl-12 pr-3 py-2 border border-slate-300 rounded-lg font-mono-num font-extrabold text-emerald-800 text-sm focus:ring-2 focus:ring-emerald-600 focus:outline-none">
                    </div>
                </div>

                <!-- Payment Date -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Payment Date *</label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Payment Method *</label>
                    <select name="payment_method" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none bg-white">
                        <option value="Cash">Cash Counter</option>
                        <option value="Card">Card (POS Terminal - Visa/Mastercard/Amex)</option>
                        <option value="Bank Transfer">Bank Transfer (QNB / CBQ / Doha Bank)</option>
                        <option value="Cheque">Corporate Cheque</option>
                    </select>
                </div>

                <!-- Reference / Transaction No -->
                <div class="sm:col-span-2">
                    <label class="block font-bold text-slate-700 mb-1">Reference / Transaction / Cheque #</label>
                    <input type="text" name="reference_number" placeholder="e.g. POS Auth Code #8849 / QNB Transfer Ref QA-9921 / Cheque #10291" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Payment Notes & Counter Comments</label>
                <textarea name="notes" rows="2" placeholder="e.g. 50% advance payment received at Salwa Road counter. Balance to be cleared upon physical delivery handover." class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                <a href="{{ route('payments.index') }}" class="px-4 py-2 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-lg text-xs font-extrabold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Record Payment & Generate Official Receipt</span>
                </button>
            </div>

        </div>

    </form>

</div>

@push('scripts')
<script>
    async function loadCustomerInvoices(customerId) {
        const balDisplay = document.getElementById('customerBalanceDisplay');
        const invSelect = document.getElementById('invoiceSelect');

        if (!customerId) {
            balDisplay.innerText = 'QAR 0.00';
            invSelect.innerHTML = '<option value="">-- Auto-Allocate against oldest open balance --</option>';
            return;
        }

        try {
            const res = await fetch(`/api/customers/${customerId}/unpaid-invoices`);
            const data = await res.json();

            balDisplay.innerText = 'QAR ' + data.balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            invSelect.innerHTML = '<option value="">-- Auto-Allocate against oldest open balance --</option>';
            if (data.invoices && data.invoices.length > 0) {
                data.invoices.forEach(inv => {
                    const opt = document.createElement('option');
                    opt.value = inv.id;
                    opt.dataset.due = inv.balance_due;
                    opt.innerText = `${inv.invoice_number} (Due: QAR ${parseFloat(inv.balance_due).toFixed(2)} of ${parseFloat(inv.grand_total).toFixed(2)}) — Date: ${inv.invoice_date}`;
                    invSelect.appendChild(opt);
                });
            }
        } catch (e) {
            console.error('Failed to load unpaid customer invoices:', e);
        }
    }

    function autoFillInvoiceAmount(select) {
        const opt = select.options[select.selectedIndex];
        if (opt && opt.dataset.due) {
            document.getElementById('amountReceived').value = parseFloat(opt.dataset.due).toFixed(2);
        }
    }
</script>
@endpush
@endsection
