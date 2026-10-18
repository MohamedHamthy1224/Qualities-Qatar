<?php

namespace App\Http\Controllers;

use App\Models\CustomerReceipt;
use App\Models\DeliveryNote;
use App\Models\Quotation;
use App\Models\SalesInvoice;
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
            return view('pdf.quotation_print', $this->documentData(compact('quotation')));
        }

        $pdf = Pdf::loadView('pdf.quotation_pdf', $this->documentData(compact('quotation')))
            ->setPaper('a4', 'portrait');

        return $this->downloadDocument($pdf, "Quotation_{$quotation->quotation_number}.pdf");
    }

    /**
     * Download or stream Sales Invoice PDF
     */
    public function invoice(SalesInvoice $invoice, Request $request)
    {
        $invoice->load(['customer', 'quotation', 'items', 'receipts']);

        if ($request->has('thermal')) {
            return view('pdf.thermal_receipt', $this->documentData(compact('invoice')));
        }

        if ($request->has('print')) {
            return view('pdf.invoice_print', $this->documentData(compact('invoice')));
        }

        $pdf = Pdf::loadView('pdf.invoice_pdf', $this->documentData(compact('invoice')))
            ->setPaper('a4', 'portrait');

        return $this->downloadDocument($pdf, "Invoice_{$invoice->invoice_number}.pdf");
    }

    /**
     * Download or stream Delivery Note PDF with sign-off
     */
    public function deliveryNote(DeliveryNote $deliveryNote, Request $request)
    {
        $deliveryNote->load(['customer', 'invoice', 'items.invoiceItem']);

        if ($request->has('print')) {
            return view('pdf.delivery_print', $this->documentData(compact('deliveryNote')));
        }

        $pdf = Pdf::loadView('pdf.delivery_pdf', $this->documentData(compact('deliveryNote')))
            ->setPaper('a4', 'portrait');

        return $this->downloadDocument($pdf, "DeliveryNote_{$deliveryNote->delivery_number}.pdf");
    }

    /**
     * Download or stream Customer Payment Receipt PDF
     */
    public function receipt(CustomerReceipt $receipt, Request $request)
    {
        $receipt->load(['customer', 'invoice']);
        $allocations = $receipt->paymentGroup();
        $receipt = $allocations->first();

        if ($request->has('print')) {
            return view('pdf.receipt_print', $this->documentData(compact('receipt', 'allocations')));
        }

        $pdf = Pdf::loadView('pdf.receipt_pdf', $this->documentData(compact('receipt', 'allocations')))
            ->setPaper('a4', 'portrait');

        return $this->downloadDocument($pdf, "Receipt_{$receipt->receipt_number}.pdf");
    }

    private function downloadDocument(\Barryvdh\DomPDF\PDF $pdf, string $filename)
    {
        $pdf->render();
        $domPdf = $pdf->getDomPDF();
        $canvas = $domPdf->getCanvas();
        $font = $domPdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text($canvas->get_width() - 90, $canvas->get_height() - 22, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.39, 0.45, 0.55]);

        return $pdf->download($filename);
    }

    private function documentData(array $data): array
    {
        $company = config('company');
        $company['logo_data_uri'] = null;

        if (! empty($company['logo_path'])) {
            $publicDirectory = realpath(public_path());
            $logo = realpath(public_path($company['logo_path']));

            // Only read configured image files within public/; do not fetch remote logos.
            if ($logo && $publicDirectory && str_starts_with(strtolower($logo), strtolower($publicDirectory.DIRECTORY_SEPARATOR)) && is_file($logo)) {
                $mime = match (strtolower(pathinfo($logo, PATHINFO_EXTENSION))) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    default => null,
                };

                if ($mime) {
                    $company['logo_data_uri'] = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logo));
                }
            }
        }

        return $data + ['company' => $company];
    }
}
