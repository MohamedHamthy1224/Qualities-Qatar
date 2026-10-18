<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\DeliveryNote;
use App\Models\ProductionOrder;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay()->addHours(12));
        $this->customer = Customer::create(['customer_code' => 'CUST-PROD', 'name' => 'Production Customer']);
    }

    public function test_dashboard_counts_all_ongoing_jobs_and_filters_completed_jobs(): void
    {
        $older = $this->order('PO-OLDER', ['created_at' => now()->subDays(3)]);
        $today = $this->order('PO-TODAY');
        $complete = $this->order('PO-COMPLETE', ['stage' => 'Completed']);

        $this->get('/')->assertOk()
            ->assertViewHas('activeOrdersCount', 2)
            ->assertViewHas('completedOrdersCount', 1)
            ->assertViewHas('recentOrders', fn ($orders) => $orders->pluck('id')->sort()->values()->all() === [$older->id, $today->id])
            ->assertSee('Ongoing Production Jobs')
            ->assertDontSee('PO-COMPLETE');

        $this->get('/?production_status=completed')->assertOk()
            ->assertViewHas('recentOrders', fn ($orders) => $orders->pluck('id')->all() === [$complete->id])
            ->assertSee('Completed Production Jobs')
            ->assertSee('Production complete')
            ->assertDontSee('PO-OLDER');
    }

    public function test_dashboard_readiness_does_not_double_count_a_pending_delivery_note_and_only_dated_bills_are_overdue(): void
    {
        $readyInvoice = $this->invoice('INV-READY', ['due_date' => now()->addDay(), 'balance_due' => 200]);
        $this->order('PO-READY', ['invoice_id' => $readyInvoice->id, 'stage' => 'Ready for Delivery']);
        DeliveryNote::create([
            'delivery_number' => 'DN-READY', 'invoice_id' => $readyInvoice->id,
            'customer_id' => $this->customer->id, 'dispatch_date' => now(),
            'recipient_name' => 'Receiver', 'status' => 'Pending',
        ]);
        $this->invoice('INV-OVERDUE', ['due_date' => now()->subDay(), 'balance_due' => 75]);
        $this->invoice('INV-UNDATED', ['due_date' => null, 'balance_due' => 125]);
        $this->invoice('INV-DUE-TODAY', ['due_date' => now(), 'balance_due' => 50]);

        $this->get('/')->assertOk()
            ->assertViewHas('readyDeliveriesCount', 1)
            ->assertViewHas('overduePaymentsQAR', 75)
            ->assertViewHas('totalReceivablesQAR', 450);
    }

    public function test_mixed_jobs_show_all_items_and_separate_units_and_filter_by_line_category(): void
    {
        $invoice = $this->invoice('INV-MIXED');
        $invoice->items()->create([
            'product_name' => 'Polo Shirts', 'category' => 'School Uniforms',
            'pricing_unit' => 'piece', 'total_qty' => 10,
        ]);
        $invoice->items()->create([
            'product_name' => 'Logo Designs', 'category' => 'Computerized Embroidery',
            'input_mode' => 'service_qty', 'pricing_unit' => 'design', 'total_qty' => 2,
        ]);
        $order = $this->order('PO-MIXED', ['invoice_id' => $invoice->id, 'item_name' => 'Polo Shirts', 'total_qty' => 12]);
        $other = $this->order('PO-OTHER', ['category' => 'Sportswear']);

        $this->assertSame([
            'category' => 'Multi-Category', 'item_name' => 'Polo Shirts / Logo Designs', 'total_qty' => 12,
        ], ProductionOrder::summaryAttributesForInvoice($invoice));
        $this->get('/')->assertOk()->assertSee('Polo Shirts / Logo Designs')->assertSee('10 piece + 2 design');
        $this->get('/production?category=Computerized%20Embroidery')->assertOk()
            ->assertSee($order->order_number)->assertSee('Logo Designs')->assertDontSee($other->order_number);
        $this->get('/production?search=Logo%20Designs')->assertOk()->assertSee($order->order_number)->assertDontSee($other->order_number);
    }

    public function test_stage_updates_change_dashboard_counts_without_changing_delivery_or_payment(): void
    {
        $invoice = $this->invoice('INV-STATUS', ['grand_total' => 100, 'balance_due' => 100]);
        $order = $this->order('PO-STATUS', ['invoice_id' => $invoice->id, 'notes' => 'Keep instructions']);
        $note = DeliveryNote::create([
            'delivery_number' => 'DN-STATUS', 'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id, 'dispatch_date' => now(),
            'recipient_name' => 'Receiver', 'status' => 'Pending',
        ]);

        $this->postJson("/production/{$order->id}/stage", ['stage' => 'Completed'])->assertOk()->assertJsonPath('stage', 'Completed');
        $this->assertSame('Completed', $order->fresh()->stage);
        $this->assertSame('Keep instructions', $order->fresh()->notes);
        $this->assertSame('Pending', $note->fresh()->status);
        $this->assertSame('Unpaid', $invoice->fresh()->status);
        $this->assertEquals(100, $invoice->fresh()->balance_due);
        $this->assertSame(0, CustomerReceipt::count());
        $this->get('/')->assertOk()->assertViewHas('activeOrdersCount', 0)->assertViewHas('completedOrdersCount', 1);
        $this->get('/production?status=completed')->assertOk()->assertSee('PO-STATUS')->assertSee('Production complete');
        $this->get('/production?status=ongoing')->assertOk()->assertDontSee('PO-STATUS');
    }

    public function test_stage_priority_and_filters_reject_invalid_values_without_mutation(): void
    {
        $order = $this->order('PO-VALIDATE');
        $this->postJson("/production/{$order->id}/stage", ['stage' => 'Paid'])->assertUnprocessable()->assertJsonValidationErrors('stage');
        $this->postJson("/production/{$order->id}/priority", ['priority' => 'Invalid'])->assertUnprocessable()->assertJsonValidationErrors('priority');
        $this->assertSame('Confirmed', $order->fresh()->stage);
        $this->assertSame('Normal', $order->fresh()->priority);
        $this->getJson('/production?priority=Invalid')->assertUnprocessable()->assertJsonValidationErrors('priority');
        $this->getJson('/?production_status=invalid')->assertUnprocessable()->assertJsonValidationErrors('production_status');
    }

    public function test_dashboard_pagination_keeps_completed_filter(): void
    {
        foreach (range(1, 9) as $number) {
            $this->order("PO-COMPLETED-{$number}", ['stage' => 'Completed']);
        }
        $this->get('/?production_status=completed')->assertOk()
            ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 8 && $orders->total() === 9)
            ->assertSee('production_status=completed', false);
        $this->get('/?production_status=completed&page=2')->assertOk()
            ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 1);
    }

    private function order(string $number, array $attributes = []): ProductionOrder
    {
        return ProductionOrder::create(array_merge([
            'order_number' => $number, 'customer_id' => $this->customer->id,
            'category' => 'School Uniforms', 'item_name' => 'Uniforms', 'total_qty' => 10,
            'stage' => 'Confirmed', 'priority' => 'Normal', 'target_date' => now()->addWeek(),
        ], $attributes));
    }

    private function invoice(string $number, array $attributes = []): SalesInvoice
    {
        return SalesInvoice::create(array_merge([
            'invoice_number' => $number, 'customer_id' => $this->customer->id,
            'invoice_date' => now(), 'status' => 'Unpaid',
        ], $attributes));
    }
}
