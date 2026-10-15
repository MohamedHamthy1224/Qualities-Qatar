<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>
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
        .grand-total-row { font-size: 12px; font-weight: bold; background-color: #0a192f; color: #ffffff; }
        .balance-row { font-size: 13px; font-weight: bold; background-color: #e2e8f0; color: #0a192f; }
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
                <div class="company-sub">C.R. No: 128492 | Tel: +974 4455 1234 | billing@qualitiesqatar.com</div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <div style="font-size: 16px; font-weight: bold; color: #0a192f;">OFFICIAL TAX INVOICE</div>
                <div style="font-size: 13px; font-weight: bold; color: #0a192f;">{{ $invoice->invoice_number }}</div>
                <div style="font-size: 10px; color: #64748b;">Date: {{ $invoice->invoice_date->format('d M Y') }}</div>
                <div style="margin-top: 4px;"><span class="badge">{{ strtoupper($invoice->status) }}</span></div>
            </td>
        </tr>
    </table>

    <!-- Customer & Meta -->
    <table class="meta-table">
        <tr>
            <td class="meta-box" style="width: 50%;">
                <div class="meta-title">Billed To:</div>
                <div style="font-size: 12px; font-weight: bold; color: #0a192f;">{{ $invoice->customer->name }}</div>
                @if($invoice->customer->company_name)
                    <div><strong>{{ $invoice->customer->company_name }}</strong></div>
                @endif
                <div>Qatar Phone: {{ $invoice->customer->phone ?? '—' }}</div>
                <div>Location: {{ $invoice->customer->address ?? 'Doha, Qatar' }}</div>
            </td>
            <td style="width: 4%;"></td>
            <td class="meta-box" style="width: 46%;">
                <div class="meta-title">Payment Terms:</div>
                <div>Invoice Date: <strong>{{ $invoice->invoice_date->format('d M Y') }}</strong></div>
                <div>Due Date: <strong>{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'Due on Handover' }}</strong></div>
                <div>Total Volume: <strong>{{ $invoice->total_qty }} pieces</strong></div>
                <div>Bank: <strong>Qatar National Bank (QNB) - Salwa Branch</strong></div>
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
            @foreach($invoice->items as $item)
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
            <td class="text-right">QAR {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->discount > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">- QAR {{ number_format($invoice->discount, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total-row">
            <td>Grand Total (QAR):</td>
            <td class="text-right">QAR {{ number_format($invoice->grand_total, 2) }}</td>
        </tr>
        <tr>
            <td>Amount Paid:</td>
            <td class="text-right" style="color: #15803d; font-weight: bold;">QAR {{ number_format($invoice->paid_amount, 2) }}</td>
        </tr>
        <tr class="balance-row">
            <td>Balance Due (QAR):</td>
            <td class="text-right" style="color: #b91c1c;">QAR {{ number_format($invoice->balance_due, 2) }}</td>
        </tr>
    </table>

    <!-- Bank Details & Footer -->
    <div class="footer-note">
        <strong>Bank Wire Instructions:</strong> Qatar National Bank (QNB) | A/C: QUALITIES UNIFORMS & GIFTS W.L.L | IBAN: QA92 QNBA 0000 0000 1284 9200 11<br>
        Thank you for your business. Certified authentic document.
    </div>

</body>
</html>
