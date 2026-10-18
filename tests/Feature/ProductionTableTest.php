<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\DeliveryNote;
use App\Models\ProductionOrder;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionTableTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 17)->startOfDay()->addHours(12));
        $this->customer = Customer::create([
            'customer_code' => 'CUST-PRODUCTION-TABLE',
            'name' => 'Production Table Customer',
        ]);
    }

    public function test_table_has_category_specific_status_options_and_preserves_full_job_details(): void
    {
        $embroidery = $this->order('PO-EMBROIDERY', ['category' => 'Computerized Embroidery']);
        $printing = $this->order('PO-PRINTING', ['category' => 'DTF Printing']);
        $gifts = $this->order('PO-GIFTS', ['category' => 'Promotional Gifts']);
        $uniform = $this->order('PO-UNIFORM');

        $response = $this->get('/production')->assertOk()
            ->assertSee('id="productionJobsTable"', false)
            ->assertDontSee('id="productionBoard"', false)
            ->assertDontSee('draggable="true"', false);

        $embroideryOptions = $this->selectOptions($response->getContent(), "stage-{$embroidery->id}");
        $this->assertContains('Embroidery', $embroideryOptions);
        $this->assertNotContains('Cutting & Stitching', $embroideryOptions);
        $this->assertNotContains('DTF Printing', $embroideryOptions);

        $printingOptions = $this->selectOptions($response->getContent(), "stage-{$printing->id}");
        $this->assertContains('DTF Printing', $printingOptions);
        $this->assertContains('Heat Pressing', $printingOptions);
        $this->assertNotContains('Embroidery', $printingOptions);
        $this->assertNotContains('Gift Customization', $printingOptions);

        $giftsOptions = $this->selectOptions($response->getContent(), "stage-{$gifts->id}");
        $this->assertContains('Gift Customization', $giftsOptions);
        $this->assertNotContains('Cutting & Stitching', $giftsOptions);
        $this->assertNotContains('Embroidery', $giftsOptions);
        $this->assertContains('Cutting & Stitching', $this->selectOptions($response->getContent(), "stage-{$uniform->id}"));
    }

    public function test_server_rejects_an_unrelated_status_and_notes_without_mutating_the_job(): void
    {
        $order = $this->order('PO-VALIDATE-CATEGORY', [
            'category' => 'DTF Printing', 'notes' => 'Keep approved artwork size',
        ]);

        $this->postJson("/production/{$order->id}/stage", [
            'stage' => 'Cutting & Stitching', 'notes' => 'Must not be saved',
        ])->assertUnprocessable()->assertJsonValidationErrors('stage');

        $this->assertSame('Confirmed', $order->fresh()->stage);
        $this->assertSame('Keep approved artwork size', $order->fresh()->notes);

        $this->postJson("/production/{$order->id}/stage", [
            'stage' => 'DTF Printing', 'previous_stage' => 'Confirmed',
        ])->assertOk()->assertJsonPath('stage', 'DTF Printing')
            ->assertJsonPath('stage_label', 'DTF Printing');
        $this->assertSame('Keep approved artwork size', $order->fresh()->notes);
    }

    public function test_mixed_job_uses_line_categories_for_statuses_and_category_filtering(): void
    {
        $invoice = $this->invoice('INV-MIXED-TABLE');
        $invoice->items()->create([
            'product_name' => 'School Polo Shirts', 'category' => 'School Uniforms',
            'pricing_unit' => 'piece', 'total_qty' => 10,
        ]);
        $invoice->items()->create([
            'product_name' => 'Personalized Gift Sets', 'category' => 'Promotional Gifts',
            'pricing_unit' => 'set', 'total_qty' => 2,
        ]);
        $order = $this->order('PO-MIXED-TABLE', [
            'invoice_id' => $invoice->id, 'category' => 'School Uniforms',
            'item_name' => 'Stale header description', 'total_qty' => 12,
        ]);
        $other = $this->order('PO-OTHER-TABLE', ['category' => 'Computerized Embroidery']);

        $this->assertSame(['School Uniforms', 'Promotional Gifts'], $order->workflow_categories->all());
        $response = $this->get('/production?category=Promotional%20Gifts')->assertOk()
            ->assertSee($order->order_number)->assertDontSee($other->order_number)
            ->assertSee('School Polo Shirts')->assertSee('Personalized Gift Sets')
            ->assertSee('10 piece + 2 set');
        $options = $this->selectOptions($response->getContent(), "stage-{$order->id}");
        $this->assertContains('Cutting & Stitching', $options);
        $this->assertContains('Gift Customization', $options);

        $this->postJson("/production/{$order->id}/stage", ['stage' => 'Gift Customization'])
            ->assertOk()->assertJsonPath('stage', 'Gift Customization');
    }

    public function test_historical_status_is_visible_but_cannot_be_reselected_after_leaving_it(): void
    {
        $order = $this->order('PO-LEGACY-TABLE', [
            'category' => 'Computerized Embroidery', 'stage' => 'Cutting & Stitching',
        ]);

        $response = $this->get('/production')->assertOk()->assertSee('PO-LEGACY-TABLE');
        $this->assertContains('Cutting & Stitching', $this->selectOptions($response->getContent(), "stage-{$order->id}"));
        $this->assertSame('Cutting & Stitching', $order->fresh()->stage);

        $this->postJson("/production/{$order->id}/stage", ['stage' => 'Embroidery'])->assertOk();
        $this->postJson("/production/{$order->id}/stage", ['stage' => 'Cutting & Stitching'])
            ->assertUnprocessable()->assertJsonValidationErrors('stage');
        $this->assertSame('Embroidery', $order->fresh()->stage);
    }

    public function test_outdated_status_submission_cannot_overwrite_another_staff_update(): void
    {
        $order = $this->order('PO-CONCURRENT-TABLE', ['category' => 'DTF Printing']);
        $this->postJson("/production/{$order->id}/stage", [
            'stage' => 'Artwork Preparation', 'previous_stage' => 'Confirmed',
        ])->assertOk();

        $this->postJson("/production/{$order->id}/stage", [
            'stage' => 'DTF Printing', 'previous_stage' => 'Confirmed',
            'notes' => 'Outdated notes must not replace current work',
        ])->assertStatus(409);
        $this->assertSame('Artwork Preparation', $order->fresh()->stage);
        $this->assertNull($order->fresh()->notes);
    }

    public function test_row_update_reports_filtered_counts_and_keeps_financial_and_delivery_records_unchanged(): void
    {
        $invoice = $this->invoice('INV-STATUS-TABLE', ['grand_total' => 150, 'balance_due' => 150]);
        $order = $this->order('PO-STATUS-TABLE', [
            'invoice_id' => $invoice->id, 'priority' => 'Urgent',
        ]);
        $note = DeliveryNote::create([
            'delivery_number' => 'DN-STATUS-TABLE', 'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id, 'dispatch_date' => now(),
            'recipient_name' => 'School Stores', 'status' => 'Pending',
        ]);

        $this->postJson("/production/{$order->id}/stage?status=ongoing", [
            'stage' => 'Completed', 'previous_stage' => 'Confirmed',
        ])->assertOk()->assertJsonPath('stage_label', 'Production complete')
            ->assertJsonPath('totalActive', 0)->assertJsonPath('totalCompleted', 0)
            ->assertJsonPath('urgentCount', 0)->assertJsonPath('matches_filters', false);

        $this->assertSame('Pending', $note->fresh()->status);
        $this->assertSame('Unpaid', $invoice->fresh()->status);
        $this->assertEquals(150, $invoice->fresh()->balance_due);
        $this->assertSame(0, CustomerReceipt::count());
        $this->get('/')->assertOk()->assertViewHas('activeOrdersCount', 0)
            ->assertViewHas('completedOrdersCount', 1);
    }

    public function test_priority_row_update_returns_json_and_reports_when_row_leaves_priority_filter(): void
    {
        $order = $this->order('PO-PRIORITY-TABLE', ['priority' => 'Urgent']);

        $this->postJson("/production/{$order->id}/priority?priority=Urgent", ['priority' => 'Normal'])
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('priority', 'Normal')
            ->assertJsonPath('matches_filters', false);
        $this->assertSame('Normal', $order->fresh()->priority);
        $this->assertSame('Confirmed', $order->fresh()->stage);
    }

    public function test_table_paginates_jobs_and_retains_all_selected_filters(): void
    {
        foreach (range(1, 21) as $number) {
            $this->order(sprintf('PO-FILTERED-%02d', $number), ['priority' => 'High']);
        }
        $this->order('PO-EXCLUDED', ['category' => 'Promotional Gifts']);
        $query = http_build_query([
            'search' => 'PO-FILTERED', 'category' => 'School Uniforms', 'priority' => 'High',
            'status' => 'ongoing', 'stage' => 'Confirmed',
        ]);

        $response = $this->get('/production?'.$query)->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 20 && $orders->total() === 21)
            ->assertViewHas('totalActive', 21)->assertDontSee('PO-EXCLUDED');
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $pageTwoLink = null;
        foreach ($document->getElementsByTagName('a') as $link) {
            $url = $link->getAttribute('href');
            parse_str((string) parse_url($url, PHP_URL_QUERY), $parameters);
            if (($parameters['page'] ?? null) === '2') {
                $pageTwoLink = $url;
                foreach (['search' => 'PO-FILTERED', 'category' => 'School Uniforms', 'priority' => 'High', 'status' => 'ongoing', 'stage' => 'Confirmed'] as $key => $expected) {
                    $this->assertSame($expected, $parameters[$key] ?? null);
                }
                break;
            }
        }
        $this->assertNotNull($pageTwoLink, 'A second page link should preserve the production filters.');
        $this->get('/production?'.$query.'&page=2')->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 1 && $orders->total() === 21);
    }

    public function test_last_row_leaving_a_filtered_page_returns_the_previous_page_and_correct_update_urls(): void
    {
        foreach (range(1, 21) as $number) {
            $lastOrder = $this->order(sprintf('PO-PAGE-CLAMP-%02d', $number), ['priority' => 'High']);
        }
        $query = http_build_query(['priority' => 'High', 'status' => 'ongoing', 'page' => 2]);

        $response = $this->postJson("/production/{$lastOrder->id}/stage?{$query}", [
            'stage' => 'Completed', 'previous_stage' => 'Confirmed',
        ])->assertOk()->assertJsonPath('matches_filters', false)->assertJsonPath('totalActive', 20);

        parse_str((string) parse_url($response->json('page_url'), PHP_URL_QUERY), $pageParameters);
        $this->assertSame('1', $pageParameters['page'] ?? null);
        $this->assertSame('High', $pageParameters['priority'] ?? null);
        $this->assertSame('ongoing', $pageParameters['status'] ?? null);
        $this->assertStringNotContainsString('PO-PAGE-CLAMP-21', $response->json('rows_html'));

        $document = new \DOMDocument;
        @$document->loadHTML('<table>'.$response->json('rows_html').'</table>');
        $forms = $document->getElementsByTagName('form');
        $this->assertGreaterThan(0, $forms->length);
        foreach ($forms as $form) {
            parse_str((string) parse_url($form->getAttribute('action'), PHP_URL_QUERY), $parameters);
            $this->assertSame('1', $parameters['page'] ?? null, 'Inline updates must target the displayed page after clamping.');
            $this->assertSame('High', $parameters['priority'] ?? null);
            $this->assertSame('ongoing', $parameters['status'] ?? null);
        }
    }

    public function test_historical_status_filter_remains_usable_after_its_last_job_changes_status(): void
    {
        $historicalStage = 'Legacy Pressing Queue';
        $order = $this->order('PO-HISTORICAL-FILTER', ['stage' => $historicalStage]);
        $this->order('PO-UNRELATED-FILTER');
        $query = http_build_query(['stage' => $historicalStage]);

        $this->get('/production?'.$query)->assertOk()
            ->assertSee($order->order_number)->assertDontSee('PO-UNRELATED-FILTER');
        $response = $this->postJson("/production/{$order->id}/stage?{$query}", [
            'stage' => 'Completed', 'previous_stage' => $historicalStage,
        ])->assertOk()->assertJsonPath('matches_filters', false)
            ->assertJsonPath('totalActive', 0)->assertJsonPath('totalCompleted', 0);
        $this->assertSame('Completed', $order->fresh()->stage);
        $this->assertStringNotContainsString($order->order_number, $response->json('rows_html'));

        $emptyResponse = $this->get('/production?'.$query)->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->total() === 0)
            ->assertViewHas('stages', fn ($stages) => array_key_exists($historicalStage, $stages));
        $document = new \DOMDocument;
        @$document->loadHTML($emptyResponse->getContent());
        $xpath = new \DOMXPath($document);
        $selected = $xpath->query('//select[@name="stage"]/option[@selected]');
        $this->assertSame(1, $selected->length);
        $this->assertSame($historicalStage, $selected->item(0)->getAttribute('value'));
    }

    private function selectOptions(string $html, string $id): array
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $selects = $xpath->query('//select[@id="'.$id.'"]');
        $this->assertSame(1, $selects->length, "Expected one status control for {$id}.");
        $values = [];
        foreach ($selects->item(0)->getElementsByTagName('option') as $option) {
            $values[] = $option->getAttribute('value');
        }

        return $values;
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
