<?php

namespace Tests\Unit;

use App\Models\InvoiceItem;
use App\Models\ProductionOrder;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesInvoice;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductionWorkflowTest extends TestCase
{
    #[DataProvider('garmentCategories')]
    public function test_garment_jobs_keep_the_existing_workflow(string $category): void
    {
        $order = $this->order($category);

        $this->assertSame([
            'Confirmed', 'Cutting & Stitching', 'Embroidery / DTF Printing',
            'Quality Check', 'Ready for Delivery', 'Completed',
        ], array_keys($order->stage_options));
    }

    public static function garmentCategories(): array
    {
        return [
            ['School Uniforms'], ['Corporate Uniforms'], ['Sportswear'],
        ];
    }

    public function test_embroidery_jobs_have_an_embroidery_process_without_garment_or_printing_steps(): void
    {
        $order = $this->order('Computerized Embroidery');

        $this->assertSame([
            'Confirmed', 'Artwork Preparation', 'Embroidery',
            'Quality Check', 'Ready for Delivery', 'Completed',
        ], array_keys($order->stage_options));
        $this->assertArrayNotHasKey('Cutting & Stitching', $order->stage_options);
        $this->assertArrayNotHasKey('Embroidery / DTF Printing', $order->stage_options);
        $this->assertArrayNotHasKey('DTF Printing', $order->stage_options);
    }

    public function test_dtf_jobs_distinguish_printing_from_heat_pressing(): void
    {
        $order = $this->order('DTF Printing', 'DTF Printing');

        $this->assertSame([
            'Confirmed', 'Artwork Preparation', 'DTF Printing', 'Heat Pressing',
            'Quality Check', 'Ready for Delivery', 'Completed',
        ], array_keys($order->stage_options));
        $this->assertSame('DTF Printing', $order->stage_label);
        $this->assertArrayNotHasKey('Embroidery', $order->stage_options);
        $this->assertArrayNotHasKey('Cutting & Stitching', $order->stage_options);
    }

    public function test_gift_customization_uses_its_own_stored_stage_and_truthful_label(): void
    {
        $order = $this->order('Promotional Gifts', 'Gift Customization');

        $this->assertSame([
            'Confirmed', 'Artwork Preparation', 'Gift Customization',
            'Quality Check', 'Ready for Delivery', 'Completed',
        ], array_keys($order->stage_options));
        $this->assertSame('Gift Customization', $order->stage_label);
        $this->assertArrayNotHasKey('Embroidery / DTF Printing', $order->stage_options);
        $this->assertArrayNotHasKey('DTF Printing', $order->stage_options);
        $this->assertArrayNotHasKey('Cutting & Stitching', $order->stage_options);
    }

    public function test_mixed_job_uses_all_invoice_line_categories_instead_of_stale_header_or_quotation(): void
    {
        $order = $this->order('School Uniforms');
        $order->setRelation('invoice', $this->invoice([
            'Computerized Embroidery', 'DTF Printing', 'Computerized Embroidery', 'Promotional Gifts',
        ]));
        $order->setRelation('quotation', $this->quotation(['School Uniforms']));

        $this->assertSame([
            'Computerized Embroidery', 'DTF Printing', 'Promotional Gifts',
        ], $order->workflow_categories->all());
        $this->assertSame([
            'Confirmed', 'Artwork Preparation', 'Embroidery', 'DTF Printing',
            'Heat Pressing', 'Gift Customization', 'Quality Check', 'Ready for Delivery', 'Completed',
        ], array_keys($order->stage_options));
        $this->assertArrayNotHasKey('Cutting & Stitching', $order->stage_options);
    }

    public function test_mixed_garment_and_service_job_keeps_both_processes_once(): void
    {
        $order = $this->order('Multi-Category');
        $order->setRelation('invoice', $this->invoice(['School Uniforms', 'Computerized Embroidery']));

        $this->assertSame([
            'Confirmed', 'Artwork Preparation', 'Cutting & Stitching', 'Embroidery / DTF Printing',
            'Embroidery', 'Quality Check', 'Ready for Delivery', 'Completed',
        ], array_keys($order->stage_options));
        $this->assertArrayNotHasKey('DTF Printing', $order->stage_options);
        $this->assertArrayNotHasKey('Gift Customization', $order->stage_options);
    }

    public function test_quotation_categories_are_used_when_invoice_line_categories_are_missing(): void
    {
        $order = $this->order('School Uniforms');
        $order->setRelation('invoice', $this->invoice([null, '  ']));
        $order->setRelation('quotation', $this->quotation([' DTF Printing ', 'DTF Printing']));

        $this->assertSame(['DTF Printing'], $order->workflow_categories->all());
        $this->assertArrayHasKey('Heat Pressing', $order->stage_options);
        $this->assertArrayNotHasKey('Cutting & Stitching', $order->stage_options);
    }

    #[DataProvider('uncategorizedJobs')]
    public function test_unknown_and_missing_categories_keep_the_existing_safe_workflow(?string $category): void
    {
        $order = $this->order($category);

        $this->assertSame($this->order('School Uniforms')->stage_options, $order->stage_options);
        $this->assertArrayNotHasKey('Gift Customization', $order->stage_options);
    }

    public static function uncategorizedJobs(): array
    {
        return [[null], [''], ['Multi-Category'], ['Legacy Custom Category']];
    }

    public function test_legacy_current_stage_is_retained_without_renaming_or_mutating_it(): void
    {
        $order = $this->order('Promotional Gifts', 'Embroidery / DTF Printing');
        $original = $order->getAttributes();

        $this->assertArrayNotHasKey('Embroidery / DTF Printing', $order->workflow_stage_options);
        $this->assertSame('Embroidery / DTF Printing', $order->stage_options['Embroidery / DTF Printing']);
        $this->assertSame('Embroidery / DTF Printing', $order->stage_label);
        $this->assertSame($original, $order->getAttributes());

        $order->stage = 'Gift Customization';
        $this->assertArrayNotHasKey('Embroidery / DTF Printing', $order->stage_options);
    }

    public function test_unknown_current_stage_remains_visible_but_does_not_become_a_global_option(): void
    {
        $order = $this->order('Computerized Embroidery', 'Awaiting thread approval');

        $this->assertSame('Awaiting thread approval', $order->stage_options['Awaiting thread approval']);
        $this->assertSame('Awaiting thread approval', $order->stage_label);
        $this->assertArrayNotHasKey('Awaiting thread approval', $order->workflow_stage_options);
        $this->assertArrayNotHasKey('Awaiting thread approval', ProductionOrder::STAGES);
        $this->assertArrayNotHasKey('Awaiting thread approval', $this->order('Computerized Embroidery')->stage_options);
    }

    public function test_new_stage_keys_fit_existing_storage_and_preserve_completed_meaning(): void
    {
        foreach (array_keys(ProductionOrder::STAGES) as $stage) {
            $this->assertLessThanOrEqual(40, strlen($stage));
        }

        foreach (['School Uniforms', 'Computerized Embroidery', 'DTF Printing', 'Promotional Gifts'] as $category) {
            $order = $this->order($category, 'Completed');
            $this->assertSame('Production complete', $order->stage_options['Completed']);
            $this->assertSame('Production complete', $order->stage_label);
            $this->assertArrayHasKey('Ready for Delivery', $order->stage_options);
        }
    }

    private function order(?string $category, string $stage = 'Confirmed'): ProductionOrder
    {
        return (new ProductionOrder(['category' => $category, 'stage' => $stage]))
            ->setRelation('invoice', null)->setRelation('quotation', null);
    }

    private function invoice(array $categories): SalesInvoice
    {
        return (new SalesInvoice)->setRelation('items', collect($categories)
            ->map(fn ($category) => new InvoiceItem(['category' => $category])));
    }

    private function quotation(array $categories): Quotation
    {
        return (new Quotation)->setRelation('items', collect($categories)
            ->map(fn ($category) => new QuotationItem(['category' => $category])));
    }
}
