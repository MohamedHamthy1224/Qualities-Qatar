<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\DeliveryNote;
use App\Models\ProductionOrder;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_order_lifecycle_keeps_production_delivery_and_split_payment_totals_consistent(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay()->addHours(12));
        $customer = Customer::create(['customer_code' => 'CUST-LIFECYCLE', 'name' => 'Lifecycle School']);
        $quotationPayload = [
            'customer_id' => $customer->id,
            'quotation_number' => 'QT-LIFECYCLE',
            'quotation_date' => '2026-10-15',
            'delivery_date' => '2026-10-22',
            'action' => 'send',
            'setup_charges' => 20, 'print_charges' => 10, 'discount' => 40,
            'notes' => 'Blue fabric; school crest approved.',
            'items' => [
                [
                    'product_name' => 'School Polo', 'category' => 'School Uniforms',
                    'input_mode' => 'garment_matrix', 'pricing_unit' => 'piece',
                    'size_s' => 4, 'size_m' => 6, 'unit_price' => 30,
                    'line_discount' => 15, 'setup_charges' => 10, 'print_charges' => 5,
                ],
                [
                    'product_name' => 'Crest Embroidery', 'category' => 'Computerized Embroidery',
                    'input_mode' => 'service_qty', 'pricing_unit' => 'service',
                    'total_qty' => 10, 'unit_price' => 5, 'setup_charges' => 5,
                    'custom_size_notes' => 'Left chest, 8 cm wide',
                ],
                [
                    'product_name' => 'Welcome Gift', 'category' => 'Promotional Gifts',
                    'input_mode' => 'standard_qty', 'pricing_unit' => 'piece',
                    'total_qty' => 3, 'unit_price' => 20, 'line_discount' => 5,
                ],
            ],
        ];

        $this->post('/quotations', $quotationPayload)->assertSessionHasNoErrors()->assertRedirect();
        $quotation = Quotation::where('quotation_number', 'QT-LIFECYCLE')->firstOrFail();
        $this->assertSame('Sent', $quotation->status);
        $this->assertSame('Multi-Category', $quotation->category);
        $this->assertEquals(410, $quotation->subtotal);
        $this->assertEquals(400, $quotation->grand_total);
        $this->assertSame(23, $quotation->total_qty);

        $this->from("/quotations/{$quotation->id}")->patch("/quotations/{$quotation->id}/status", ['status' => 'Approved'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $invoice = SalesInvoice::where('quotation_id', $quotation->id)->firstOrFail();
        $this->assertSame('Invoiced', $quotation->fresh()->status);
        $this->assertEquals(400, $invoice->grand_total);
        $this->assertEquals(400, $customer->fresh()->balance);
        $this->assertCount(3, $invoice->items);
        foreach ($quotation->items as $line) {
            $converted = $invoice->items->firstWhere('product_name', $line->product_name);
            foreach (['category', 'input_mode', 'pricing_unit', 'total_qty', 'size_s', 'size_m', 'custom_size_notes', 'line_total'] as $field) {
                $this->assertEquals($line->$field, $converted->$field, "Converted {$line->product_name}: {$field}");
            }
        }
        $this->post("/quotations/{$quotation->id}/convert")->assertRedirect("/invoices/{$invoice->id}");
        $this->assertSame(1, SalesInvoice::count());
        $this->assertSame(1, ProductionOrder::count());

        $order = $invoice->productionOrder;
        $this->assertSame('Multi-Category', $order->category);
        $this->assertSame('School Polo / Crest Embroidery / Welcome Gift', $order->item_name);
        $this->get('/')->assertOk()->assertViewHas('activeOrdersCount', 1)->assertViewHas('completedOrdersCount', 0);
        foreach (array_keys($order->workflow_stage_options) as $stage) {
            $this->postJson("/production/{$order->id}/stage", ['stage' => $stage])->assertOk()->assertJsonPath('stage', $stage);
            if ($stage === 'Ready for Delivery') {
                $this->get('/')->assertOk()->assertViewHas('readyDeliveriesCount', 1);
            }
        }
        $this->get('/?production_status=completed')->assertOk()
            ->assertViewHas('activeOrdersCount', 0)->assertViewHas('completedOrdersCount', 1)
            ->assertSee($order->order_number)->assertSee('Crest Embroidery');
        $this->assertSame('Unpaid', $invoice->fresh()->status);
        $this->assertSame(0, DeliveryNote::count());

        $items = $invoice->items->keyBy('product_name');
        $deliveryBase = [
            'invoice_id' => $invoice->id, 'dispatch_date' => '2026-10-22',
            'recipient_name' => 'School Stores', 'recipient_phone' => '+974 55551234',
        ];
        $this->post('/delivery', $deliveryBase + [
            'delivery_number' => 'DN-LIFECYCLE-1', 'status' => 'Dispatched',
            'items' => [
                ['invoice_item_id' => $items['School Polo']->id, 'size_s' => 2, 'size_m' => 3, 'total_qty' => 5],
                ['invoice_item_id' => $items['Crest Embroidery']->id, 'total_qty' => 4],
                ['invoice_item_id' => $items['Welcome Gift']->id, 'total_qty' => 1],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $partial = DeliveryNote::where('delivery_number', 'DN-LIFECYCLE-1')->firstOrFail();
        $this->assertEquals(10, $partial->items->sum('total_qty'));
        $this->get("/delivery/create?invoice_id={$invoice->id}")->assertOk()
            ->assertViewHas('invoiceOptions', function ($options) use ($invoice, $items) {
                $remaining = $options[$invoice->id]['items']->keyBy('invoice_item_id');

                return ! $options[$invoice->id]['needs_review']
                    && $remaining[$items['School Polo']->id]['size_s'] === 2
                    && $remaining[$items['School Polo']->id]['size_m'] === 3
                    && $remaining[$items['Crest Embroidery']->id]['total_qty'] === 6
                    && $remaining[$items['Welcome Gift']->id]['total_qty'] === 2;
            });
        $this->patch("/delivery/{$partial->id}/status", ['status' => 'Delivered'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotNull($partial->fresh()->delivered_at);
        $this->post('/delivery', $deliveryBase + [
            'delivery_number' => 'DN-LIFECYCLE-2', 'status' => 'Pending',
            'items' => [
                ['invoice_item_id' => $items['School Polo']->id, 'size_s' => 2, 'size_m' => 3, 'total_qty' => 5],
                ['invoice_item_id' => $items['Crest Embroidery']->id, 'total_qty' => 6],
                ['invoice_item_id' => $items['Welcome Gift']->id, 'total_qty' => 2],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $remainder = DeliveryNote::where('delivery_number', 'DN-LIFECYCLE-2')->firstOrFail();
        $this->assertEquals(13, $remainder->items->sum('total_qty'));
        $this->assertEquals(0, DeliveryNote::availabilityFor($invoice->fresh())['items']->sum('total_qty'));
        $this->patch("/delivery/{$remainder->id}/status", ['status' => 'Dispatched'])->assertSessionHasNoErrors()->assertRedirect();
        $this->patch("/delivery/{$remainder->id}/status", ['status' => 'Delivered'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Completed', $order->fresh()->stage);
        $this->assertSame('Unpaid', $invoice->fresh()->status);

        // A second existing invoice exercises one payment allocated across invoices.
        $this->post('/invoices', [
            'customer_id' => $customer->id, 'invoice_number' => 'INV-LIFECYCLE-EXTRA',
            'invoice_date' => '2026-10-15', 'due_date' => '2026-11-01',
            'items' => [[
                'product_name' => 'Extra Welcome Gifts', 'category' => 'Promotional Gifts',
                'input_mode' => 'standard_qty', 'pricing_unit' => 'piece', 'total_qty' => 5, 'unit_price' => 20,
            ]],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $extraInvoice = SalesInvoice::where('invoice_number', 'INV-LIFECYCLE-EXTRA')->firstOrFail();
        $this->assertEquals(500, $customer->fresh()->balance);
        $this->post('/payments', [
            'customer_id' => $customer->id, 'receipt_number' => 'RCPT-LIFECYCLE-1',
            'amount_received' => '450.00', 'payment_method' => 'Bank Transfer',
            'payment_date' => '2026-10-23', 'reference_number' => 'BANK-LIFECYCLE-1',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $splitPayment = CustomerReceipt::where('receipt_number', 'RCPT-LIFECYCLE-1')->firstOrFail();
        $this->assertCount(2, $splitPayment->paymentGroup());
        $this->assertEquals(450, $splitPayment->payment_total);
        $this->assertEquals(450, CustomerReceipt::sum('amount_received'));
        $this->assertSame('Paid', $invoice->fresh()->status);
        $this->assertEquals(0, $invoice->fresh()->balance_due);
        $this->assertSame('Partially Paid', $extraInvoice->fresh()->status);
        $this->assertEquals(50, $extraInvoice->fresh()->balance_due);
        $this->assertEquals(50, $customer->fresh()->balance);
        $this->post('/payments', [
            'customer_id' => $customer->id, 'invoice_id' => $extraInvoice->id,
            'receipt_number' => 'RCPT-LIFECYCLE-2', 'amount_received' => '50.00',
            'payment_method' => 'Cash', 'payment_date' => '2026-10-24',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals(500, CustomerReceipt::sum('amount_received'));
        $this->assertSame('Paid', $extraInvoice->fresh()->status);
        $this->assertEquals(0, $customer->fresh()->balance);

        $this->get("/pdf/quotations/{$quotation->id}?print=1")->assertOk()->assertSee('QAR 400.00')->assertSee('Crest Embroidery');
        $this->get("/pdf/invoices/{$invoice->id}?print=1")->assertOk()->assertSee('QAR 400.00')->assertSee('QAR 0.00')->assertSee('Amount paid');
        $this->get("/pdf/delivery/{$partial->id}?print=1")->assertOk()->assertSee('School Polo')->assertSee('Crest Embroidery');
        $this->get("/pdf/receipts/{$splitPayment->id}?print=1")->assertOk()->assertSee('QAR 450.00')->assertSee($extraInvoice->invoice_number);
        foreach (["quotations/{$quotation->id}", "invoices/{$invoice->id}", "delivery/{$partial->id}", "receipts/{$splitPayment->id}"] as $document) {
            $response = $this->get('/pdf/'.$document)->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
        $this->get("/customers/{$customer->id}")->assertOk()->assertSee('RCPT-LIFECYCLE-1')->assertSee('RCPT-LIFECYCLE-2');
        $this->get('/')->assertOk()->assertViewHas('activeOrdersCount', 1)->assertViewHas('completedOrdersCount', 1);
    }
}
