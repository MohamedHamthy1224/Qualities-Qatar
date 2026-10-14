<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Delivery Note {{ $deliveryNote->delivery_number }} — QUALITIES</title>
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
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
            <span>Delivery Note Print Preview — {{ $deliveryNote->delivery_number }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="bg-[#d4af37] text-slate-950 font-extrabold text-xs px-3 py-1.5 rounded shadow-sm hover:bg-[#c59b27]">
                Print Handover Sheet
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
                    <p class="text-[10px] text-slate-400">Tel: +974 4455 1234 | logistics@qualitiesqatar.com</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">DELIVERY HANDOVER NOTE</div>
                <div class="text-xl font-extrabold text-[#0a192f] font-mono-num">{{ $deliveryNote->delivery_number }}</div>
                <div class="text-xs text-slate-500 font-mono-num mt-1">Date: {{ $deliveryNote->dispatch_date->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Recipient & Meta -->
        <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-lg border border-slate-200 text-xs mb-6">
            <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">RECIPIENT & CLIENT:</span>
                <div class="font-bold text-slate-900 text-sm">{{ $deliveryNote->recipient_name }}</div>
                <div class="font-semibold text-slate-700">{{ $deliveryNote->customer->name }}</div>
                <div class="text-slate-600 mt-1">Qatar Phone: <strong class="font-mono-num">{{ $deliveryNote->recipient_phone ?? $deliveryNote->customer->phone ?? '—' }}</strong></div>
                <div class="text-slate-600">Location: {{ $deliveryNote->customer->address ?? 'Doha, Qatar' }}</div>
            </div>
            <div class="text-right space-y-1">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">DISPATCH LOGISTICS:</span>
                <div>Invoice Reference: <strong class="font-mono-num text-[#0a192f]">{{ $deliveryNote->invoice->invoice_number }}</strong></div>
                <div>Driver: <strong class="text-slate-800">{{ $deliveryNote->driver_name ?? 'Delivery Van 04' }}</strong></div>
                <div>Total Packaged Units: <strong class="font-mono-num text-slate-900">{{ $deliveryNote->items->sum('total_qty') }} pcs</strong></div>
                <div>Status: <span class="font-bold uppercase text-slate-900">{{ $deliveryNote->status }}</span></div>
            </div>
        </div>

        <!-- Size Matrix Table -->
        <table class="w-full text-left text-xs border border-slate-200 rounded-lg mb-6">
            <thead class="bg-slate-900 text-white font-bold">
                <tr>
                    <th class="p-2 w-72">Uniform Description</th>
                    <th class="p-2 text-center">XS</th>
                    <th class="p-2 text-center">S</th>
                    <th class="p-2 text-center">M</th>
                    <th class="p-2 text-center">L</th>
                    <th class="p-2 text-center">XL</th>
                    <th class="p-2 text-center">2XL</th>
                    <th class="p-2 text-center">3XL</th>
                    <th class="p-2 text-center">Cust</th>
                    <th class="p-2 text-center font-mono-num bg-slate-800">Total Handed Over</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 font-mono-num">
                @foreach($deliveryNote->items as $item)
                    <tr>
                        <td class="p-2 font-sans font-bold text-slate-900">{{ $item->product_name }}</td>
                        <td class="p-2 text-center">{{ $item->size_xs ?: '—' }}</td>
                        <td class="p-2 text-center">{{ $item->size_s ?: '—' }}</td>
                        <td class="p-2 text-center">{{ $item->size_m ?: '—' }}</td>
                        <td class="p-2 text-center">{{ $item->size_l ?: '—' }}</td>
                        <td class="p-2 text-center">{{ $item->size_xl ?: '—' }}</td>
                        <td class="p-2 text-center">{{ $item->size_2xl ?: '—' }}</td>
                        <td class="p-2 text-center">{{ $item->size_3xl ?: '—' }}</td>
                        <td class="p-2 text-center">{{ $item->size_custom ?: '—' }}</td>
                        <td class="p-2 text-center font-bold bg-slate-50">{{ $item->total_qty }} pcs</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($deliveryNote->driver_notes)
            <div class="bg-slate-50 p-3 rounded border border-slate-200 text-xs mb-6">
                <span class="font-bold text-slate-800 block text-[10px] uppercase">Driver Handover Notes:</span>
                <p>{{ $deliveryNote->driver_notes }}</p>
            </div>
        @endif

        <!-- Customer Handover Sign-off Box -->
        <div class="pt-8 border-t-2 border-dashed border-slate-400 grid grid-cols-2 gap-8 text-xs">
            <div class="border border-slate-300 rounded-lg p-4 bg-slate-50/50">
                <span class="font-bold text-slate-800 block uppercase tracking-wider text-[10px]">Dispatched By (Logistics):</span>
                <div class="pt-12 border-b border-slate-400"></div>
                <div class="flex justify-between text-slate-500 text-[11px] mt-1">
                    <span>Driver / Courier Signature</span>
                    <span>Date & Time</span>
                </div>
            </div>

            <div class="border border-slate-300 rounded-lg p-4 bg-slate-50/50">
                <span class="font-bold text-slate-800 block uppercase tracking-wider text-[10px]">Customer Receipt & Sign-off:</span>
                <div class="text-[10px] text-slate-500">I confirm receipt of all above items in sound condition & exact sizes.</div>
                <div class="pt-10 border-b border-slate-400"></div>
                <div class="flex justify-between text-slate-500 text-[11px] mt-1">
                    <span>Receiver Name, QID & Mobile</span>
                    <span>Signature & Official Stamp</span>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
