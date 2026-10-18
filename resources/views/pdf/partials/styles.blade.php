<style>
    @page { margin: 14mm 12mm; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; line-height: 1.45; color: #1e293b; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; overflow-wrap: anywhere; word-wrap: break-word; }
    .header { border-bottom: 2px solid #10243e; margin-bottom: 18px; }
    .header td { padding-bottom: 14px; }
    .company-name { font-size: 14px; font-weight: bold; color: #10243e; }
    .company-details { color: #526176; font-size: 9px; margin-top: 3px; }
    .logo { display: block; width: 160px; height: auto; margin-bottom: 7px; }
    .monogram { display: inline-block; padding: 3px 10px; margin-bottom: 5px; background: #10243e; color: #e6c260; font-size: 21px; font-weight: bold; }
    .document-type { text-transform: uppercase; letter-spacing: 1px; font-weight: bold; font-size: 13px; color: #10243e; }
    .document-number { font-size: 12px; font-weight: bold; margin: 5px 0; }
    .right { text-align: right; }
    .center { text-align: center; }
    .muted { color: #64748b; }
    .strong { font-weight: bold; }
    .meta { margin-bottom: 17px; }
    .meta td { padding: 10px 12px; background: #f3f6fa; border: 1px solid #dfe6ee; }
    .meta td.spacer { padding: 0; width: 3%; border: 0; background: white; }
    .label { font-size: 8px; text-transform: uppercase; letter-spacing: .6px; color: #526176; font-weight: bold; margin-bottom: 4px; }
    .customer-name { font-size: 12px; font-weight: bold; color: #10243e; }
    .items { margin-bottom: 14px; table-layout: fixed; }
    .items thead { display: table-header-group; }
    .items th { background: #10243e; color: white; padding: 8px 7px; font-size: 9px; }
    .items td { padding: 10px 7px; border-bottom: 1px solid #dfe6ee; font-size: 9px; }
    .items tr { page-break-inside: avoid; }
    .item-name { font-weight: bold; font-size: 10px; color: #10243e; }
    .item-detail { font-size: 8px; color: #526176; margin-top: 3px; }
    .category { font-size: 8px; color: #64748b; margin-bottom: 2px; }
    .money { white-space: nowrap; }
    .totals-wrap { margin-top: 8px; page-break-inside: avoid; }
    .totals { width: 47%; margin-left: auto; }
    .totals td { padding: 5px 8px; font-size: 10px; }
    .totals .grand td { background: #10243e; color: white; font-weight: bold; font-size: 11px; padding: 8px; }
    .totals .balance td { background: #edf4fa; font-weight: bold; border-top: 1px solid #c9d7e5; }
    .notes { margin-top: 15px; padding: 10px 12px; background: #f3f6fa; border-left: 3px solid #d4af37; white-space: pre-line; }
    .footer { border-top: 1px solid #dfe6ee; margin-top: 24px; padding-top: 9px; color: #64748b; font-size: 8px; }
    .signatures { margin-top: 26px; page-break-inside: avoid; }
    .signatures td { width: 48%; padding: 10px; border: 1px solid #dfe6ee; }
    .signatures .spacer { width: 4%; border: 0; }
    .signature-line { margin-top: 40px; border-top: 1px solid #94a3b8; padding-top: 5px; color: #64748b; font-size: 9px; }
    .amount-box { text-align: center; background: #effaf4; border: 1px solid #b9dec8; padding: 14px; margin-bottom: 18px; }
    .amount-box strong { font-size: 24px; color: #166534; }
    .details td { padding: 8px; border-bottom: 1px solid #dfe6ee; }
    .details .detail-label { width: 30%; color: #64748b; }
    .preview { background: #edf1f6; padding: 24px 12px; }
    .preview .sheet { background: white; max-width: 210mm; min-height: 270mm; margin: 0 auto; padding: 14mm 12mm; box-shadow: 0 3px 20px #ccd3dd; }
    .print-controls { max-width: 210mm; margin: 0 auto 15px; padding: 10px 14px; background: #10243e; color: white; font: 12px Arial, sans-serif; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
    .print-controls button { cursor: pointer; background: #e6c260; color: #10243e; border: 0; border-radius: 3px; padding: 7px 10px; font-weight: bold; }
    @media screen and (max-width: 600px) { .preview .sheet { padding: 18px 12px; } .company-name { font-size: 12px; } .document-type { font-size: 11px; } .items th, .items td { padding: 6px 4px; } .totals { width: 70%; } }
    @media print { .no-print { display: none !important; } .preview { background: white; padding: 0; } .preview .sheet { max-width: none; min-height: 0; padding: 0; box-shadow: none; } }
</style>
