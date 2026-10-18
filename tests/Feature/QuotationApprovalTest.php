<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class QuotationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function quotation(string $status = 'Sent'): Quotation
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-APPROVAL',
            'name' => 'Approval School',
        ]);
        $product = Product::create([
            'sku' => 'POLO-APPROVAL', 'name' => 'School Polo',
            'category' => 'School Uniforms', 'unit_price' => 25,
        ]);
        $quotation = Quotation::create([
            'quotation_number' => 'QT-AUTO-APPROVAL',
            'customer_id' => $customer->id,
            'quotation_date' => '2026-10-15',
            'delivery_date' => '2026-10-25',
            'category' => 'Multi-Category',
            'status' => $status,
            'total_qty' => 14,
            'subtotal' => 330,
            'setup_charges' => 20,
            'print_charges' => 10,
            'discount' => 15,
            'grand_total' => 345,
            'notes' => 'Keep the approved crest and fabric colour.',
        ]);
        $quotation->items()->createMany([
            [
                'product_id' => $product->id, 'product_name' => 'School Polo', 'category' => 'School Uniforms',
                'input_mode' => 'garment_matrix', 'pricing_unit' => 'piece',
                'size_xs' => 1, 'size_s' => 2, 'size_m' => 3, 'size_l' => 1,
                'size_xl' => 1, 'size_2xl' => 1, 'size_3xl' => 1, 'size_custom' => 1,
                'custom_size_notes' => 'One extra-long sleeve',
                'total_qty' => 11, 'unit_price' => 25, 'line_discount' => 5,
                'setup_charges' => 10, 'print_charges' => 5, 'line_total' => 285,
            ],
            [
                'product_name' => 'Crest Embroidery', 'category' => 'Computerized Embroidery',
                'input_mode' => 'service_qty', 'pricing_unit' => 'service',
                'custom_size_notes' => 'Left chest, 8 cm wide',
                'total_qty' => 3, 'unit_price' => 15, 'line_total' => 45,
            ],
        ]);

        return $quotation;
    }

    public function test_approval_automatically_creates_the_invoice_and_production_order_with_the_approved_snapshot(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 17)->startOfDay()->addHours(12));
        $quotation = $this->quotation();
        Product::query()->update(['name' => 'New catalogue name', 'unit_price' => 99]);

        $response = $this->from('/quotations/'.$quotation->id)
            ->patch('/quotations/'.$quotation->id.'/status', ['status' => 'Approved'])
            ->assertSessionHasNoErrors();

        $invoice = SalesInvoice::where('quotation_id', $quotation->id)->firstOrFail();
        $response->assertRedirect('/quotations/'.$quotation->id);
        $this->assertSame('Invoiced', $quotation->fresh()->status);
        $this->assertSame($quotation->customer_id, $invoice->customer_id);
        $this->assertSame('2026-10-17', $invoice->invoice_date->toDateString());
        $this->assertSame('2026-10-25', $invoice->due_date->toDateString());
        foreach (['total_qty', 'subtotal', 'setup_charges', 'print_charges', 'discount', 'grand_total'] as $field) {
            $this->assertEquals($quotation->$field, $invoice->$field, $field);
        }
        $this->assertSame('0.00', $invoice->paid_amount);
        $this->assertSame('345.00', $invoice->balance_due);
        $this->assertSame('Unpaid', $invoice->status);
        $this->assertStringContainsString($quotation->notes, $invoice->notes);
        $this->assertSame('345.00', $quotation->customer->fresh()->balance);

        $fields = [
            'product_id', 'product_name', 'category', 'input_mode', 'pricing_unit',
            'size_xs', 'size_s', 'size_m', 'size_l', 'size_xl', 'size_2xl', 'size_3xl',
            'size_custom', 'custom_size_notes', 'total_qty', 'unit_price',
            'line_discount', 'setup_charges', 'print_charges', 'line_total',
        ];
        $this->assertCount(2, $invoice->items);
        foreach ($quotation->items as $item) {
            $invoiceItem = $invoice->items->firstWhere('product_name', $item->product_name);
            $this->assertNotNull($invoiceItem);
            $this->assertEquals($item->only($fields), $invoiceItem->only($fields));
        }

        $order = $invoice->productionOrder;
        $this->assertNotNull($order);
        $this->assertSame($quotation->id, $order->quotation_id);
        $this->assertSame($quotation->customer_id, $order->customer_id);
        $this->assertSame('Confirmed', $order->stage);
        $this->assertSame('Multi-Category', $order->category);
        $this->assertSame('School Polo / Crest Embroidery', $order->item_name);
        $this->assertSame(14, $order->total_qty);
        $this->assertSame('2026-10-25', $order->target_date->toDateString());
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertDatabaseCount('production_orders', 1);
        $this->get('/invoices')->assertOk()->assertSee($invoice->invoice_number);
    }

    public function test_repeated_approval_and_the_old_convert_action_return_the_existing_invoice_without_duplicate_effects(): void
    {
        $quotation = $this->quotation();
        $this->patch('/quotations/'.$quotation->id.'/status', ['status' => 'Approved'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $invoice = $quotation->invoice()->firstOrFail();

        $this->from('/quotations/'.$quotation->id)
            ->patch('/quotations/'.$quotation->id.'/status', ['status' => 'Approved'])
            ->assertSessionHasNoErrors()->assertRedirect('/quotations/'.$quotation->id);
        $this->post('/quotations/'.$quotation->id.'/convert')
            ->assertSessionHasNoErrors()->assertRedirect('/invoices/'.$invoice->id);

        $this->assertSame('Invoiced', $quotation->fresh()->status);
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertDatabaseCount('invoice_items', 2);
        $this->assertDatabaseCount('production_orders', 1);
        $this->assertSame('345.00', $quotation->customer->fresh()->balance);
    }

    public function test_legacy_approved_quotations_can_still_be_invoiced_once_through_the_old_convert_action(): void
    {
        $quotation = $this->quotation('Approved');

        $this->post('/quotations/'.$quotation->id.'/convert')->assertSessionHasNoErrors()->assertRedirect();
        $invoice = $quotation->invoice()->firstOrFail();
        $this->post('/quotations/'.$quotation->id.'/convert')
            ->assertSessionHasNoErrors()->assertRedirect('/invoices/'.$invoice->id);

        $this->assertSame('Invoiced', $quotation->fresh()->status);
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertDatabaseCount('production_orders', 1);
        $this->assertSame('345.00', $quotation->customer->fresh()->balance);
    }

    public function test_manual_conversion_cannot_approve_draft_sent_or_cancelled_quotations(): void
    {
        $quotation = $this->quotation('Draft');

        foreach (['Draft', 'Sent', 'Cancelled'] as $status) {
            $quotation->update(['status' => $status]);
            $this->post('/quotations/'.$quotation->id.'/convert')->assertSessionHasErrors('status');
            $this->assertSame($status, $quotation->fresh()->status);
        }

        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('production_orders', 0);
        $this->assertSame('0.00', $quotation->customer->fresh()->balance);
    }

    public function test_linked_quotations_cannot_be_changed_back_to_an_uninvoiced_status(): void
    {
        $quotation = $this->quotation();
        $this->patch('/quotations/'.$quotation->id.'/status', ['status' => 'Approved'])
            ->assertSessionHasNoErrors();

        foreach (['Draft', 'Sent', 'Cancelled'] as $status) {
            $this->patch('/quotations/'.$quotation->id.'/status', ['status' => $status])
                ->assertSessionHasErrors('status');
            $this->assertSame('Invoiced', $quotation->fresh()->status);
        }

        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertDatabaseCount('production_orders', 1);
    }

    public function test_production_failure_rolls_back_the_approval_invoice_items_and_customer_balance(): void
    {
        $quotation = $this->quotation();
        $dispatcher = ProductionOrder::getEventDispatcher();
        ProductionOrder::setEventDispatcher(clone $dispatcher);
        ProductionOrder::creating(function () {
            $this->assertDatabaseCount('sales_invoices', 1);
            $this->assertDatabaseCount('invoice_items', 2);
            throw new RuntimeException('Simulated production queue failure');
        });
        $this->withoutExceptionHandling();

        try {
            $this->patch('/quotations/'.$quotation->id.'/status', ['status' => 'Approved']);
            $this->fail('The simulated production queue failure should interrupt approval.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated production queue failure', $exception->getMessage());
        } finally {
            ProductionOrder::setEventDispatcher($dispatcher);
        }

        $this->assertSame('Sent', $quotation->fresh()->status);
        $this->assertDatabaseCount('quotations', 1);
        $this->assertDatabaseCount('quotation_items', 2);
        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
        $this->assertDatabaseCount('production_orders', 0);
        $this->assertSame('0.00', $quotation->customer->fresh()->balance);
    }
}
