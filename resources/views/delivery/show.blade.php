@extends('layouts.app')

@section('title', "Delivery Note {$deliveryNote->delivery_number}")
@section('page_title', "Delivery Note {$deliveryNote->delivery_number}")

@section('header_action')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('delivery.index') }}" class="text-xs text-slate-500 hover:text-slate-800 font-semibold px-2 py-1">
            &larr; All Delivery Notes
        </a>

        <!-- Print View -->
        <a href="{{ route('pdf.delivery', ['deliveryNote' => $deliveryNote->id, 'print' => 1]) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-300">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print Sign-off Voucher</span>
        </a>

        <!-- PDF Download -->
        <a href="{{ route('pdf.delivery', ['deliveryNote' => $deliveryNote->id]) }}" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-xs">
            <svg class="w-4 h-4 text-[#d4af37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Download PDF</span>
        </a>
    </div>
@endsection

@section('content')
<div class="ui-page pt-2 max-w-5xl mx-auto">

    <!-- Status & Action Bar -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            @php
                $statusColor = match($deliveryNote->status) {
                    'Delivered' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                    'Dispatched' => 'bg-sky-50 text-sky-800 border-sky-300',
                    'Pending' => 'bg-amber-50 text-amber-800 border-amber-300',
                    default => 'bg-slate-50 text-slate-700'
                };
            @endphp
            <div class="px-3 py-1 rounded-lg text-xs font-extrabold border {{ $statusColor }}">
                Status: {{ strtoupper($deliveryNote->status) }}
            </div>
            @if($deliveryNote->delivered_at)
                <div class="text-xs text-slate-500 font-mono-num">
                    Delivered on: <strong>{{ $deliveryNote->delivered_at->format('d M Y, h:i A') }}</strong>
                </div>
            @endif
        </div>

        <form action="{{ route('delivery.update-status', $deliveryNote) }}" method="POST" class="flex flex-wrap items-center gap-2">
            @csrf
            @method('PATCH')
            <span class="text-xs font-bold text-slate-500">Update Status:</span>
            <select name="status" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded px-2.5 py-1 font-semibold focus:outline-none">
                <option value="Pending" {{ $deliveryNote->status === 'Pending' ? 'selected' : '' }}>Pending Dispatch</option>
                <option value="Dispatched" {{ $deliveryNote->status === 'Dispatched' ? 'selected' : '' }}>Dispatched</option>
                <option value="Delivered" {{ $deliveryNote->status === 'Delivered' ? 'selected' : '' }}>Delivered & Signed</option>
            </select>
        </form>
    </div>

    <!-- Official Delivery Note Document -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-sm ui-section ui-page">
        <div class="ui-record-actions no-print">@yield('header_action')</div>

        <!-- Branded Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
            @include('documents.company')


            <div class="text-left sm:text-right">
                <div class="ui-field-label text-slate-500">Delivery note</div>
                <div class="text-2xl font-extrabold text-[#0a192f] font-mono-num break-all">{{ $deliveryNote->delivery_number }}</div>
                <div class="text-xs text-slate-500 font-mono-num mt-1">Dispatch: {{ $deliveryNote->dispatch_date->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Recipient & Dispatch Meta Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50/80 p-4 rounded-xl border border-slate-200/80 text-xs">
            <div>
                <span class="ui-field-label text-slate-500 block mb-1">Customer / recipient</span>
                <div class="font-bold text-sm text-slate-900">{{ $deliveryNote->recipient_name }}</div>
                <div class="font-semibold text-slate-700">{{ $deliveryNote->customer->name }}</div>
                <div class="text-slate-600 mt-1">Phone: <strong class="font-mono-num">{{ $deliveryNote->recipient_phone ?? $deliveryNote->customer->phone ?? '—' }}</strong></div>
                <div class="text-slate-600">Handover Location: {{ $deliveryNote->customer->address ?: '—' }}</div>
            </div>

            <div class="sm:text-right space-y-1">
                <span class="ui-field-label text-slate-500 block mb-1">Dispatch details</span>
                <div>Invoice Reference: <a href="{{ route('invoices.show', $deliveryNote->invoice_id) }}" class="text-[#0a192f] font-mono-num font-bold hover:underline">{{ $deliveryNote->invoice->invoice_number }}</a></div>
                <div>Driver / Vehicle: <strong class="text-slate-800">{{ $deliveryNote->driver_name ?: '—' }}</strong></div>
                <div>Dispatch Date: <strong class="font-mono-num text-slate-900">{{ $deliveryNote->dispatch_date->format('d M Y') }}</strong></div>
                <div>Total Handover Quantity: <strong class="font-mono-num text-slate-900">{{ $deliveryNote->items->sum('total_qty') }}</strong></div>
            </div>
        </div>

        <!-- Itemized Size Breakdown Matrix -->
        <div>
            <div class="flex flex-wrap gap-2 items-center justify-between mb-2">
                <h3 class="ui-section-heading text-slate-900">Items & sizes</h3>
                <span class="ui-help text-slate-500 font-mono-num">Total quantity: <strong>{{ $deliveryNote->items->sum('total_qty') }}</strong></span>
            </div>

            <div data-paginated-table-region class="border border-slate-200 rounded-lg">
                <div class="overflow-x-auto">
                <table id="deliveryDetailItemsTable" data-paginate-table data-pagination-label="delivery items" class="ui-table w-full text-left">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2.5">Item Description</th>
                            <th class="px-1.5 py-2.5 text-center">XS</th>
                            <th class="px-1.5 py-2.5 text-center">S</th>
                            <th class="px-1.5 py-2.5 text-center">M</th>
                            <th class="px-1.5 py-2.5 text-center">L</th>
                            <th class="px-1.5 py-2.5 text-center">XL</th>
                            <th class="px-1.5 py-2.5 text-center">2XL</th>
                            <th class="px-1.5 py-2.5 text-center">3XL</th>
                            <th class="px-1.5 py-2.5 text-center">Custom</th>
                            <th class="px-3 py-2.5 text-center bg-slate-200/60 font-mono-num font-bold">Qty Handed Over</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-mono-num">
                        @foreach($deliveryNote->items as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2.5 font-sans font-bold text-slate-900">
                                    {{ $item->product_name }}
                                </td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_xs > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_xs }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_s > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_s }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_m > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_m }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_l > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_l }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_xl > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_xl }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_2xl > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_2xl }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_3xl > 0 ? 'font-bold text-slate-900 bg-sky-50/50' : 'text-slate-300' }}">{{ $item->size_3xl }}</td>
                                <td class="px-1.5 py-2.5 text-center {{ $item->size_custom > 0 ? 'font-bold text-purple-700 bg-purple-50' : 'text-slate-300' }}">{{ $item->size_custom }}</td>
                                <td class="px-3 py-2.5 text-center bg-slate-50 font-bold text-slate-900">{{ $item->total_qty }} {{ $item->invoiceItem?->pricing_unit ?: '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        @if($deliveryNote->driver_notes)
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                <span class="ui-field-label text-slate-500 block mb-1">Dispatch instructions</span>
                <p class="text-slate-700">{{ $deliveryNote->driver_notes }}</p>
            </div>
        @endif

        <!-- Customer Handover Sign-off Box -->
        <div class="pt-4 border-t-2 border-dashed border-slate-300 grid grid-cols-1 sm:grid-cols-2 gap-8 text-xs">
            <div class="border border-slate-200 rounded-xl p-4 space-y-3 bg-slate-50/50">
                <span class="ui-field-label text-slate-700 block">Dispatched by</span>
                <div class="pt-8 border-b border-slate-300"></div>
                <div class="flex justify-between text-slate-500 text-[11px]">
                    <span>Driver / Logistics Officer Name</span>
                    <span>Date & Time</span>
                </div>
            </div>

            <div class="border border-slate-200 rounded-xl p-4 space-y-3 bg-slate-50/50">
                <span class="ui-field-label text-slate-700 block">Customer sign-off</span>
                <div class="ui-help text-slate-500">I confirm receipt of the listed items in good condition and the stated sizes.</div>
                <div class="pt-6 border-b border-slate-300"></div>
                <div class="flex justify-between text-slate-500 text-[11px]">
                    <span>Receiver Full Name & QID/Mobile</span>
                    <span>Signature & Official Stamp</span>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
