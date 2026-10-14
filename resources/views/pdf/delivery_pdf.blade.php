<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Delivery Note {{ $deliveryNote->delivery_number }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 15px; }
        .header-table { width: 100%; border-bottom: 2px solid #0a192f; padding-bottom: 15px; margin-bottom: 15px; }
        .logo-box { width: 50px; height: 50px; background-color: #0a192f; color: #d4af37; text-align: center; line-height: 50px; font-weight: bold; font-size: 26px; border-radius: 6px; float: left; margin-right: 12px; }
        .company-name { font-size: 16px; font-weight: bold; color: #0a192f; }
        .company-sub { font-size: 10px; color: #64748b; }
        .meta-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .meta-box { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; border-radius: 6px; vertical-align: top; }
        .meta-title { font-size: 9px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th { background-color: #0a192f; color: #ffffff; padding: 6px; font-size: 10px; text-align: left; }
        .items-table td { padding: 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .text-center { text-align: center; }
        .sign-table { width: 100%; border-collapse: collapse; margin-top: 25px; }
        .sign-box { border: 1px solid #cbd5e1; background-color: #f8fafc; padding: 12px; height: 90px; vertical-align: top; width: 48%; }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="logo-box">Q</div>
                <div class="company-name">QUALITIES UNIFORMS & GIFTS</div>
                <div class="company-sub">Salwa Road, Near Wholesale Market, Zone 56, Doha, State of Qatar</div>
                <div class="company-sub">Tel: +974 4455 1234 | logistics@qualitiesqatar.com</div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div style="font-size: 15px; font-weight: bold; color: #0a192f;">DELIVERY HANDOVER NOTE</div>
                <div style="font-size: 13px; font-weight: bold; color: #0a192f;">{{ $deliveryNote->delivery_number }}</div>
                <div style="font-size: 10px; color: #64748b;">Dispatch Date: {{ $deliveryNote->dispatch_date->format('d M Y') }}</div>
                <div style="font-size: 10px; color: #0a192f;">Invoice: <strong>{{ $deliveryNote->invoice->invoice_number }}</strong></div>
            </td>
        </tr>
    </table>

    <!-- Customer & Logistics -->
    <table class="meta-table">
        <tr>
            <td class="meta-box" style="width: 50%;">
                <div class="meta-title">Delivered To:</div>
                <div style="font-size: 12px; font-weight: bold; color: #0a192f;">{{ $deliveryNote->recipient_name }}</div>
                <div>Organization: <strong>{{ $deliveryNote->customer->name }}</strong></div>
                <div>Qatar Phone: {{ $deliveryNote->recipient_phone ?? $deliveryNote->customer->phone ?? '—' }}</div>
                <div>Address: {{ $deliveryNote->customer->address ?? 'Doha, Qatar' }}</div>
            </td>
            <td style="width: 4%;"></td>
            <td class="meta-box" style="width: 46%;">
                <div class="meta-title">Handover Details:</div>
                <div>Driver / Vehicle: <strong>{{ $deliveryNote->driver_name ?? 'Delivery Van 04' }}</strong></div>
                <div>Dispatch Status: <strong>{{ strtoupper($deliveryNote->status) }}</strong></div>
                <div>Total Dispatched: <strong>{{ $deliveryNote->items->sum('total_qty') }} pieces</strong></div>
                @if($deliveryNote->driver_notes)
                    <div style="font-size: 9px; color: #64748b; margin-top: 4px;">Notes: {{ $deliveryNote->driver_notes }}</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Items with Size Breakdown -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Uniform Description</th>
                <th class="text-center">XS</th>
                <th class="text-center">S</th>
                <th class="text-center">M</th>
                <th class="text-center">L</th>
                <th class="text-center">XL</th>
                <th class="text-center">2XL</th>
                <th class="text-center">3XL</th>
                <th class="text-center">Cust</th>
                <th class="text-center">Total Pcs</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveryNote->items as $item)
                <tr>
                    <td><strong>{{ $item->product_name }}</strong></td>
                    <td class="text-center">{{ $item->size_xs ?: '—' }}</td>
                    <td class="text-center">{{ $item->size_s ?: '—' }}</td>
                    <td class="text-center">{{ $item->size_m ?: '—' }}</td>
                    <td class="text-center">{{ $item->size_l ?: '—' }}</td>
                    <td class="text-center">{{ $item->size_xl ?: '—' }}</td>
                    <td class="text-center">{{ $item->size_2xl ?: '—' }}</td>
                    <td class="text-center">{{ $item->size_3xl ?: '—' }}</td>
                    <td class="text-center">{{ $item->size_custom ?: '—' }}</td>
                    <td class="text-center" style="font-weight: bold; background-color: #f1f5f9;">{{ $item->total_qty }} pcs</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Physical Sign-off Handover Box -->
    <table class="sign-table">
        <tr>
            <td class="sign-box">
                <div style="font-size: 9px; font-weight: bold; color: #475569; text-transform: uppercase;">Dispatched By (Logistics):</div>
                <div style="margin-top: 45px; border-bottom: 1px solid #94a3b8;"></div>
                <div style="font-size: 9px; color: #64748b; margin-top: 4px;">Driver Name & Signature</div>
            </td>
            <td style="width: 4%;"></td>
            <td class="sign-box">
                <div style="font-size: 9px; font-weight: bold; color: #475569; text-transform: uppercase;">Received By (Customer Handover):</div>
                <div style="font-size: 8px; color: #64748b;">Received in good condition and correct sizes.</div>
                <div style="margin-top: 35px; border-bottom: 1px solid #94a3b8;"></div>
                <div style="font-size: 9px; color: #64748b; margin-top: 4px;">Receiver Name, Qatar ID/Mobile & Official Stamp</div>
            </td>
        </tr>
    </table>

</body>
</html>
