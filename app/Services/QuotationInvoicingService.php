<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ProductionOrder;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use Illuminate\Validation\ValidationException;

class QuotationInvoicingService
{
    public function approve(Quotation $quotation): SalesInvoice
    {
        return DocumentNumber::transaction(function () use ($quotation) {
            $quotation = Quotation::whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if ($invoice = $this->existingInvoice($quotation)) {
                return $invoice;
            }
            if ($quotation->status === 'Invoiced') {
                throw ValidationException::withMessages(['status' => 'This quotation is already invoiced, but its linked invoice is missing. Please check its invoice record.']);
            }

            $quotation->update(['status' => 'Approved']);

            return $this->createInvoice($quotation);
        });
    }

    /** Preserve older conversion links for quotations approved before automatic invoicing. */
    public function convertApproved(Quotation $quotation): SalesInvoice
    {
        return DocumentNumber::transaction(function () use ($quotation) {
            $quotation = Quotation::whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            return $this->createInvoice($quotation);
        });
    }

    /** Called only with the quotation locked inside a document-number transaction. */
    private function createInvoice(Quotation $quotation): SalesInvoice
    {
        if ($invoice = $this->existingInvoice($quotation)) {
            return $invoice;
        }
        if ($quotation->status !== 'Approved') {
            throw ValidationException::withMessages(['status' => 'Approve the quotation to automatically create its sales invoice.']);
        }

        $customer = Customer::whereKey($quotation->customer_id)->lockForUpdate()->firstOrFail();
        $year = date('Y');
        $invoice = SalesInvoice::create([
            'invoice_number' => DocumentNumber::next(SalesInvoice::class, 'invoice_number', 'INV-'.$year.'-'),
            'quotation_id' => $quotation->id,
            'customer_id' => $quotation->customer_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => $quotation->delivery_date ?? now()->addDays(15)->toDateString(),
            'total_qty' => $quotation->total_qty,
            'subtotal' => $quotation->subtotal,
            'discount' => $quotation->discount,
            'setup_charges' => $quotation->setup_charges,
            'print_charges' => $quotation->print_charges,
            'grand_total' => $quotation->grand_total,
            'paid_amount' => 0.00,
            'balance_due' => $quotation->grand_total,
            'status' => $quotation->grand_total > 0 ? 'Unpaid' : 'Paid',
            'notes' => "Converted from Quotation {$quotation->quotation_number}. ".$quotation->notes,
        ]);

        foreach ($quotation->items as $item) {
            $invoice->items()->create($item->only([
                'product_id', 'product_name', 'category', 'input_mode', 'pricing_unit',
                'size_xs', 'size_s', 'size_m', 'size_l', 'size_xl', 'size_2xl', 'size_3xl',
                'size_custom', 'custom_size_notes', 'total_qty', 'unit_price', 'line_discount',
                'setup_charges', 'print_charges', 'line_total',
            ]));
        }

        ProductionOrder::create([
            'order_number' => DocumentNumber::next(ProductionOrder::class, 'order_number', 'PO-'.$year.'-'),
            'invoice_id' => $invoice->id,
            'quotation_id' => $quotation->id,
            'customer_id' => $quotation->customer_id,
            ...ProductionOrder::summaryAttributesForInvoice($invoice),
            'stage' => 'Confirmed',
            'priority' => 'Normal',
            'target_date' => $quotation->delivery_date ?? now()->addDays(7)->toDateString(),
            'notes' => 'Auto-queued from approved Quotation '.$quotation->quotation_number,
        ]);

        $quotation->update(['status' => 'Invoiced']);
        $customer->recalculateBalance();

        return $invoice;
    }

    /** Keep legacy records consistent when an idempotent approval/conversion reuses an invoice. */
    private function existingInvoice(Quotation $quotation): ?SalesInvoice
    {
        $invoices = $quotation->invoice()->lockForUpdate()->get();
        if ($invoices->isEmpty()) {
            return null;
        }
        if ($invoices->count() !== 1 || $invoices->first()->customer_id !== $quotation->customer_id) {
            throw ValidationException::withMessages(['status' => 'The linked invoice needs review before this quotation status can be updated.']);
        }

        if ($quotation->status !== 'Invoiced') {
            $quotation->update(['status' => 'Invoiced']);
        }

        return $invoices->first();
    }
}
