<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation {{ $quotation->quotation_number }}</title>
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
        .text-right { text-align: right; }
        .totals-table { width: 45%; float: right; border-collapse: collapse; margin-bottom: 15px; }
        .totals-table td { padding: 4px 8px; font-size: 10px; }
        .grand-total-row { font-size: 13px; font-weight: bold; background-color: #0a192f; color: #ffffff; }
        .footer-note { clear: both; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 9px; color: #64748b; }
        .badge { display: inline-block; padding: 2px 6px; font-size: 9px; font-weight: bold; border-radius: 4px; background: #e2e8f0; }
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
                <div class="company-sub">Tel: +974 4455 1234 | WhatsApp: +974 5588 9922 | sales@qualitiesqatar.com</div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div style="font-size: 16px; font-weight: bold; color: #0a192f;">QUOTATION</div>
                <div style="font-size: 13px; font-weight: bold; color: #b5891d;">{{ $quotation->quotation_number }}</div>
                <div style="font-size: 10px; color: #64748b;">Date: {{ $quotation->quotation_date->format('d M Y') }}</div>
                <div style="margin-top: 4px;"><span class="badge">{{ strtoupper($quotation->status) }}</span></div>
            </td>
        </tr>
    </table>

    <!-- Customer & Meta -->
    <table class="meta-table">
        <tr>
            <td class="meta-box" style="width: 50%;">
                <div class="meta-title">Prepared For:</div>
                <div style="font-size: 12px; font-weight: bold; color: #0a192f;">{{ $quotation->customer->name }}</div>
                @if($quotation->customer->company_name)
                    <div><strong>{{ $quotation->customer->company_name }}</strong></div>
                @endif
                <div>Qatar Phone: {{ $quotation->customer->phone ?? '—' }}</div>
                <div>Location: {{ $quotation->customer->address ?? 'Doha, Qatar' }}</div>
            </td>
            <td style="width: 4%;"></td>
            <td class="meta-box" style="width: 46%;">
                <div class="meta-title">Order Specifications:</div>
                <div>Category: <strong>{{ $quotation->category }}</strong></div>
                <div>Target Delivery: <strong>{{ $quotation->delivery_date ? $quotation->delivery_date->format('d M Y') : '7 Working Days' }}</strong></div>
                <div>Total Volume: <strong>{{ $quotation->total_qty }} pieces</strong></div>
                <div>Payment Terms: <strong>50% Advance, 50% on Handover</strong></div>
            </td>
        </tr>
    </table>

    <!-- Items with Size Matrix -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 32%;">Item Description</th>
                <th class="text-center">XS</th>
                <th class="text-center">S</th>
                <th class="text-center">M</th>
                <th class="text-center">L</th>
                <th class="text-center">XL</th>
                <th class="text-center">2XL</th>
                <th class="text-center">3XL</th>
                <th class="text-center">Cust</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Unit (QAR)</th>
                <th class="text-right">Total (QAR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $item)
                <tr>
                    <td>
                        <span style="font-size: 7.5px; font-weight: bold; padding: 1px 4px; background: #e2e8f0; border-radius: 2px; color: #0a192f; text-transform: uppercase;">{{ $item->category }}</span><br>
                        <strong style="color: #0a192f; font-size: 10.5px;">{{ $item->product_name }}</strong>
                        @if($item->custom_size_notes)
                            <br><span style="font-size: 8px; color: #64748b;">Note: {{ $item->custom_size_notes }}</span>
                        @endif
                    </td>
                    @if($item->input_mode === 'garment_matrix')
                        <td class="text-center">{{ $item->size_xs ?: '—' }}</td>
                        <td class="text-center">{{ $item->size_s ?: '—' }}</td>
                        <td class="text-center">{{ $item->size_m ?: '—' }}</td>
                        <td class="text-center">{{ $item->size_l ?: '—' }}</td>
                        <td class="text-center">{{ $item->size_xl ?: '—' }}</td>
                        <td class="text-center">{{ $item->size_2xl ?: '—' }}</td>
                        <td class="text-center">{{ $item->size_3xl ?: '—' }}</td>
                        <td class="text-center">{{ $item->size_custom ?: '—' }}</td>
                    @else
                        <td colspan="8" class="text-center" style="font-style: italic; color: #64748b; background: #f8fafc;">
                            Flat Quantity ({{ $item->total_qty }} {{ $item->pricing_unit ?? 'units' }})
                        </td>
                    @endif
                    <td class="text-center" style="font-weight: bold;">{{ $item->total_qty }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Table -->
    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">QAR {{ number_format($quotation->subtotal, 2) }}</td>
        </tr>
        @if($quotation->setup_charges > 0)
            <tr>
                <td>Setup / Screen Fee:</td>
                <td class="text-right">QAR {{ number_format($quotation->setup_charges, 2) }}</td>
            </tr>
        @endif
        @if($quotation->print_charges > 0)
            <tr>
                <td>Printing / Heat Transfer:</td>
                <td class="text-right">QAR {{ number_format($quotation->print_charges, 2) }}</td>
            </tr>
        @endif
        @if($quotation->discount > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">- QAR {{ number_format($quotation->discount, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total-row">
            <td>Grand Total (QAR):</td>
            <td class="text-right">QAR {{ number_format($quotation->grand_total, 2) }}</td>
        </tr>
    </table>

    <!-- Footer Note -->
    <div class="footer-note">
        <strong>Validity:</strong> 30 days from date of issuance. Prices in Qatari Riyals (QAR).<br>
        <strong>QUALITIES Uniforms & Gifts Solutions</strong> • C.R. 128492 • Salwa Road, Doha, Qatar
    </div>

</body>
</html>
