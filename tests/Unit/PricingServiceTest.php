<?php

namespace Tests\Unit;

use App\Services\PricingService;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    protected PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new PricingService();
    }

    /**
     * Test 1: Garment matrix quantity sum and base amount.
     */
    public function test_garment_matrix_calculates_sum_of_sizes_correctly(): void
    {
        $item = [
            'product_name' => 'Custom Pique Polo Shirt',
            'category' => 'School Uniforms',
            'input_mode' => 'garment_matrix',
            'pricing_unit' => 'piece',
            'size_xs' => 10,
            'size_s' => 20,
            'size_m' => 30,
            'size_l' => 25,
            'size_xl' => 15,
            'size_2xl' => 0,
            'size_3xl' => 0,
            'size_custom' => 0,
            'unit_price' => 65.00,
            'line_discount' => 0,
            'setup_charges' => 0,
            'print_charges' => 0,
        ];

        $result = $this->pricingService->calculateLineItem($item);

        $this->assertEquals(100, $result['total_qty']);
        $this->assertEquals(6500.00, $result['base_amount']);
        $this->assertEquals(6500.00, $result['line_total']);
    }

    /**
     * Test 2: Standard and service quantity mode uses total_qty directly and ignores size matrix.
     */
    public function test_standard_and_service_modes_use_direct_quantity(): void
    {
        $serviceItem = [
            'product_name' => 'Logo Embroidery',
            'category' => 'Computerized Embroidery',
            'input_mode' => 'service_qty',
            'pricing_unit' => 'service',
            'total_qty' => 150,
            'unit_price' => 5.00,
            'line_discount' => 0,
            'setup_charges' => 50.00,
            'print_charges' => 0,
        ];

        $result = $this->pricingService->calculateLineItem($serviceItem);

        $this->assertEquals(150, $result['total_qty']);
        $this->assertEquals(750.00, $result['base_amount']);
        $this->assertEquals(800.00, $result['line_total']); // 750 + 50 setup
        $this->assertEquals(0, $result['size_xs']);
    }

    /**
     * Test 3: Charges and discounts are applied authoritatively once and line total cannot be negative.
     */
    public function test_charges_and_discount_rules(): void
    {
        $item = [
            'product_name' => 'Printed Design',
            'category' => 'DTF Printing',
            'input_mode' => 'service_qty',
            'total_qty' => 10,
            'unit_price' => 8.00,
            'setup_charges' => 40.00,
            'print_charges' => 20.00,
            'line_discount' => 200.00, // Exceeds base amount (80.00)
        ];

        $result = $this->pricingService->calculateLineItem($item);

        // Line discount capped to base amount 80.00
        $this->assertEquals(80.00, $result['line_discount']);
        // Line total: 80 + 40 + 20 - 80 = 60.00
        $this->assertEquals(60.00, $result['line_total']);
    }

    /**
     * Test 4: Section 9 Master Prompt Multi-Category Quotation calculation:
     * 1. School Uniforms - Polo: 100 @ 65 = 6500
     * 2. School Uniforms - Trousers: 50 @ 95 = 4750
     * 3. Computerized Embroidery - Logo: 150 @ 5 = 750
     * 4. DTF Printing - Design: 100 @ 8 = 800
     * 5. Promotional Gifts - Gift Set: 20 @ 125 = 2500
     * Subtotal: QAR 15,300
     */
    public function test_section_9_multi_category_quotation_exact_match(): void
    {
        $items = [
            [
                'product_name' => 'Custom Pique Polo Shirt',
                'category' => 'School Uniforms',
                'input_mode' => 'garment_matrix',
                'size_xs' => 10, 'size_s' => 20, 'size_m' => 30, 'size_l' => 25, 'size_xl' => 15,
                'unit_price' => 65.00,
            ],
            [
                'product_name' => 'School Trousers',
                'category' => 'School Uniforms',
                'input_mode' => 'garment_matrix',
                'size_s' => 10, 'size_m' => 20, 'size_l' => 15, 'size_xl' => 5,
                'unit_price' => 95.00,
            ],
            [
                'product_name' => 'Logo Embroidery',
                'category' => 'Computerized Embroidery',
                'input_mode' => 'service_qty',
                'pricing_unit' => 'service',
                'total_qty' => 150,
                'unit_price' => 5.00,
            ],
            [
                'product_name' => 'Printed Design',
                'category' => 'DTF Printing',
                'input_mode' => 'service_qty',
                'pricing_unit' => 'design',
                'total_qty' => 100,
                'unit_price' => 8.00,
            ],
            [
                'product_name' => 'Executive Gift Set',
                'category' => 'Promotional Gifts',
                'input_mode' => 'standard_qty',
                'pricing_unit' => 'set',
                'total_qty' => 20,
                'unit_price' => 125.00,
            ],
        ];

        $totals = $this->pricingService->calculateQuotationTotals($items);

        $this->assertEquals(420, $totals['total_qty']); // 100 + 50 + 150 + 100 + 20
        $this->assertEquals(15300.00, $totals['subtotal']);
        $this->assertEquals(15300.00, $totals['grand_total']);
        $this->assertEquals('Multi-Category', $totals['primary_category']);
        $this->assertCount(4, $totals['categories']);
    }

    /**
     * Test 5: Proposal discount applies correctly and does not result in negative grand total.
     */
    public function test_overall_discount_and_setup_charges(): void
    {
        $items = [
            [
                'product_name' => 'Custom Item',
                'category' => 'Corporate Uniforms',
                'input_mode' => 'standard_qty',
                'total_qty' => 10,
                'unit_price' => 100.00, // 1000.00
            ],
        ];

        // Overall discount 300, overall setup 150
        $totals = $this->pricingService->calculateQuotationTotals(
            rawItems: $items,
            overallDiscount: 300.00,
            overallSetup: 150.00,
            overallPrint: 50.00
        );

        $this->assertEquals(1000.00, $totals['subtotal']);
        $this->assertEquals(150.00, $totals['setup_charges']);
        $this->assertEquals(50.00, $totals['print_charges']);
        $this->assertEquals(300.00, $totals['discount']);
        // 1000 + 150 + 50 - 300 = 900.00
        $this->assertEquals(900.00, $totals['grand_total']);
        $this->assertEquals('Corporate Uniforms', $totals['primary_category']);
    }
}
