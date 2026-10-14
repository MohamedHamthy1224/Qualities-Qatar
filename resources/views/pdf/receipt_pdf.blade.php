<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payment Receipt {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 20px; }
        .receipt-card { border: 2px solid #0a192f; border-radius: 8px; padding: 20px; background-color: #ffffff; }
        .header-table { width: 100%; border-bottom: 2px solid #0a192f; padding-bottom: 15px; margin-bottom: 15px; }
        .logo-box { width: 45px; height: 45px; background-color: #0a192f; color: #d4af37; text-align: center; line-height: 45px; font-weight: bold; font-size: 24px; border-radius: 6px; float: left; margin-right: 12px; }
        .company-name { font-size: 15px; font-weight: bold; color: #0a192f; }
        .company-sub { font-size: 9px; color: #64748b; }
        .amount-box { background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px; border-radius: 6px; text-align: center; margin: 15px 0; }
        .amount-text { font-size: 24px; font-weight: bold; color: #15803d; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details-table td { padding: 6px 4px; font-size: 11px; border-bottom: 1px solid #f1f5f9; }
        .details-table td.label { font-weight: bold; color: #64748b; width: 35%; }
        .stamp-box { border: 2px dashed #15803d; color: #15803d; padding: 8px 16px; display: inline-block; font-weight: bold; font-size: 11px; text-transform: uppercase; float: right; border-radius: 6px; }
    </style>
</head>
<body>

    <div class="receipt-card">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 65%;">
                    <div class="logo-box">Q</div>
                    <div class="company-name">QUALITIES UNIFORMS & GIFTS</div>
                    <div class="company-sub">Salwa Road, Near Wholesale Market, Zone 56, Doha, State of Qatar</div>
                    <div class="company-sub">Official Cashier Payment Voucher</div>
                </td>
                <td style="width: 35%; text-align: right; vertical-align: top;">
                    <div style="font-size: 13px; font-weight: bold; color: #15803d;">PAYMENT RECEIPT</div>
                    <div style="font-size: 12px; font-weight: bold; color: #0a192f;">{{ $receipt->receipt_number }}</div>
                    <div style="font-size: 9px; color: #64748b;">Date: {{ $receipt->payment_date->format('d M Y') }}</div>
                </td>
            </tr>
        </table>

        <!-- Amount Highlight -->
        <div class="amount-box">
            <div style="font-size: 10px; font-weight: bold; color: #166534; text-transform: uppercase;">Amount Received in Full Confirmation:</div>
            <div class="amount-text">QAR {{ number_format($receipt->amount_received, 2) }}</div>
        </div>

        <!-- Details -->
        <table class="details-table">
            <tr>
                <td class="label">Received From Client:</td>
                <td><strong>{{ $receipt->customer->name }}</strong> @if($receipt->customer->company_name) ({{ $receipt->customer->company_name }}) @endif</td>
            </tr>
            <tr>
                <td class="label">Payment Method:</td>
                <td><strong>{{ $receipt->payment_method }}</strong> @if($receipt->reference_number) [Ref: {{ $receipt->reference_number }}] @endif</td>
            </tr>
            <tr>
                <td class="label">Allocated Invoice:</td>
                <td>
                    @if($receipt->invoice)
                        <strong>{{ $receipt->invoice->invoice_number }}</strong> (Original Grand Total: QAR {{ number_format($receipt->invoice->grand_total, 2) }})
                    @else
                        General Customer Account Balance
                    @endif
                </td>
            </tr>
            @if($receipt->notes)
                <tr>
                    <td class="label">Counter Notes:</td>
                    <td>{{ $receipt->notes }}</td>
                </tr>
            @endif
        </table>

        <!-- Stamp & Sign -->
        <div style="margin-top: 25px; clear: both;">
            <div style="float: left; width: 50%; font-size: 10px; color: #64748b;">
                Issued By: <strong>QUALITIES Billing Cashier Desk</strong><br>
                Salwa Road Branch, Doha, Qatar
            </div>
            <div class="stamp-box">
                PAYMENT RECEIVED & VERIFIED
            </div>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>
