<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ProductionOrder;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionGridViewTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::create([
            'customer_code' => 'CUST-PRODUCTION-GRID',
            'name' => 'Production Grid Customer',
        ]);
    }

    public function test_table_is_the_default_and_view_links_preserve_filters_with_an_accessible_selected_state(): void
    {
        $this->order('PO-GRID-SWITCH');

        foreach (['table' => '/production', 'grid' => '/production?view=grid&priority=High'] as $selectedView => $url) {
            $response = $this->get($url)->assertOk();
            $xpath = $this->document($response->getContent());
            $page = $xpath->query('//*[@data-production-page]')->item(0);
            $this->assertSame($selectedView, $page->getAttribute('data-view'));

            $switch = '//*[contains(concat(" ", normalize-space(@class), " "), " production-view-switch ")]';
            $links = $xpath->query($switch.'//a');
            $this->assertSame(2, $links->length);
            $selected = $xpath->query($switch.'//a[@aria-current="true"]');
            $this->assertSame(1, $selected->length);
            $this->assertSame(ucfirst($selectedView), trim($selected->item(0)->textContent));
            foreach ($links as $link) {
                $parameters = $this->queryParameters($link->getAttribute('href'));
                $this->assertContains($parameters['view'] ?? null, ['table', 'grid']);
                if ($selectedView === 'grid') {
                    $this->assertSame('High', $parameters['priority'] ?? null);
                }
            }

            $viewFields = $xpath->query('//form[@id="productionFilters"]/input[@type="hidden" and @name="view"]');
            $this->assertSame(1, $viewFields->length);
            $this->assertSame($selectedView, $viewFields->item(0)->getAttribute('value'));
        }
    }

    public function test_each_grid_progress_indicator_uses_its_own_category_workflow(): void
    {
        $cases = [
            ['Computerized Embroidery', 'Embroidery', 3, 6, 'Embroidery'],
            ['DTF Printing', 'Heat Pressing', 4, 7, 'Heat Pressing'],
            ['Promotional Gifts', 'Gift Customization', 3, 6, 'Gift Customization'],
            ['School Uniforms', 'Cutting & Stitching', 2, 6, 'Cutting & Stitching'],
            ['School Uniforms', 'Confirmed', 1, 6, 'Confirmed'],
            ['School Uniforms', 'Completed', 6, 6, 'Production complete'],
        ];
        $orders = [];
        foreach ($cases as $index => [$category, $stage, $current, $total, $label]) {
            $order = $this->order('PO-GRID-PROGRESS-'.$index, compact('category', 'stage'));
            $this->assertSame(compact('current', 'total', 'label'), $order->workflow_progress);
            $orders[] = [$order, $current, $total, $label];
        }

        $response = $this->get('/production?view=grid')->assertOk();
        $xpath = $this->document($response->getContent());
        foreach ($orders as [$order, $current, $total, $label]) {
            $progress = $this->progressNode($xpath, $order);
            $this->assertSame((string) $current, $progress->getAttribute('data-current-stage'));
            $this->assertSame((string) $total, $progress->getAttribute('data-total-stages'));
            $this->assertSame(1, $xpath->query('.//progress', $progress)->length);
            $this->assertStringContainsString($label, $progress->textContent);
        }
    }

    public function test_mixed_grid_job_uses_the_combined_workflow_without_counting_unrelated_processes(): void
    {
        $invoice = SalesInvoice::create([
            'invoice_number' => 'INV-GRID-MIXED', 'customer_id' => $this->customer->id,
            'invoice_date' => now(), 'status' => 'Unpaid',
        ]);
        foreach (['Computerized Embroidery', 'Promotional Gifts'] as $category) {
            $invoice->items()->create([
                'product_name' => $category.' item', 'category' => $category,
                'pricing_unit' => 'piece', 'total_qty' => 10,
            ]);
        }
        $order = $this->order('PO-GRID-MIXED', [
            'invoice_id' => $invoice->id, 'category' => 'School Uniforms', 'stage' => 'Gift Customization',
        ]);
        $this->assertSame(['current' => 4, 'total' => 7, 'label' => 'Gift Customization'], $order->workflow_progress);

        $response = $this->get('/production?view=grid')->assertOk();
        $xpath = $this->document($response->getContent());
        $progress = $this->progressNode($xpath, $order);
        $this->assertSame('4', $progress->getAttribute('data-current-stage'));
        $this->assertSame('7', $progress->getAttribute('data-total-stages'));
        $options = $this->stageOptions($xpath, $order);
        $this->assertContains('Embroidery', $options);
        $this->assertContains('Gift Customization', $options);
        $this->assertNotContains('DTF Printing', $options);
        $this->assertNotContains('Cutting & Stitching', $options);
    }

    public function test_legacy_statuses_remain_truthful_without_a_fabricated_progress_bar(): void
    {
        $legacy = $this->order('PO-GRID-OLD-CATEGORY', [
            'category' => 'Promotional Gifts', 'stage' => 'Embroidery / DTF Printing',
        ]);
        $unknown = $this->order('PO-GRID-UNKNOWN-STAGE', ['stage' => 'Awaiting client thread approval']);

        $response = $this->get('/production?view=grid')->assertOk();
        $xpath = $this->document($response->getContent());
        foreach ([$legacy, $unknown] as $order) {
            $this->assertNull($order->workflow_progress['current']);
            $this->assertSame($order->stage_label, $order->workflow_progress['label']);
            $progress = $this->progressNode($xpath, $order);
            $this->assertSame('', $progress->getAttribute('data-current-stage'));
            $this->assertSame(0, $xpath->query('.//progress', $progress)->length);
            $this->assertStringContainsString($order->stage_label, $progress->textContent);
            $this->assertContains($order->stage, $this->stageOptions($xpath, $order));
            $this->assertSame($order->stage, $order->fresh()->stage);
        }
    }

    public function test_grid_filter_form_pagination_and_inline_update_actions_retain_the_selected_view(): void
    {
        foreach (range(1, 21) as $number) {
            $this->order(sprintf('PO-GRID-PAGED-%02d', $number), ['priority' => 'High']);
        }
        $query = http_build_query([
            'view' => 'grid', 'search' => 'PO-GRID-PAGED', 'category' => 'School Uniforms',
            'priority' => 'High', 'status' => 'ongoing', 'stage' => 'Confirmed',
        ]);

        $response = $this->get('/production?'.$query)->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 20 && $orders->total() === 21);
        $xpath = $this->document($response->getContent());
        $forms = $xpath->query('//form[@data-production-update]');
        $this->assertSame(40, $forms->length, 'Each displayed job has one stage and one priority control.');
        foreach ($forms as $form) {
            $parameters = $this->queryParameters($form->getAttribute('action'));
            $this->assertSame('grid', $parameters['view'] ?? null);
            $this->assertSame('High', $parameters['priority'] ?? null);
            $this->assertSame('Confirmed', $parameters['stage'] ?? null);
        }
        $pageTwoUrl = null;
        foreach ($xpath->query('//*[@id="productionPagination"]//a') as $link) {
            $parameters = $this->queryParameters($link->getAttribute('href'));
            $this->assertSame('grid', $parameters['view'] ?? null);
            if (($parameters['page'] ?? null) === '2') {
                $pageTwoUrl = $link->getAttribute('href');
            }
        }
        $this->assertNotNull($pageTwoUrl);
        $this->get($pageTwoUrl)->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 1 && $orders->total() === 21);
    }

    public function test_grid_status_autosave_refreshes_progress_options_and_the_selected_view_url(): void
    {
        $order = $this->order('PO-GRID-SAVE', ['category' => 'DTF Printing', 'stage' => 'DTF Printing']);

        $response = $this->postJson("/production/{$order->id}/stage?view=grid", [
            'stage' => 'Heat Pressing', 'previous_stage' => 'DTF Printing',
        ])->assertOk()->assertJsonPath('stage', 'Heat Pressing')
            ->assertJsonPath('stage_label', 'Heat Pressing')->assertJsonPath('matches_filters', true);
        $this->assertSame('grid', $this->queryParameters($response->json('page_url'))['view'] ?? null);
        $xpath = $this->document('<table><tbody>'.$response->json('rows_html').'</tbody></table>');
        $progress = $this->progressNode($xpath, $order);
        $this->assertSame('4', $progress->getAttribute('data-current-stage'));
        $this->assertSame('7', $progress->getAttribute('data-total-stages'));
        $this->assertContains('Heat Pressing', $this->stageOptions($xpath, $order));
        $this->assertNotContains('Cutting & Stitching', $this->stageOptions($xpath, $order));
        foreach ($xpath->query('//form[@data-production-update]') as $form) {
            $this->assertSame('grid', $this->queryParameters($form->getAttribute('action'))['view'] ?? null);
        }
        $this->assertSame('Heat Pressing', $order->fresh()->stage);
    }

    public function test_grid_priority_autosave_keeps_mode_when_a_job_leaves_the_filter(): void
    {
        $order = $this->order('PO-GRID-PRIORITY', ['priority' => 'Urgent']);

        $response = $this->postJson("/production/{$order->id}/priority?view=grid&priority=Urgent", [
            'priority' => 'Normal', 'previous_priority' => 'Urgent',
        ])->assertOk()->assertJsonPath('matches_filters', false)->assertJsonPath('totalActive', 0);
        $parameters = $this->queryParameters($response->json('page_url'));
        $this->assertSame('grid', $parameters['view'] ?? null);
        $this->assertSame('Urgent', $parameters['priority'] ?? null);
        $this->assertStringNotContainsString($order->order_number, $response->json('rows_html'));
        $this->assertSame('Normal', $order->fresh()->priority);
        $this->assertSame('Confirmed', $order->fresh()->stage);
    }

    private function order(string $number, array $attributes = []): ProductionOrder
    {
        return ProductionOrder::create(array_merge([
            'order_number' => $number, 'customer_id' => $this->customer->id,
            'category' => 'School Uniforms', 'item_name' => 'Uniforms', 'total_qty' => 10,
            'stage' => 'Confirmed', 'priority' => 'Normal', 'target_date' => now()->addWeek(),
        ], $attributes));
    }

    private function document(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);

        return new \DOMXPath($document);
    }

    private function progressNode(\DOMXPath $xpath, ProductionOrder $order): \DOMElement
    {
        $nodes = $xpath->query('//*[@id="production-order-'.$order->id.'"]//*[contains(concat(" ", normalize-space(@class), " "), " production-progress ")]');
        $this->assertSame(1, $nodes->length, 'Each job should have one category-specific progress indicator.');

        return $nodes->item(0);
    }

    private function stageOptions(\DOMXPath $xpath, ProductionOrder $order): array
    {
        $nodes = $xpath->query('//select[@id="stage-'.$order->id.'"]/option');
        $values = [];
        foreach ($nodes as $option) {
            $values[] = $option->getAttribute('value');
        }

        return $values;
    }

    private function queryParameters(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parameters);

        return $parameters;
    }
}
