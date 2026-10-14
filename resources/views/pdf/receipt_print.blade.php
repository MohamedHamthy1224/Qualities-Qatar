<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Receipt {{ $receipt->receipt_number }} — QUALITIES</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            @page { margin: 15mm; size: A5 landscape; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-8 font-sans antialiased text-slate-800">

    <!-- Print Control Bar -->
    <div class="max-w-2xl mx-auto mb-4 bg-slate-900 text-white px-4 py-2.5 rounded-lg flex items-center justify-between no-print shadow-md">
        <div class="flex items-center gap-2 text-xs font-bold">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
            <span>Payment Receipt Print Preview — {{ $receipt->receipt_number }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="bg-[#d4af37] text-slate-950 font-extrabold text-xs px-3 py-1.5 rounded shadow-sm hover:bg-[#c59b27]">
                Print Receipt
            </button>
            <button onclick="window.close()" class="bg-slate-800 text-slate-300 hover:text-white text-xs px-2.5 py-1.5 rounded">
                Close
            </button>
        </div>
    </div>

    <!-- Receipt Container -->
    <div class="max-w-2xl mx-auto bg-white p-6 sm:p-8 rounded-xl shadow-lg border-2 border-slate-900">
        <!-- Header -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-4 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#0a192f] text-white flex items-center justify-center font-extrabold text-xl border border-[#d4af37]">
                    Q
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-slate-900 tracking-tight">QUALITIES UNIFORMS & GIFTS SOLUTIONS</h1>
                    <p class="text-[10px] text-slate-500">Salwa Road, Near Wholesale Market, Zone 56, Doha, Qatar</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] font-bold text-emerald-700 uppercase tracking-widest">PAYMENT RECEIPT</div>
                <div class="text-base font-extrabold text-[#0a192f] font-mono-num">{{ $receipt->receipt_number }}</div>
                <div class="text-[10px] text-slate-500 font-mono-num">Date: {{ $receipt->payment_date->format('d M Y') }}</div>
            </div>
        </div>

        <!-- Highlight -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-center mb-4">
            <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Amount Received in Full Confirmation</span>
            <div class="text-3xl font-black text-emerald-800 font-mono-num mt-1">
                QAR {{ number_format($receipt->amount_received, 2) }}
            </div>
        </div>

        <!-- Details Grid -->
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-xs space-y-2 mb-4">
            <div class="grid grid-cols-3">
                <span class="font-bold text-slate-500">Received From:</span>
                <span class="col-span-2 font-bold text-slate-900">{{ $receipt->customer->name }}</span>
            </div>
            <div class="grid grid-cols-3">
                <span class="font-bold text-slate-500">Payment Method:</span>
                <span class="col-span-2 font-semibold text-slate-800">{{ $receipt->payment_method }} @if($receipt->reference_number) (Ref: {{ $receipt->reference_number }}) @endif</span>
            </div>
            <div class="grid grid-cols-3">
                <span class="font-bold text-slate-500">Allocated Invoice:</span>
                <span class="col-span-2 font-mono-num font-semibold text-slate-800">{{ $receipt->invoice ? $receipt->invoice->invoice_number : 'Customer Outstanding Balance' }}</span>
            </div>
            @if($receipt->notes)
                <div class="grid grid-cols-3">
                    <span class="font-bold text-slate-500">Notes:</span>
                    <span class="col-span-2 text-slate-700">{{ $receipt->notes }}</span>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-200 text-xs text-slate-500">
            <div>
                <div>Issued By: <strong>QUALITIES Billing Counter</strong></div>
                <div class="text-[10px] text-slate-400">Salwa Road, Doha, Qatar</div>
            </div>
            <div>
                <div class="border-2 border-emerald-600/70 text-emerald-800 font-extrabold text-[10px] px-3 py-1 rounded tracking-wider uppercase">
                    PAID & VERIFIED
                </div>
            </div>
        </div>
    </div>

</body>
</html>
