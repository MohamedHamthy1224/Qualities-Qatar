<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\DeliveryNote;
use App\Models\CustomerReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    /**
     * Download or stream Quotation PDF
     */
    public function quotation(Quotation $quotation, Request $request)
    {
        $quotation->load(['customer', 'items']);

        if ($request->has('print')) {
            return view('pdf.quotation_print', compact('quotation'));
        }

        $pdf = Pdf::loadView('pdf.quotation_pdf', compact('quotation'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Quotation_{$quotation->quotation_number}.pdf");
    }

    /**
     * Download or stream Sales Invoice PDF
     */
    public function invoice(SalesInvoice $invoice, Request $request)
    {
        $invoice->load(['customer', 'items', 'receipts']);

        if ($request->has('thermal')) {
            return view('pdf.thermal_receipt', compact('invoice'));
        }

        if ($request->has('print')) {
            return view('pdf.invoice_print', compact('invoice'));
        }

        $pdf = Pdf::loadView('pdf.invoice_pdf', compact('invoice'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Invoice_{$invoice->invoice_number}.pdf");
    }

    /**
     * Download or stream Delivery Note PDF with sign-off
     */
    public function deliveryNote(DeliveryNote $deliveryNote, Request $request)
    {
        $deliveryNote->load(['customer', 'invoice', 'items']);

        if ($request->has('print')) {
            return view('pdf.delivery_print', compact('deliveryNote'));
        }

        $pdf = Pdf::loadView('pdf.delivery_pdf', compact('deliveryNote'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("DeliveryNote_{$deliveryNote->delivery_number}.pdf");
    }

    /**
     * Download or stream Customer Payment Receipt PDF
     */
    public function receipt(CustomerReceipt $receipt, Request $request)
    {
        $receipt->load(['customer', 'invoice']);

        if ($request->has('print')) {
            return view('pdf.receipt_print', compact('receipt'));
        }

        $pdf = Pdf::loadView('pdf.receipt_pdf', compact('receipt'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Receipt_{$receipt->receipt_number}.pdf");
    }
}
