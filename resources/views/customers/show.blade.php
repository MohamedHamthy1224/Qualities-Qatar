@extends('layouts.app')

@section('title', $customer->name . ' — Customer Profile')
@section('page_title', 'Customer Profile & History')

@section('header_action')
    <div class="flex items-center gap-2">
        <a href="{{ route('customers.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
            &larr; Back to Customers
        </a>
        <a href="{{ route('quotations.create', ['customer_id' => $customer->id]) }}" class="inline-flex items-center gap-1.5 bg-[#0a192f] hover:bg-[#132a4a] text-[#d4af37] px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs">
            + New Quote
        </a>
        <a href="{{ route('payments.create', ['customer_id' => $customer->id]) }}" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold shadow-xs">
            + Record Payment
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6 pt-2">

    <!-- Profile Header Card -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-[#0a192f] text-[#d4af37] flex items-center justify-center font-extrabold text-xl shadow-md border border-slate-700">
                {{ substr($customer->name, 0, 2) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-slate-900">{{ $customer->name }}</h2>
                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono-num font-bold text-xs border border-slate-200">
                        {{ $customer->customer_code }}
                    </span>
                </div>
                <div class="text-xs text-slate-500 font-medium">
                    {{ $customer->company_name ?? 'Individual / Walk-in' }} • {{ $customer->address ?? 'Doha, State of Qatar' }}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-slate-50 border border-slate-200 px-5 py-3 rounded-xl w-full md:w-auto justify-between md:justify-end">
            <div class="text-left md:text-right">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Current Balance Due</div>
                <div class="text-xl font-extrabold {{ $customer->balance > 0 ? 'text-rose-700' : 'text-emerald-600' }} font-mono-num">
                    <span class="text-xs font-semibold">QAR</span> {{ number_format($customer->balance, 2) }}
                </div>
            </div>
            @if($customer->balance > 0)
                <a href="{{ route('payments.create', ['customer_id' => $customer->id]) }}" class="bg-rose-700 hover:bg-rose-800 text-white font-bold text-xs px-3 py-1.5 rounded-lg shadow-xs">
                    Clear Balance
                </a>
            @endif
        </div>
    </div>

    <!-- Contact & Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Left: Contact Information & Edit Form -->
        <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-5 space-y-4">
            <h3 class="font-bold text-xs text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">Client Information</h3>
            <div class="space-y-3 text-xs">
                <div>
                    <span class="block text-slate-400 text-[10px] uppercase font-bold">Qatar Phone (+974)</span>
                    <span class="font-mono-num font-bold text-slate-800">{{ $customer->phone ?? '—' }}</span>
                </div>
                <div>
                    <span class="block text-slate-400 text-[10px] uppercase font-bold">Email Address</span>
                    <span class="text-slate-800 font-medium">{{ $customer->email ?? '—' }}</span>
                </div>
                <div>
                    <span class="block text-slate-400 text-[10px] uppercase font-bold">Billing Address</span>
                    <span class="text-slate-800">{{ $customer->address ?? '—' }}</span>
                </div>
                <div>
                    <span class="block text-slate-400 text-[10px] uppercase font-bold">Account Opened</span>
                    <span class="text-slate-600">{{ $customer->created_at->format('d M Y') }}</span>
                </div>
            </div>

            <!-- Quick Edit Collapsible / Modal Trigger -->
            <details class="pt-3 border-t border-slate-100 text-xs">
                <summary class="font-bold text-[#0a192f] hover:text-[#d4af37] cursor-pointer">Edit Profile Details</summary>
                <form action="{{ route('customers.update', $customer) }}" method="POST" class="mt-3 space-y-2.5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600">Client Name</label>
                        <input type="text" name="name" value="{{ $customer->name }}" class="w-full text-xs p-1.5 border border-slate-300 rounded">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600">Company Name</label>
                        <input type="text" name="company_name" value="{{ $customer->company_name }}" class="w-full text-xs p-1.5 border border-slate-300 rounded">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600">Phone</label>
                        <input type="text" name="phone" value="{{ $customer->phone }}" class="w-full text-xs p-1.5 border border-slate-300 rounded">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600">Email</label>
                        <input type="email" name="email" value="{{ $customer->email }}" class="w-full text-xs p-1.5 border border-slate-300 rounded">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600">Address</label>
                        <textarea name="address" rows="2" class="w-full text-xs p-1.5 border border-slate-300 rounded">{{ $customer->address }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-1.5 rounded text-xs">
                        Update Customer
                    </button>
                </form>
            </details>
        </div>

        <!-- Right: 4 History Tabs (Quotations, Invoices, Delivery Notes, Receipts) -->
        <div class="lg:col-span-3 bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden" x-data="{ tab: 'invoices' }">
            <!-- Tab Headers -->
            <div class="border-b border-slate-200 bg-slate-50/70 px-4 flex items-center gap-2 overflow-x-auto" id="historyTabs">
                <button type="button" onclick="switchTab('invoices')" id="tab-invoices" class="tab-btn py-3 px-3 text-xs font-bold border-b-2 border-[#0a192f] text-[#0a192f]">
                    Sales Invoices ({{ $customer->invoices->count() }})
                </button>
                <button type="button" onclick="switchTab('quotes')" id="tab-quotes" class="tab-btn py-3 px-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                    Quotations ({{ $customer->quotations->count() }})
                </button>
                <button type="button" onclick="switchTab('delivery')" id="tab-delivery" class="tab-btn py-3 px-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                    Delivery Notes ({{ $customer->deliveryNotes->count() }})
                </button>
                <button type="button" onclick="switchTab('receipts')" id="tab-receipts" class="tab-btn py-3 px-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                    Payment Receipts ({{ $customer->receipts->count() }})
                </button>
            </div>

            <!-- Tab Content: Invoices -->
            <div id="content-invoices" class="history-content p-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-500 font-bold border-b border-slate-100">
                            <tr>
                                <th class="pb-2">Invoice #</th>
                                <th class="pb-2">Date</th>
                                <th class="pb-2">Total Qty</th>
                                <th class="pb-2 text-right">Grand Total</th>
                                <th class="pb-2 text-right">Balance Due</th>
                                <th class="pb-2 text-center">Status</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono-num">
                            @forelse($customer->invoices as $inv)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 font-bold text-[#0a192f]">
                                        <a href="{{ route('invoices.show', $inv) }}" class="hover:underline">{{ $inv->invoice_number }}</a>
                                    </td>
                                    <td class="py-2.5 text-slate-600">{{ $inv->invoice_date->format('d M Y') }}</td>
                                    <td class="py-2.5">{{ $inv->total_qty }} pcs</td>
                                    <td class="py-2.5 text-right font-extrabold">QAR {{ number_format($inv->grand_total, 2) }}</td>
                                    <td class="py-2.5 text-right font-extrabold {{ $inv->balance_due > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        QAR {{ number_format($inv->balance_due, 2) }}
                                    </td>
                                    <td class="py-2.5 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $inv->status === 'Paid' ? 'bg-emerald-100 text-emerald-800' : ($inv->status === 'Partially Paid' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                            {{ $inv->status }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <a href="{{ route('invoices.show', $inv) }}" class="text-xs text-[#0a192f] hover:underline font-bold">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-400 font-sans">No invoices found for this client.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Quotations -->
            <div id="content-quotes" class="history-content p-4 hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-500 font-bold border-b border-slate-100">
                            <tr>
                                <th class="pb-2">Quotation #</th>
                                <th class="pb-2">Category</th>
                                <th class="pb-2">Date</th>
                                <th class="pb-2">Total Qty</th>
                                <th class="pb-2 text-right">Grand Total (QAR)</th>
                                <th class="pb-2 text-center">Status</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono-num">
                            @forelse($customer->quotations as $quote)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 font-bold text-[#0a192f]">
                                        <a href="{{ route('quotations.show', $quote) }}" class="hover:underline">{{ $quote->quotation_number }}</a>
                                    </td>
                                    <td class="py-2.5 font-sans text-slate-700">{{ $quote->category }}</td>
                                    <td class="py-2.5 text-slate-600">{{ $quote->quotation_date->format('d M Y') }}</td>
                                    <td class="py-2.5">{{ $quote->total_qty }} pcs</td>
                                    <td class="py-2.5 text-right font-extrabold">QAR {{ number_format($quote->grand_total, 2) }}</td>
                                    <td class="py-2.5 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800">
                                            {{ $quote->status }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <a href="{{ route('quotations.show', $quote) }}" class="text-xs text-[#0a192f] hover:underline font-bold">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-400 font-sans">No quotations recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Delivery Notes -->
            <div id="content-delivery" class="history-content p-4 hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-500 font-bold border-b border-slate-100">
                            <tr>
                                <th class="pb-2">Delivery Note #</th>
                                <th class="pb-2">Invoice #</th>
                                <th class="pb-2">Dispatch Date</th>
                                <th class="pb-2">Recipient / Driver</th>
                                <th class="pb-2 text-center">Status</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono-num">
                            @forelse($customer->deliveryNotes as $dn)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 font-bold text-[#0a192f]">
                                        <a href="{{ route('delivery.show', $dn) }}" class="hover:underline">{{ $dn->delivery_number }}</a>
                                    </td>
                                    <td class="py-2.5 text-slate-600">{{ $dn->invoice->invoice_number ?? '—' }}</td>
                                    <td class="py-2.5 text-slate-600">{{ $dn->dispatch_date->format('d M Y') }}</td>
                                    <td class="py-2.5 font-sans text-slate-700">{{ $dn->recipient_name }} ({{ $dn->driver_name ?? 'Courier' }})</td>
                                    <td class="py-2.5 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $dn->status === 'Delivered' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $dn->status }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <a href="{{ route('delivery.show', $dn) }}" class="text-xs text-[#0a192f] hover:underline font-bold">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-6 text-center text-slate-400 font-sans">No delivery notes generated yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Receipts -->
            <div id="content-receipts" class="history-content p-4 hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-500 font-bold border-b border-slate-100">
                            <tr>
                                <th class="pb-2">Receipt #</th>
                                <th class="pb-2">Payment Date</th>
                                <th class="pb-2">Method & Reference</th>
                                <th class="pb-2 text-right">Amount Received (QAR)</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono-num">
                            @forelse($customer->receipts as $rcpt)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 font-bold text-[#0a192f]">
                                        <a href="{{ route('payments.show', $rcpt) }}" class="hover:underline">{{ $rcpt->receipt_number }}</a>
                                    </td>
                                    <td class="py-2.5 text-slate-600">{{ $rcpt->payment_date->format('d M Y') }}</td>
                                    <td class="py-2.5 font-sans text-slate-700">
                                        <span class="font-bold">{{ $rcpt->payment_method }}</span>
                                        @if($rcpt->reference_number)
                                            <span class="text-slate-400 text-[10px]">({{ $rcpt->reference_number }})</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-right font-extrabold text-emerald-700">
                                        QAR {{ number_format($rcpt->amount_received, 2) }}
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <a href="{{ route('payments.show', $rcpt) }}" class="text-xs text-[#0a192f] hover:underline font-bold">Receipt</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-slate-400 font-sans">No payment receipts recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-[#0a192f]', 'text-[#0a192f]');
            btn.classList.add('border-transparent', 'text-slate-500');
        });
        document.querySelectorAll('.history-content').forEach(c => c.classList.add('hidden'));

        document.getElementById('tab-' + tabName).classList.add('border-[#0a192f]', 'text-[#0a192f]');
        document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-slate-500');
        document.getElementById('content-' + tabName).classList.remove('hidden');
    }
</script>
@endsection
