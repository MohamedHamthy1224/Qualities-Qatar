<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\ProductionOrder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionDonutTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'customer_code' => 'CUST-DONUT',
            'name' => 'Production chart customer',
        ]);
    }

    public function test_donut_exposes_existing_counts_percentages_and_accessible_filter_links(): void
    {
        $this->orders(13, 'Confirmed');
        $this->orders(1, 'Completed');

        $response = $this->get('/')->assertOk()->assertViewHas('productionCharts', [
            'workload' => [['stage' => 'Confirmed', 'label' => 'Confirmed', 'count' => 13, 'percent' => 100.0]],
            'max' => 13,
            'active' => 13,
            'completed' => 1,
            'total' => 14,
            'activePercent' => 92.86,
        ]);

        [$xpath, $chart] = $this->chart($response->getContent());

        $this->assertStringContainsString('7.1%', $chart->textContent);
        $this->assertStringContainsString('92.9%', $chart->textContent);
        $this->assertSame(2, $this->segmentCount($xpath, $chart));
        $this->assertSeriesLinks($xpath, $chart, 'ongoing', 13, '92.9');
        $this->assertSeriesLinks($xpath, $chart, 'completed', 1, '7.1');

        foreach (['data-donut-tooltip', 'data-donut-label', 'data-donut-count', 'data-donut-percent'] as $attribute) {
            $this->assertGreaterThan(0, $xpath->query(".//*[@{$attribute}]", $chart)->length, "Missing {$attribute} display hook.");
        }
    }

    public function test_original_chart_can_be_selected_without_losing_the_saved_counts(): void
    {
        $this->orders(2, 'Confirmed');
        $this->orders(1, 'Completed');

        [$xpath, $chart] = $this->chart($this->get('/')->assertOk()->getContent());

        $original = $xpath->query('.//*[@data-donut-view="original"]', $chart);
        $this->assertSame(1, $original->length);
        $this->assertTrue($original->item(0)->hasAttribute('hidden'), 'Original style is retained and hidden until selected.');
        $this->assertStringContainsString('66.7%', $original->item(0)->textContent);
        $this->assertStringContainsString('33.3%', $original->item(0)->textContent);

        $buttons = $xpath->query('.//button[@data-donut-style-choice]', $chart);
        $styles = [];
        foreach ($buttons as $button) {
            $styles[$button->getAttribute('data-donut-style-choice')] = $button->getAttribute('aria-pressed');
            $this->assertSame('button', $button->getAttribute('type'));
        }

        $this->assertSame(['interactive' => 'true', 'original' => 'false'], $styles);
    }

    public function test_zero_jobs_have_an_empty_state_and_no_clickable_svg_slices(): void
    {
        $response = $this->get('/')->assertOk()->assertViewHas('productionCharts', [
            'workload' => [], 'max' => 0, 'active' => 0, 'completed' => 0, 'total' => 0, 'activePercent' => 0.0,
        ]);
        [$xpath, $chart] = $this->chart($response->getContent());

        $this->assertSame(0, $this->segmentCount($xpath, $chart));
        $this->assertStringContainsString('No production jobs yet', $chart->textContent);
        $this->assertStringNotContainsString('NaN', $chart->textContent);
        $this->assertStringNotContainsString('Infinity', $chart->textContent);
        $this->assertStringNotContainsString('100%', $chart->textContent);
        $this->assertStringNotContainsString('100.0%', $chart->textContent);
    }

    public function test_all_completed_jobs_render_one_real_segment_and_truthful_zero_ongoing_count(): void
    {
        $this->orders(3, 'Completed');

        $response = $this->get('/')->assertOk()->assertViewHas('productionCharts', [
            'workload' => [], 'max' => 0, 'active' => 0, 'completed' => 3, 'total' => 3, 'activePercent' => 0.0,
        ]);
        [$xpath, $chart] = $this->chart($response->getContent());

        $this->assertSame(1, $this->segmentCount($xpath, $chart));
        $this->assertSeriesLinks($xpath, $chart, 'completed', 3, '100.0');
        $this->assertStringContainsString('100.0%', $chart->textContent);
        $this->assertSame(0, $xpath->query('.//*[@data-donut-view="interactive"]//svg//*[@data-donut-series="ongoing"]', $chart)->length);
    }

    public function test_all_ongoing_jobs_render_one_real_segment_and_zero_completion_percentage(): void
    {
        $this->orders(4, 'Confirmed');

        $response = $this->get('/')->assertOk()->assertViewHas('productionCharts', [
            'workload' => [['stage' => 'Confirmed', 'label' => 'Confirmed', 'count' => 4, 'percent' => 100.0]],
            'max' => 4, 'active' => 4, 'completed' => 0, 'total' => 4, 'activePercent' => 100.0,
        ]);
        [$xpath, $chart] = $this->chart($response->getContent());

        $this->assertSame(1, $this->segmentCount($xpath, $chart));
        $this->assertSeriesLinks($xpath, $chart, 'ongoing', 4, '100.0');
        $this->assertStringContainsString('0.0%', $chart->textContent);
        $this->assertSame(0, $xpath->query('.//*[@data-donut-view="interactive"]//svg//*[@data-donut-series="completed"]', $chart)->length);
    }

    public function test_list_filter_and_pagination_do_not_change_chart_values_or_saved_jobs(): void
    {
        $this->orders(13, 'Confirmed');
        $this->orders(1, 'Completed');
        $before = ProductionOrder::orderBy('id')->get()->toArray();

        $default = $this->get('/')->assertOk();
        $filtered = $this->get('/?production_status=completed&per_page=15&page=1')->assertOk();
        $this->assertSame($default->viewData('productionCharts'), $filtered->viewData('productionCharts'));

        [$xpath, $chart] = $this->chart($filtered->getContent());
        $this->assertSeriesLinks($xpath, $chart, 'ongoing', 13, '92.9');
        $this->assertSeriesLinks($xpath, $chart, 'completed', 1, '7.1');
        $this->assertSame($before, ProductionOrder::orderBy('id')->get()->toArray());
    }

    private function orders(int $count, string $stage): void
    {
        foreach (range(1, $count) as $number) {
            ProductionOrder::create([
                'order_number' => "PO-DONUT-{$stage}-{$number}",
                'customer_id' => $this->customer->id,
                'category' => 'School Uniforms',
                'item_name' => 'Uniforms',
                'total_qty' => 10,
                'stage' => $stage,
                'priority' => 'Normal',
                'target_date' => now()->addWeek(),
            ]);
        }
    }

    /** @return array{DOMXPath, DOMElement} */
    private function chart(string $html): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $charts = $xpath->query('//*[@data-production-donut]');
        $this->assertSame(1, $charts->length, 'The production chart should have one interaction root.');

        return [$xpath, $charts->item(0)];
    }

    private function segmentCount(DOMXPath $xpath, DOMElement $chart): int
    {
        return $xpath->query('.//*[@data-donut-view="interactive"]//svg//a[@data-donut-series]', $chart)->length;
    }

    private function assertSeriesLinks(DOMXPath $xpath, DOMElement $chart, string $series, int $count, string $percent): void
    {
        $links = $xpath->query(".//*[@data-donut-view='interactive']//a[@data-donut-series='{$series}']", $chart);
        $this->assertGreaterThan(0, $links->length, "Missing {$series} filter links.");

        foreach ($links as $link) {
            $query = [];
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY) ?? '', $query);
            $this->assertSame('/production', parse_url($link->getAttribute('href'), PHP_URL_PATH));
            $this->assertSame($series, $query['status'] ?? null);
            $this->assertStringContainsString((string) $count, $link->getAttribute('aria-label'));
            $this->assertStringContainsString($percent.'%', $link->getAttribute('aria-label'));
            $this->assertSame((string) $count, $link->getAttribute('data-donut-count'));
            $this->assertSame($percent, $link->getAttribute('data-donut-percent'));
        }
    }
}
