<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ProductionOrder;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::create(['customer_code' => 'CUST-CHART', 'name' => 'Chart Customer']);
    }

    public function test_empty_production_charts_have_no_invented_counts_or_percentages(): void
    {
        $this->get('/')->assertOk()->assertViewHas('productionCharts', [
            'workload' => [],
            'max' => 0,
            'active' => 0,
            'completed' => 0,
            'total' => 0,
            'activePercent' => 0.0,
        ]);
    }

    public function test_all_completed_jobs_have_an_empty_workload_and_a_complete_overview(): void
    {
        $this->order('PO-COMPLETE-1', 'Completed');
        $this->order('PO-COMPLETE-2', 'Completed');

        $this->get('/')->assertOk()
            ->assertViewHas('productionCharts', [
                'workload' => [],
                'max' => 0,
                'active' => 0,
                'completed' => 2,
                'total' => 2,
                'activePercent' => 0.0,
            ])
            ->assertViewHas('stages', fn ($stages) => $stages['Completed'] === 'Production complete');
    }

    public function test_charts_count_the_whole_register_regardless_of_list_filter_and_page(): void
    {
        foreach (range(1, 10) as $number) {
            $this->order("PO-ACTIVE-{$number}", 'Confirmed');
        }
        foreach (range(1, 9) as $number) {
            $this->order("PO-DONE-{$number}", 'Completed');
        }

        $expected = [
            'workload' => [['stage' => 'Confirmed', 'label' => 'Confirmed', 'count' => 10, 'percent' => 100.0]],
            'max' => 10,
            'active' => 10,
            'completed' => 9,
            'total' => 19,
            'activePercent' => 52.63,
        ];
        $this->get('/')->assertOk()
            ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 8 && $orders->total() === 10)
            ->assertViewHas('productionCharts', $expected);
        $this->get('/?production_status=completed&page=2')->assertOk()
            ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 1 && $orders->total() === 9)
            ->assertViewHas('productionCharts', $expected);
    }

    public function test_new_and_historical_stages_are_counted_once_with_truthful_labels_and_stable_order(): void
    {
        $this->order('PO-HISTORICAL', 'Supplier Processing');
        $this->order('PO-PRINT-1', 'DTF Printing');
        $this->order('PO-PRINT-2', 'DTF Printing');
        $this->order('PO-GIFT', 'Gift Customization');
        $this->order('PO-CONFIRMED', 'Confirmed');
        $this->order('PO-READY', 'Ready for Delivery');
        $this->order('PO-COMPLETE', 'Completed');

        $this->get('/')->assertOk()->assertViewHas('productionCharts', [
            'workload' => [
                ['stage' => 'Confirmed', 'label' => 'Confirmed', 'count' => 1, 'percent' => 50.0],
                ['stage' => 'DTF Printing', 'label' => 'DTF Printing', 'count' => 2, 'percent' => 100.0],
                ['stage' => 'Gift Customization', 'label' => 'Gift Customization', 'count' => 1, 'percent' => 50.0],
                ['stage' => 'Ready for Delivery', 'label' => 'Ready for Delivery', 'count' => 1, 'percent' => 50.0],
                ['stage' => 'Supplier Processing', 'label' => 'Supplier Processing', 'count' => 1, 'percent' => 50.0],
            ],
            'max' => 2,
            'active' => 6,
            'completed' => 1,
            'total' => 7,
            'activePercent' => 85.71,
        ]);
    }

    public function test_mixed_category_invoice_lines_do_not_multiply_the_jobs_in_charts(): void
    {
        $invoice = SalesInvoice::create([
            'invoice_number' => 'INV-CHART', 'customer_id' => $this->customer->id,
            'invoice_date' => now(), 'status' => 'Unpaid',
        ]);
        foreach (['School Uniforms', 'Promotional Gifts', 'DTF Printing'] as $category) {
            $invoice->items()->create([
                'product_name' => $category, 'category' => $category,
                'pricing_unit' => 'piece', 'total_qty' => 10,
            ]);
        }
        $order = $this->order('PO-MIXED', 'Artwork Preparation');
        $order->update(['invoice_id' => $invoice->id, 'category' => 'Multi-Category']);

        $this->get('/')->assertOk()->assertViewHas('productionCharts', [
            'workload' => [['stage' => 'Artwork Preparation', 'label' => 'Artwork Preparation', 'count' => 1, 'percent' => 100.0]],
            'max' => 1,
            'active' => 1,
            'completed' => 0,
            'total' => 1,
            'activePercent' => 100.0,
        ]);
    }

    private function order(string $number, string $stage): ProductionOrder
    {
        return ProductionOrder::create([
            'order_number' => $number, 'customer_id' => $this->customer->id,
            'category' => 'School Uniforms', 'item_name' => 'Uniforms', 'total_qty' => 10,
            'stage' => $stage, 'priority' => 'Normal', 'target_date' => now()->addWeek(),
        ]);
    }
}
