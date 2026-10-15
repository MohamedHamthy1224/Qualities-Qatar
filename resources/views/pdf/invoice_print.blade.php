<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Invoice {{ $invoice->invoice_number }} — QUALITIES</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            @page { margin: 15mm; size: A4 portrait; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 font-sans antialiased text-slate-800">

    <!-- Print Control Bar -->
    <div class="max-w-4xl mx-auto mb-4 bg-slate-900 text-white px-4 py-2.5 rounded-lg flex items-center justify-between no-print shadow-md">
        <div class="flex items-center gap-2 text-xs font-bold">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
            <span>A4 Invoice Print Preview — {{ $invoice->invoice_number }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="bg-[#d4af37] text-slate-950 font-extrabold text-xs px-3 py-1.5 rounded shadow-sm hover:bg-[#c59b27]">
                Print Invoice
            </button>
            <button onclick="window.close()" class="bg-slate-800 text-slate-300 hover:text-white text-xs px-2.5 py-1.5 rounded">
                Close
            </button>
        </div>
    </div>

    <!-- A4 Sheet Container -->
    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-xl shadow-lg border border-slate-200">
        <!-- Branded Header -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-5 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-[#0a192f] text-white flex items-center justify-center font-extrabold text-2xl border-2 border-[#d4af37]">
                    Q
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-slate-900 tracking-tight">QUALITIES UNIFORMS & GIFTS SOLUTIONS</h1>
                    <p class="text-[11px] text-slate-500">Salwa Road, Near Wholesale Market, Zone 56, Doha, State of Qatar</p>
                    <p class="text-[10px] text-slate-400">C.R. No: 128492 | Tel: +974 4455 1234 | billing@qualitiesqatar.com</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">OFFICIAL TAX INVOICE</div>
                <div class="text-xl font-extrabold text-[#0a192f] font-mono-num">{{ $invoice->invoice_number }}</div>
                <div class="text-xs text-slate-500 font-mono-num mt-1">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Client & Meta -->
        <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-lg border border-slate-200 text-xs mb-6">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">BILLED TO:</span>
                <div class="font-bold text-slate-900 text-sm">{{ $invoice->customer->name }}</div>
                @if($invoice->customer->company_name)
                    <div class="font-semibold text-slate-700">{{ $invoice->customer->company_name }}</div>
                @endif
                <div class="text-slate-600 mt-1">Qatar Phone: <strong class="font-mono-num">{{ $invoice->customer->phone ?? '—' }}</strong></div>
                <div class="text-slate-600">Location: {{ $invoice->customer->address ?? 'Doha, Qatar' }}</div>
            </div>
            <div class="text-right space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">TERMS:</span>
                <div>Invoice Date: <strong class="font-mono-num">{{ $invoice->invoice_date->format('d M Y') }}</strong></div>
                <div>Due Date: <strong class="font-mono-num">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'Due on Delivery' }}</strong></div>
                <div>Payment Status: <span class="font-bold uppercase text-[#0a192f]">{{ $invoice->status }}</span></div>
            </div>
        </div>

        <!-- Size Matrix Table -->
        <table class="w-full text-left text-xs border border-slate-200 rounded-lg mb-6">
            <thead class="bg-slate-900 text-white font-bold">
                <tr>
                    <th class="p-2">Description</th>
                    <th class="p-2 text-center">XS</th>
                    <th class="p-2 text-center">S</th>
                    <th class="p-2 text-center">M</th>
                    <th class="p-2 text-center">L</th>
                    <th class="p-2 text-center">XL</th>
                    <th class="p-2 text-center">2XL</th>
                    <th class="p-2 text-center">3XL</th>
                    <th class="p-2 text-center">Cust</th>
                    <th class="p-2 text-center font-mono-num bg-slate-800">Qty</th>
                    <th class="p-2 text-right font-mono-num">Unit (QAR)</th>
                    <th class="p-2 text-right font-mono-num bg-slate-800">Total (QAR)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 font-mono-num">
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="p-2 font-sans">
                            <span class="inline-block text-[8px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider bg-slate-100 text-slate-800 border border-slate-300">{{ $item->category }}</span>
                            <div class="font-bold text-slate-900 text-xs mt-0.5">{{ $item->product_name }}</div>
                            @if($item->custom_size_notes)
                                <div class="text-[10px] text-slate-500 font-normal">Spec: {{ $item->custom_size_notes }}</div>
                            @endif
                        </td>
                        @if($item->input_mode === 'garment_matrix')
                            <td class="p-2 text-center">{{ $item->size_xs ?: '—' }}</td>
                            <td class="p-2 text-center">{{ $item->size_s ?: '—' }}</td>
                            <td class="p-2 text-center">{{ $item->size_m ?: '—' }}</td>
                            <td class="p-2 text-center">{{ $item->size_l ?: '—' }}</td>
                            <td class="p-2 text-center">{{ $item->size_xl ?: '—' }}</td>
                            <td class="p-2 text-center">{{ $item->size_2xl ?: '—' }}</td>
                            <td class="p-2 text-center">{{ $item->size_3xl ?: '—' }}</td>
                            <td class="p-2 text-center">{{ $item->size_custom ?: '—' }}</td>
                        @else
                            <td colspan="8" class="p-2 text-center text-slate-500 italic font-sans bg-slate-50/50">
                                Flat Quantity: <strong>{{ $item->total_qty }} {{ $item->pricing_unit ?? 'units' }}</strong>
                            </td>
                        @endif
                        <td class="p-2 text-center font-bold bg-slate-50">{{ $item->total_qty }}</td>
                        <td class="p-2 text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="p-2 text-right font-bold bg-slate-50">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals & Bank Wire -->
        <div class="grid grid-cols-2 gap-8 pt-2">
            <div class="bg-slate-50 p-4 rounded border border-slate-200 text-xs space-y-1">
                <span class="font-bold text-slate-800 block text-[10px] uppercase">Wire Payment Information:</span>
                <div>Qatar National Bank (QNB) - Salwa Branch</div>
                <div>Account: QUALITIES UNIFORMS & GIFTS W.L.L</div>
                <div>IBAN: <strong>QA92 QNBA 0000 0000 1284 9200 11</strong></div>
            </div>

            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-xs space-y-1.5 font-mono-num">
                <div class="flex justify-between text-slate-600 font-sans">
                    <span>Subtotal:</span>
                    <span>QAR {{ number_format($invoice->subtotal, 2) }}</span>
                </div>
                @if($invoice->discount > 0)
                    <div class="flex justify-between text-emerald-700 font-sans">
                        <span>Discount:</span>
                        <span>- QAR {{ number_format($invoice->discount, 2) }}</span>
                    </div>
                @endif
                <div class="border-t border-slate-300 pt-2 flex justify-between font-sans font-bold text-slate-900">
                    <span>Grand Total:</span>
                    <span>QAR {{ number_format($invoice->grand_total, 2) }}</span>
                </div>
                <div class="flex justify-between text-emerald-700 font-sans font-semibold">
                    <span>Paid to Date:</span>
                    <span>QAR {{ number_format($invoice->paid_amount, 2) }}</span>
                </div>
                <div class="border-t-2 border-slate-400 pt-2 flex justify-between font-sans font-black text-sm text-slate-900">
                    <span>Balance Due (QAR):</span>
                    <span class="font-mono-num text-base {{ $invoice->balance_due > 0 ? 'text-rose-700' : 'text-emerald-700' }}">QAR {{ number_format($invoice->balance_due, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Stamp -->
        <div class="pt-8 border-t border-slate-200 grid grid-cols-2 gap-8 text-xs text-slate-500 mt-6">
            <div>
                <div class="pt-10 border-b border-slate-300"></div>
                <div class="mt-1">Authorized Billing Signature</div>
            </div>
            <div class="text-right">
                <div class="inline-block border-2 border-slate-400 rounded-lg px-4 py-2 text-slate-600 font-extrabold text-[11px] tracking-wider uppercase">
                    QUALITIES OFFICIAL STAMP
                </div>
            </div>
        </div>
    </div>

</body>
</html>
