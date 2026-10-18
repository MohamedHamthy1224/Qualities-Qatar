<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): Customer
    {
        return Customer::create(['name' => 'Workflow Client', 'customer_code' => 'CUST-WORKFLOW']);
    }

    public function test_direct_invoice_and_quotation_use_identical_calculation_and_item_metadata(): void
    {
        $customer = $this->customer();
        $service = Product::create([
            'sku' => 'EMB-TEST', 'name' => 'Logo Embroidery', 'category' => 'Computerized Embroidery',
            'input_mode' => 'service_qty', 'pricing_unit' => 'service', 'unit_price' => 50,
            'size_matrix_required' => false, 'is_active' => true,
        ]);
        $payload = [
            'customer_id' => $customer->id, 'quotation_date' => '2026-10-15', 'invoice_date' => '2026-10-15',
            'quotation_number' => 'QT-PARITY', 'invoice_number' => 'INV-PARITY',
            'setup_charges' => 1.005, 'print_charges' => 2.675, 'discount' => 4.005,
            'items' => [
                ['product_id' => $service->id, 'product_name' => 'Logo Embroidery', 'category' => 'School Uniforms', 'input_mode' => 'garment_matrix', 'pricing_unit' => 'piece', 'total_qty' => 2, 'unit_price' => 50, 'setup_charges' => 20, 'line_discount' => 150],
                ['product_name' => 'Shirts', 'category' => 'Corporate Uniforms', 'input_mode' => 'garment_matrix', 'size_s' => 1, 'size_m' => 2, 'unit_price' => 10.005, 'print_charges' => .335, 'line_discount' => .335],
                ['product_name' => 'Gift set', 'category' => 'Promotional Gifts', 'input_mode' => 'standard_qty', 'pricing_unit' => 'set', 'total_qty' => 2, 'unit_price' => 12.34],
            ],
        ];

        $this->post('/quotations', $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->post('/invoices', $payload)->assertSessionHasNoErrors()->assertRedirect();
        $quote = Quotation::firstOrFail();
        $invoice = SalesInvoice::firstOrFail();
        $this->assertSame('74.39', $invoice->grand_total);
        $this->assertSame('74.71', $invoice->subtotal);
        foreach (['total_qty', 'subtotal', 'setup_charges', 'print_charges', 'discount', 'grand_total'] as $field) {
            $this->assertEquals($quote->$field, $invoice->$field, $field);
        }
        $fields = ['product_id', 'product_name', 'category', 'input_mode', 'pricing_unit', 'total_qty', 'size_s', 'size_m', 'unit_price', 'setup_charges', 'print_charges', 'line_discount', 'line_total'];
        foreach ($quote->items as $index => $item) {
            $this->assertEquals($item->only($fields), $invoice->items[$index]->only($fields));
        }
        $this->assertSame('service_qty', $invoice->items[0]->input_mode);
        $this->assertSame('100.00', $invoice->items[0]->line_discount);
        $this->assertSame('Multi-Category', $invoice->productionOrder->category);
        $this->assertStringContainsString('Gift set', $invoice->productionOrder->item_name);
        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('Additional Setup:')->assertSee('Printing')->assertSee('set');
    }

    public function test_invalid_quantities_and_prices_are_rejected_without_losing_form_input(): void
    {
        $payload = [
            'customer_id' => $this->customer()->id, 'invoice_number' => 'INV-INVALID', 'invoice_date' => '2026-10-15',
            'notes' => 'Keep these customer instructions', 'discount' => 25,
            'items' => [['product_name' => 'Keep this item', 'input_mode' => 'standard_qty', 'total_qty' => -2, 'unit_price' => -5]],
        ];
        $this->from('/invoices/create')->post('/invoices', $payload)
            ->assertRedirect('/invoices/create')
            ->assertSessionHasErrors(['items.0.total_qty', 'items.0.unit_price'])
            ->assertSessionHasInput('notes', $payload['notes'])
            ->assertSessionHasInput('items.0.product_name', 'Keep this item');
        $this->assertDatabaseCount('sales_invoices', 0);
        $this->get('/invoices/create')->assertOk()->assertSee('Keep these customer instructions')->assertSee('Keep this item');

        $payload['items'][0]['total_qty'] = 0;
        $payload['items'][0]['unit_price'] = 5;
        $this->post('/invoices', $payload)->assertSessionHasErrors('items.0.total_qty');
        $this->assertDatabaseCount('production_orders', 0);
    }

    public function test_approval_and_linked_invoice_status_cannot_be_bypassed(): void
    {
        $quote = Quotation::create([
            'quotation_number' => 'QT-APPROVAL', 'customer_id' => $this->customer()->id,
            'quotation_date' => '2026-10-15', 'category' => 'School Uniforms', 'status' => 'Cancelled', 'grand_total' => 10,
        ]);
        $quote->items()->create(['product_name' => 'Test item', 'total_qty' => 1, 'unit_price' => 10, 'line_total' => 10]);
        $this->post('/quotations/'.$quote->id.'/convert')->assertSessionHasErrors('status');
        $this->patch('/quotations/'.$quote->id.'/status', ['status' => 'Invoiced'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('sales_invoices', 0);
        $this->patch('/quotations/'.$quote->id.'/status', ['status' => 'Approved'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertDatabaseCount('production_orders', 1);
        $this->patch('/quotations/'.$quote->id.'/status', ['status' => 'Draft'])->assertSessionHasErrors('status');
        $this->post('/quotations/'.$quote->id.'/convert')->assertRedirect();
        $this->assertSame('Invoiced', $quote->fresh()->status);
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertDatabaseCount('production_orders', 1);
    }

    public function test_create_forms_start_empty_and_numbering_follows_highest_existing_suffix(): void
    {
        Quotation::create([
            'quotation_number' => 'QT-'.date('Y').'-009', 'customer_id' => $this->customer()->id,
            'quotation_date' => now()->toDateString(), 'category' => 'School Uniforms',
        ]);
        $this->get('/quotations/create')->assertOk()->assertSee('QT-'.date('Y').'-010')
            ->assertSee('id="emptyLineItems"', false)->assertDontSee('value="100"', false);
        $this->get('/invoices/create')->assertOk()->assertSee('name="invoice_number"', false)
            ->assertSee('name="invoice_date"', false)->assertSee('name="due_date"', false)
            ->assertDontSee('name="artwork_file"', false)->assertSee('id="emptyLineItems"', false);
    }

    public function test_invalid_item_row_keys_return_validation_errors_instead_of_server_errors(): void
    {
        $this->postJson('/invoices', [
            'customer_id' => $this->customer()->id, 'invoice_number' => 'INV-BAD-ROW',
            'invoice_date' => '2026-10-16',
            'items' => ['unexpected' => ['product_name' => 'Custom item', 'input_mode' => 'standard_qty', 'total_qty' => 0, 'unit_price' => 10]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('sales_invoices', 0);
    }
}
