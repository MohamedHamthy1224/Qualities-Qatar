<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\DeliveryItem;
use App\Models\DeliveryNote;
use App\Models\InvoiceItem;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAndDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private int $serial = 0;

    private function customer(): Customer
    {
        return Customer::create(['customer_code' => 'C-' . ++$this->serial, 'name' => 'Customer ' . $this->serial]);
    }

    private function invoice(Customer $customer, float $amount = 100): SalesInvoice
    {
        return SalesInvoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-' . ++$this->serial,
            'invoice_date' => '2026-10-' . str_pad((string) $this->serial, 2, '0', STR_PAD_LEFT),
            'grand_total' => $amount,
            'balance_due' => $amount,
        ]);
    }

    private function item(SalesInvoice $invoice, array $data = []): InvoiceItem
    {
        return $invoice->items()->create(array_merge([
            'product_name' => 'Uniform shirt',
            'size_s' => 4, 'size_m' => 6, 'total_qty' => 10,
            'input_mode' => 'garment_matrix', 'pricing_unit' => 'piece',
        ], $data));
    }

    private function payment(Customer $customer, array $data = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'receipt_number' => 'TEST-RCPT-' . ++$this->serial,
            'amount_received' => '150.00', 'payment_method' => 'Cash', 'payment_date' => '2026-10-15',
        ], $data);
    }

    private function dispatch(SalesInvoice $invoice, array $items, array $data = []): array
    {
        return array_merge([
            'invoice_id' => $invoice->id, 'delivery_number' => 'TEST-DN-' . ++$this->serial,
            'dispatch_date' => '2026-10-15', 'recipient_name' => 'Recipient', 'status' => 'Pending', 'items' => $items,
        ], $data);
    }

    public function test_auto_allocation_splits_the_actual_amount_without_double_counting(): void
    {
        $customer = $this->customer();
        $first = $this->invoice($customer);
        $second = $this->invoice($customer);
        $response = $this->post(route('payments.store'), $this->payment($customer));
        $response->assertSessionHasNoErrors();
        $receipts = CustomerReceipt::orderBy('id')->get();
        $this->assertCount(2, $receipts);
        $this->assertSame('100.00', $receipts[0]->amount_received);
        $this->assertSame('50.00', $receipts[1]->amount_received);
        $this->assertSame($receipts[0]->id, $receipts[1]->parent_receipt_id);
        $this->assertEquals(150, CustomerReceipt::sum('amount_received'));
        $this->assertSame('Paid', $first->fresh()->status);
        $this->assertSame('Partially Paid', $second->fresh()->status);
        $this->assertSame('50.00', $second->fresh()->balance_due);
        $this->assertSame('50.00', $customer->fresh()->balance);
        $this->assertSame(150.0, $receipts[1]->payment_total);
        $this->get(route('payments.show', $receipts[1]))->assertOk()
            ->assertSee($receipts[0]->receipt_number)->assertSee('150.00')->assertSee($first->invoice_number)->assertSee($second->invoice_number);
    }

    public function test_specific_invoice_must_belong_to_customer(): void
    {
        $customer = $this->customer();
        $otherInvoice = $this->invoice($this->customer());
        $this->post(route('payments.store'), $this->payment($customer, ['invoice_id' => $otherInvoice->id, 'amount_received' => 50]))
            ->assertSessionHasErrors('invoice_id');
        $this->assertDatabaseCount('customer_receipts', 0);
    }

    public function test_payment_register_shows_one_payment_with_all_allocations(): void
    {
        $customer = $this->customer();
        $first = $this->invoice($customer);
        $second = $this->invoice($customer);
        $this->post(route('payments.store'), $this->payment($customer))->assertSessionHasNoErrors();
        $split = CustomerReceipt::whereNotNull('parent_receipt_id')->firstOrFail();
        $this->get(route('payments.index'))->assertOk()->assertSee('150.00')
            ->assertSee($first->invoice_number)->assertSee($second->invoice_number)
            ->assertViewHas('receipts', fn ($receipts) => $receipts->total() === 1);
        $this->get(route('payments.index', ['search' => $split->receipt_number]))->assertOk()
            ->assertViewHas('receipts', fn ($receipts) => $receipts->total() === 1);
    }

    public function test_specific_and_auto_payments_cannot_exceed_available_balance(): void
    {
        $customer = $this->customer();
        $invoice = $this->invoice($customer);
        $this->invoice($customer);
        $this->post(route('payments.store'), $this->payment($customer, ['invoice_id' => $invoice->id, 'amount_received' => 101]))
            ->assertSessionHasErrors('amount_received');
        $this->post(route('payments.store'), $this->payment($customer, ['amount_received' => 201]))
            ->assertSessionHasErrors('amount_received');
        $this->assertDatabaseCount('customer_receipts', 0);
        $this->assertSame('100.00', $invoice->fresh()->balance_due);
    }

    public function test_allocation_uses_actual_receipts_and_exact_cents(): void
    {
        $customer = $this->customer();
        $first = $this->invoice($customer, 0.10);
        $second = $this->invoice($customer, 0.20);
        $first->update(['balance_due' => 999]);
        $this->post(route('payments.store'), $this->payment($customer, ['amount_received' => '0.30']))->assertSessionHasNoErrors();
        $this->assertSame(30, (int) round(CustomerReceipt::sum('amount_received') * 100));
        $this->assertSame('0.00', $first->fresh()->balance_due);
        $this->assertSame('0.00', $second->fresh()->balance_due);
        $this->assertSame('0.00', $customer->fresh()->balance);
    }

    public function test_duplicate_receipt_and_fractional_cents_are_rejected_without_extra_payments(): void
    {
        $customer = $this->customer();
        $this->invoice($customer);
        $payload = $this->payment($customer, ['amount_received' => '25.00']);
        $this->post(route('payments.store'), $payload)->assertSessionHasNoErrors();
        $this->post(route('payments.store'), $payload)->assertSessionHasErrors('receipt_number');
        $this->post(route('payments.store'), $this->payment($customer, ['amount_received' => '1.001']))->assertSessionHasErrors('amount_received');
        $this->assertDatabaseCount('customer_receipts', 1);
        $this->assertEquals(25, CustomerReceipt::sum('amount_received'));
    }

    public function test_payment_validation_restores_entered_fields_and_customer_selection(): void
    {
        $customer = $this->customer();
        $invoice = $this->invoice($customer);
        $payload = $this->payment($customer, [
            'invoice_id' => $invoice->id, 'amount_received' => '120.00', 'payment_method' => 'Bank Transfer',
            'notes' => 'Retain these payment notes', 'reference_number' => 'BANK-REFERENCE',
        ]);
        $this->from(route('payments.create'))->post(route('payments.store'), $payload)->assertSessionHasErrors('amount_received');
        $this->get(route('payments.create'))->assertOk()->assertSee('Retain these payment notes')->assertSee('BANK-REFERENCE')->assertSee('120.00');
    }

    public function test_delivery_saves_invoice_owned_sized_and_flat_items_with_server_quantities(): void
    {
        $invoice = $this->invoice($this->customer());
        $shirt = $this->item($invoice);
        $service = $this->item($invoice, ['product_name' => 'Logo printing', 'size_s' => 0, 'size_m' => 0, 'total_qty' => 5, 'input_mode' => 'service_qty', 'pricing_unit' => 'design']);
        $response = $this->post(route('delivery.store'), $this->dispatch($invoice, [
            ['invoice_item_id' => $shirt->id, 'product_name' => 'Forged product', 'size_s' => 2, 'size_m' => 1, 'total_qty' => 999],
            ['invoice_item_id' => $service->id, 'total_qty' => 4],
        ]));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('delivery_items', ['invoice_item_id' => $shirt->id, 'product_name' => 'Uniform shirt', 'total_qty' => 3]);
        $this->assertDatabaseHas('delivery_items', ['invoice_item_id' => $service->id, 'total_qty' => 4]);
        $note = DeliveryNote::firstOrFail();
        $this->assertSame($invoice->customer_id, $note->customer_id);
        $this->assertNull($note->delivered_at);
        $this->get(route('delivery.show', $note))->assertOk()->assertSee('Logo printing')->assertSee('design');
    }

    public function test_pending_delivery_reserves_size_quantities_and_prevents_overdelivery(): void
    {
        $invoice = $this->invoice($this->customer());
        $item = $this->item($invoice);
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'size_s' => 3, 'total_qty' => 3]]))->assertSessionHasNoErrors();
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'size_s' => 2, 'total_qty' => 2]]))->assertSessionHasErrors('items.0.size_s');
        $this->assertDatabaseCount('delivery_notes', 1);
        $this->assertDatabaseCount('delivery_items', 1);
        $this->get(route('delivery.create', ['invoice_id' => $invoice->id]))->assertOk()
            ->assertViewHas('invoiceOptions', function ($options) use ($invoice) {
                $remaining = $options[$invoice->id]['items']->first();
                return $remaining['size_s'] === 1 && $remaining['size_m'] === 6 && $remaining['total_qty'] === 7;
            });
    }

    public function test_partial_flat_delivery_keeps_remaining_service_quantity(): void
    {
        $invoice = $this->invoice($this->customer());
        $item = $this->item($invoice, ['size_s' => 0, 'size_m' => 0, 'total_qty' => 5, 'input_mode' => 'service_qty']);
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'total_qty' => 3]]))->assertSessionHasNoErrors();
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'total_qty' => 3]]))->assertSessionHasErrors('items.0.total_qty');
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'total_qty' => 2]]))->assertSessionHasNoErrors();
        $this->assertEquals(5, DeliveryItem::sum('total_qty'));
    }

    public function test_delivery_rejects_foreign_duplicate_and_invalid_item_quantities_atomically(): void
    {
        $invoice = $this->invoice($this->customer());
        $item = $this->item($invoice);
        $foreign = $this->item($this->invoice($this->customer()));
        $this->post(route('delivery.store'), $this->dispatch($invoice, [
            ['invoice_item_id' => $item->id, 'size_s' => 1, 'total_qty' => 1],
            ['invoice_item_id' => $foreign->id, 'size_s' => 1, 'total_qty' => 1],
        ]))->assertSessionHasErrors('items.1.invoice_item_id');
        $this->post(route('delivery.store'), $this->dispatch($invoice, [
            ['invoice_item_id' => $item->id, 'size_s' => 1, 'total_qty' => 1],
            ['invoice_item_id' => $item->id, 'size_s' => 1, 'total_qty' => 1],
        ]))->assertSessionHasErrors('items.0.invoice_item_id');
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'size_s' => -1, 'total_qty' => 1]]))->assertSessionHasErrors('items.0.size_s');
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'size_s' => 1.5, 'total_qty' => 1]]))->assertSessionHasErrors('items.0.size_s');
        $this->assertDatabaseCount('delivery_notes', 0);
        $this->assertDatabaseCount('delivery_items', 0);
    }

    public function test_empty_handover_cannot_create_a_delivery_note(): void
    {
        $invoice = $this->invoice($this->customer());
        $item = $this->item($invoice);
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'total_qty' => 0]]))->assertSessionHasErrors('items');
        $this->assertDatabaseCount('delivery_notes', 0);
    }

    public function test_legacy_delivery_is_counted_by_unambiguous_invoice_item_name(): void
    {
        $invoice = $this->invoice($this->customer());
        $item = $this->item($invoice);
        $note = $invoice->deliveryNotes()->create([
            'customer_id' => $invoice->customer_id, 'delivery_number' => 'LEGACY', 'dispatch_date' => '2026-10-14', 'recipient_name' => 'Recipient',
        ]);
        $note->items()->create(['product_name' => $item->product_name, 'size_s' => 2, 'total_qty' => 2]);
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'size_s' => 3, 'total_qty' => 3]]))->assertSessionHasErrors('items.0.size_s');
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'size_s' => 2, 'size_m' => 6, 'total_qty' => 8]]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('delivery_notes', 2);
    }

    public function test_ambiguous_legacy_delivery_requires_review_instead_of_guessing(): void
    {
        $invoice = $this->invoice($this->customer());
        $first = $this->item($invoice);
        $this->item($invoice);
        $note = $invoice->deliveryNotes()->create([
            'customer_id' => $invoice->customer_id, 'delivery_number' => 'LEGACY', 'dispatch_date' => '2026-10-14', 'recipient_name' => 'Recipient',
        ]);
        $note->items()->create(['product_name' => $first->product_name, 'size_s' => 2, 'total_qty' => 2]);
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $first->id, 'size_s' => 1, 'total_qty' => 1]]))->assertSessionHasErrors('invoice_id');
        $this->assertDatabaseCount('delivery_notes', 1);
    }

    public function test_delivery_status_reset_clears_stale_delivery_timestamp(): void
    {
        $invoice = $this->invoice($this->customer());
        $item = $this->item($invoice);
        $this->post(route('delivery.store'), $this->dispatch($invoice, [['invoice_item_id' => $item->id, 'size_s' => 1, 'total_qty' => 1]], ['status' => 'Delivered']))->assertSessionHasNoErrors();
        $note = DeliveryNote::firstOrFail();
        $this->assertNotNull($note->delivered_at);
        $this->patch(route('delivery.update-status', $note), ['status' => 'Dispatched'])->assertSessionHasNoErrors();
        $this->assertNull($note->fresh()->delivered_at);
    }
}
