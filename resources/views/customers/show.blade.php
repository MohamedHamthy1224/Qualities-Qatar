@extends('layouts.app')

@section('title', $customer->name . ' — Customer Profile')
@section('page_title', 'Customer Profile & History')

@section('header_action')
    <div class="flex flex-wrap items-center gap-2">
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
<div class="ui-page pt-2">

    <!-- Profile Header Card -->
    <div class="ui-section bg-white rounded-xl border border-slate-200/90 shadow-xs space-y-4">
        <div class="ui-record-actions no-print">@yield('header_action')</div>
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-3">
                <div class="w-12 h-12 shrink-0 rounded-xl bg-[#0a192f] text-[#d4af37] flex items-center justify-center font-bold text-lg border border-slate-700">
                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($customer->name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900 break-words">{{ $customer->name }}</h2>
                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono-num font-bold text-xs border border-slate-200">
                            {{ $customer->customer_code }}
                        </span>
                    </div>
                    <div class="ui-help text-slate-500 break-words mt-0.5">
                        {{ $customer->company_name ?? 'Individual / Walk-in' }}@if($customer->address) • {{ $customer->address }}@endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 bg-slate-50 border border-slate-200 px-5 py-3 rounded-xl w-full md:w-auto justify-between md:justify-end">
                <div class="text-left md:text-right">
                    <div class="ui-field-label text-slate-500">Balance due</div>
                    <div class="text-xl font-extrabold {{ $customer->balance > 0 ? 'text-rose-700' : 'text-emerald-600' }} font-mono-num">
                        <span class="text-xs font-semibold">QAR</span> {{ number_format($customer->balance, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact & Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5">
        <!-- Left: Contact Information & Edit Form -->
        <div class="ui-section min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs space-y-4">
            <h3 class="ui-section-heading text-slate-900 border-b border-slate-100 pb-2">Contact details</h3>
            <div class="space-y-3 text-xs">
                <div>
                    <span class="ui-field-label block text-slate-500">Qatar phone (+974)</span>
                    <span class="font-mono-num font-bold text-slate-800">{{ $customer->phone ?? '—' }}</span>
                </div>
                <div>
                    <span class="ui-field-label block text-slate-500">Email address</span>
                    <span class="text-slate-800 font-medium break-words">{{ $customer->email ?? '—' }}</span>
                </div>
                <div>
                    <span class="ui-field-label block text-slate-500">Billing address</span>
                    <span class="text-slate-800 break-words">{{ $customer->address ?? '—' }}</span>
                </div>
                <div>
                    <span class="ui-field-label block text-slate-500">Account opened</span>
                    <span class="text-slate-600">{{ $customer->created_at->format('d M Y') }}</span>
                </div>
            </div>

            <!-- Quick Edit Collapsible / Modal Trigger -->
            <details class="pt-3 border-t border-slate-100 text-xs" @if($errors->customerProfile->any()) open @endif>
                <summary class="font-semibold text-[#0a192f] hover:text-[#d4af37] cursor-pointer">Edit customer details</summary>
                <form action="{{ route('customers.update', $customer) }}" method="POST" class="mt-3 space-y-2.5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="profile-name" class="ui-field-label block text-slate-600">Client Name *</label>
                        <input id="profile-name" type="text" name="name" maxlength="255" required value="{{ old('name', $customer->name) }}" @if($errors->customerProfile->has('name')) aria-invalid="true" aria-describedby="profile-name-error" @endif class="w-full text-xs p-1.5 border {{ $errors->customerProfile->has('name') ? 'border-rose-500' : 'border-slate-300' }} rounded">
                        @error('name', 'customerProfile')<p id="profile-name-error" class="mt-1 text-[11px] text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile-company" class="ui-field-label block text-slate-600">Company Name</label>
                        <input id="profile-company" type="text" name="company_name" maxlength="255" value="{{ old('company_name', $customer->company_name) }}" @if($errors->customerProfile->has('company_name')) aria-invalid="true" @endif class="w-full text-xs p-1.5 border {{ $errors->customerProfile->has('company_name') ? 'border-rose-500' : 'border-slate-300' }} rounded">
                        @error('company_name', 'customerProfile')<p class="mt-1 text-[11px] text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile-phone" class="ui-field-label block text-slate-600">Phone</label>
                        <input id="profile-phone" type="tel" name="phone" maxlength="50" value="{{ old('phone', $customer->phone) }}" @if($errors->customerProfile->has('phone')) aria-invalid="true" @endif class="w-full text-xs p-1.5 border {{ $errors->customerProfile->has('phone') ? 'border-rose-500' : 'border-slate-300' }} rounded">
                        @error('phone', 'customerProfile')<p class="mt-1 text-[11px] text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile-email" class="ui-field-label block text-slate-600">Email</label>
                        <input id="profile-email" type="email" name="email" maxlength="255" value="{{ old('email', $customer->email) }}" @if($errors->customerProfile->has('email')) aria-invalid="true" aria-describedby="profile-email-error" @endif class="w-full text-xs p-1.5 border {{ $errors->customerProfile->has('email') ? 'border-rose-500' : 'border-slate-300' }} rounded">
                        @error('email', 'customerProfile')<p id="profile-email-error" class="mt-1 text-[11px] text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="profile-address" class="ui-field-label block text-slate-600">Address</label>
                        <textarea id="profile-address" name="address" rows="2" maxlength="10000" @if($errors->customerProfile->has('address')) aria-invalid="true" @endif class="w-full text-xs p-1.5 border {{ $errors->customerProfile->has('address') ? 'border-rose-500' : 'border-slate-300' }} rounded">{{ old('address', $customer->address) }}</textarea>
                        @error('address', 'customerProfile')<p class="mt-1 text-[11px] text-rose-700">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-1.5 rounded text-xs">
                        Update Customer
                    </button>
                </form>
            </details>
        </div>

        <!-- Right: 4 History Tabs (Quotations, Invoices, Delivery Notes, Receipts) -->
        <div class="lg:col-span-3 min-w-0 bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
            <!-- Tab Headers -->
            <div class="border-b border-slate-200 bg-slate-50/70 px-4 flex items-center gap-2 overflow-x-auto [&>button]:shrink-0 [&>button]:whitespace-nowrap" id="historyTabs" role="tablist" aria-label="Customer history">
                <button type="button" role="tab" aria-controls="content-invoices" aria-selected="true" onclick="switchTab('invoices')" id="tab-invoices" class="tab-btn py-3 px-3 text-xs font-bold border-b-2 border-[#0a192f] text-[#0a192f]">
                    Sales Invoices ({{ $customer->invoices_count }})
                </button>
                <button type="button" role="tab" aria-controls="content-quotes" aria-selected="false" onclick="switchTab('quotes')" id="tab-quotes" class="tab-btn py-3 px-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                    Quotations ({{ $customer->quotations_count }})
                </button>
                <button type="button" role="tab" aria-controls="content-delivery" aria-selected="false" onclick="switchTab('delivery')" id="tab-delivery" class="tab-btn py-3 px-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                    Delivery Notes ({{ $customer->delivery_notes_count }})
                </button>
                <button type="button" role="tab" aria-controls="content-receipts" aria-selected="false" onclick="switchTab('receipts')" id="tab-receipts" class="tab-btn py-3 px-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                    Payment Receipts ({{ $customer->receipts_count }})
                </button>
            </div>

            <!-- Tab Content: Invoices -->
            <div id="content-invoices" role="tabpanel" aria-labelledby="tab-invoices" class="history-content p-4">
                <div id="customer-invoices" class="overflow-x-auto scroll-mt-24">
                    <table class="ui-table w-full min-w-[650px] text-left">
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
                            @forelse($invoices as $inv)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 font-bold text-[#0a192f]">
                                        <a href="{{ route('invoices.show', $inv) }}" class="hover:underline">{{ $inv->invoice_number }}</a>
                                    </td>
                                    <td class="py-2.5 text-slate-600">{{ $inv->invoice_date->format('d M Y') }}</td>
                                    <td class="py-2.5">{{ $inv->total_qty }} units</td>
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
                <x-table-pagination :paginator="$invoices" label="Customer invoices" />
            </div>

            <!-- Tab Content: Quotations -->
            <div id="content-quotes" role="tabpanel" aria-labelledby="tab-quotes" class="history-content p-4 hidden">
                <div id="customer-quotations" class="overflow-x-auto scroll-mt-24">
                    <table class="ui-table w-full min-w-[650px] text-left">
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
                            @forelse($quotations as $quote)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 font-bold text-[#0a192f]">
                                        <a href="{{ route('quotations.show', $quote) }}" class="hover:underline">{{ $quote->quotation_number }}</a>
                                    </td>
                                    <td class="py-2.5 font-sans text-slate-700">{{ $quote->category }}</td>
                                    <td class="py-2.5 text-slate-600">{{ $quote->quotation_date->format('d M Y') }}</td>
                                    <td class="py-2.5">{{ $quote->total_qty }} units</td>
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
                <x-table-pagination :paginator="$quotations" label="Customer quotations" />
            </div>

            <!-- Tab Content: Delivery Notes -->
            <div id="content-delivery" role="tabpanel" aria-labelledby="tab-delivery" class="history-content p-4 hidden">
                <div id="customer-delivery" class="overflow-x-auto scroll-mt-24">
                    <table class="ui-table w-full min-w-[650px] text-left">
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
                            @forelse($deliveryNotes as $dn)
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
                <x-table-pagination :paginator="$deliveryNotes" label="Customer delivery notes" />
            </div>

            <!-- Tab Content: Receipts -->
            <div id="content-receipts" role="tabpanel" aria-labelledby="tab-receipts" class="history-content p-4 hidden">
                <div id="customer-receipts" class="overflow-x-auto scroll-mt-24">
                    <table class="ui-table w-full min-w-[580px] text-left">
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
                            @forelse($receipts as $rcpt)
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
                <x-table-pagination :paginator="$receipts" label="Customer payment receipts" />
            </div>
        </div>
    </div>

</div>

<script>
    const historyAnchors = {
        invoices: 'customer-invoices',
        quotes: 'customer-quotations',
        delivery: 'customer-delivery',
        receipts: 'customer-receipts',
    };

    function switchTab(tabName, updateAnchor = true) {
        if (!Object.hasOwn(historyAnchors, tabName)) return;
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-[#0a192f]', 'text-[#0a192f]');
            btn.classList.add('border-transparent', 'text-slate-500');
            btn.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.history-content').forEach(c => c.classList.add('hidden'));

        document.getElementById('tab-' + tabName).classList.add('border-[#0a192f]', 'text-[#0a192f]');
        document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-slate-500');
        document.getElementById('tab-' + tabName).setAttribute('aria-selected', 'true');
        document.getElementById('content-' + tabName).classList.remove('hidden');
        if (updateAnchor) {
            history.replaceState(history.state, '', location.pathname + location.search + '#' + historyAnchors[tabName]);
        }
    }

    function activateHistoryAnchor() {
        const tabName = Object.keys(historyAnchors).find(name => '#' + historyAnchors[name] === location.hash);
        if (!tabName) return;
        switchTab(tabName, false);
        requestAnimationFrame(() => document.getElementById(historyAnchors[tabName]).scrollIntoView({ block: 'start' }));
    }

    activateHistoryAnchor();
    window.addEventListener('hashchange', activateHistoryAnchor);

    document.getElementById('historyTabs').addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        const buttons = [...document.querySelectorAll('.tab-btn')];
        let next = buttons.indexOf(document.activeElement);
        if (next < 0) return;
        event.preventDefault();
        if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = buttons.length - 1;
        else next = (next + (event.key === 'ArrowRight' ? 1 : -1) + buttons.length) % buttons.length;
        buttons[next].focus();
        buttons[next].click();
    });
</script>
@endsection
