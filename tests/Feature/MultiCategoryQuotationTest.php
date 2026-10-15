<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiCategoryQuotationTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected array $seededProducts = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test Qatar customer
        $this->customer = Customer::create([
            'customer_code' => 'CUST-TEST-001',
            'name' => 'Al Noor International School',
            'company_name' => 'Al Noor Education Qatar',
            'phone' => '+974 5511 2233',
            'email' => 'admin@alnoorschool.qa',
            'address' => 'Al Waab St, Zone 55, Doha, Qatar',
            'balance' => 0.00,
        ]);

        // Seed products across distinct categories
        $this->seededProducts['polo'] = Product::create([
            'sku' => 'UNI-POLO-SCH',
            'name' => 'Custom Pique Polo Shirt',
            'category' => 'School Uniforms',
            'unit_price' => 65.00,
            'cost_price' => 28.00,
            'pricing_unit' => 'piece',
            'input_mode' => 'garment_matrix',
            'size_matrix_required' => true,
            'is_active' => true,
        ]);

        $this->seededProducts['trousers'] = Product::create([
            'sku' => 'UNI-TROU-SCH',
            'name' => 'School Trousers',
            'category' => 'School Uniforms',
            'unit_price' => 95.00,
            'cost_price' => 40.00,
            'pricing_unit' => 'piece',
            'input_mode' => 'garment_matrix',
            'size_matrix_required' => true,
            'is_active' => true,
        ]);

        $this->seededProducts['corp_shirt'] = Product::create([
            'sku' => 'UNI-OXFD-05',
            'name' => 'Oxford Button-Down Corporate Shirt',
            'category' => 'Corporate Uniforms',
            'unit_price' => 85.00,
            'cost_price' => 35.00,
            'pricing_unit' => 'piece',
            'input_mode' => 'garment_matrix',
            'size_matrix_required' => true,
            'is_active' => true,
        ]);

        $this->seededProducts['embroidery'] = Product::create([
            'sku' => 'SRV-EMB-01',
            'name' => 'Logo Embroidery',
            'category' => 'Computerized Embroidery',
            'unit_price' => 5.00,
            'cost_price' => 1.50,
            'pricing_unit' => 'service',
            'input_mode' => 'service_qty',
            'size_matrix_required' => false,
            'is_active' => true,
        ]);

        $this->seededProducts['dtf'] = Product::create([
            'sku' => 'SRV-DTF-01',
            'name' => 'Printed Design',
            'category' => 'DTF Printing',
            'unit_price' => 8.00,
            'cost_price' => 2.50,
            'pricing_unit' => 'design',
            'input_mode' => 'service_qty',
            'size_matrix_required' => false,
            'is_active' => true,
        ]);

        $this->seededProducts['gift'] = Product::create([
            'sku' => 'GIFT-VIP-09',
            'name' => 'Executive Gift Set',
            'category' => 'Promotional Gifts',
            'unit_price' => 125.00,
            'cost_price' => 50.00,
            'pricing_unit' => 'set',
            'input_mode' => 'standard_qty',
            'size_matrix_required' => false,
            'is_active' => true,
        ]);

        // Inactive product
        Product::create([
            'sku' => 'DISCONTINUED-01',
            'name' => 'Discontinued Item',
            'category' => 'School Uniforms',
            'unit_price' => 10.00,
            'is_active' => false,
        ]);
    }

    /**
     * Test 1 & 2 & 3: Category filtering via api/products.
     */
    public function test_product_api_filters_by_category_and_excludes_inactive(): void
    {
        // 1. School Uniforms filter
        $response = $this->getJson('/api/products?category=School Uniforms');
        $response->assertStatus(200);
        $products = $response->json('products');
        $this->assertCount(2, $products); // Only active School Uniforms products
        foreach ($products as $p) {
            $this->assertEquals('School Uniforms', $p['category']);
        }

        // 2. Corporate Uniforms filter
        $responseCorp = $this->getJson('/api/products?category=Corporate Uniforms');
        $responseCorp->assertStatus(200);
        $corpProducts = $responseCorp->json('products');
        $this->assertCount(1, $corpProducts);
        $this->assertEquals('Corporate Uniforms', $corpProducts[0]['category']);

        // 3. All Categories filter (should return 6 active products, excluding 1 inactive)
        $responseAll = $this->getJson('/api/products?category=all');
        $responseAll->assertStatus(200);
        $this->assertCount(6, $responseAll->json('products'));
    }

    /**
     * Test 4, 5, 6, 7, 8, 9, 10: Multi-Category Quotation creation and authoritative financial calculation.
     */
    public function test_multi_category_quotation_creation_and_authoritative_calculation(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'quotation_number' => 'QT-2026-009',
            'category' => 'Multi-Category',
            'quotation_date' => '2026-10-15',
            'delivery_date' => '2026-10-22',
            'notes' => 'Multi-category test order for Al Noor International School',
            'items' => [
                // 1. School Uniforms - Polo: 100 @ 65 = 6500
                [
                    'product_id' => $this->seededProducts['polo']->id,
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
                ],
                // 2. School Uniforms - Trousers: 50 @ 95 = 4750
                [
                    'product_id' => $this->seededProducts['trousers']->id,
                    'product_name' => 'School Trousers',
                    'category' => 'School Uniforms',
                    'input_mode' => 'garment_matrix',
                    'pricing_unit' => 'piece',
                    'size_xs' => 0,
                    'size_s' => 10,
                    'size_m' => 20,
                    'size_l' => 15,
                    'size_xl' => 5,
                    'size_2xl' => 0,
                    'size_3xl' => 0,
                    'size_custom' => 0,
                    'unit_price' => 95.00,
                ],
                // 3. Computerized Embroidery - Logo: 150 @ 5 = 750
                [
                    'product_id' => $this->seededProducts['embroidery']->id,
                    'product_name' => 'Logo Embroidery',
                    'category' => 'Computerized Embroidery',
                    'input_mode' => 'service_qty',
                    'pricing_unit' => 'service',
                    'total_qty' => 150,
                    'unit_price' => 5.00,
                ],
                // 4. DTF Printing - Design: 100 @ 8 = 800
                [
                    'product_id' => $this->seededProducts['dtf']->id,
                    'product_name' => 'Printed Design',
                    'category' => 'DTF Printing',
                    'input_mode' => 'service_qty',
                    'pricing_unit' => 'design',
                    'total_qty' => 100,
                    'unit_price' => 8.00,
                ],
                // 5. Promotional Gifts - Gift Set: 20 @ 125 = 2500
                [
                    'product_id' => $this->seededProducts['gift']->id,
                    'product_name' => 'Executive Gift Set',
                    'category' => 'Promotional Gifts',
                    'input_mode' => 'standard_qty',
                    'pricing_unit' => 'set',
                    'total_qty' => 20,
                    'unit_price' => 125.00,
                ],
            ],
            'discount' => 0.00,
            'setup_charges' => 0.00,
            'print_charges' => 0.00,
        ];

        $response = $this->post('/quotations', $payload);
        $response->assertRedirect();

        // Verify Quotation created in database
        $quotation = Quotation::where('quotation_number', 'QT-2026-009')->first();
        $this->assertNotNull($quotation);
        $this->assertEquals('Multi-Category', $quotation->category);
        $this->assertEquals(420, $quotation->total_qty);
        $this->assertEquals('15300.00', (string)$quotation->subtotal);
        $this->assertEquals('15300.00', (string)$quotation->grand_total);

        // Verify all 5 line items saved with their own categories and modes
        $items = $quotation->items;
        $this->assertCount(5, $items);

        $categories = $items->pluck('category')->toArray();
        $this->assertContains('School Uniforms', $categories);
        $this->assertContains('Computerized Embroidery', $categories);
        $this->assertContains('DTF Printing', $categories);
        $this->assertContains('Promotional Gifts', $categories);

        // Verify garment item size breakdown
        $poloItem = $items->where('product_name', 'Custom Pique Polo Shirt')->first();
        $this->assertEquals(100, $poloItem->total_qty);
        $this->assertEquals(10, $poloItem->size_xs);
        $this->assertEquals(20, $poloItem->size_s);
        $this->assertEquals(30, $poloItem->size_m);
        $this->assertEquals(25, $poloItem->size_l);
        $this->assertEquals(15, $poloItem->size_xl);
        $this->assertEquals('6500.00', (string)$poloItem->line_total);

        // Verify service item
        $embItem = $items->where('product_name', 'Logo Embroidery')->first();
        $this->assertEquals('service_qty', $embItem->input_mode);
        $this->assertEquals(150, $embItem->total_qty);
        $this->assertEquals('750.00', (string)$embItem->line_total);

        // Verify gift item
        $giftItem = $items->where('product_name', 'Executive Gift Set')->first();
        $this->assertEquals('standard_qty', $giftItem->input_mode);
        $this->assertEquals(20, $giftItem->total_qty);
        $this->assertEquals('2500.00', (string)$giftItem->line_total);
    }

    /**
     * Test 13: Custom unlisted item can be added without inserting permanent catalog records.
     */
    public function test_custom_item_saved_without_polluting_product_catalog(): void
    {
        $initialProductCount = Product::count();

        $payload = [
            'customer_id' => $this->customer->id,
            'quotation_number' => 'QT-CUSTOM-001',
            'quotation_date' => '2026-10-15',
            'items' => [
                [
                    'product_id' => null, // Unlisted custom item
                    'product_name' => 'Custom Hand-Embroidered Velvet Podium Drape',
                    'category' => 'Computerized Embroidery',
                    'input_mode' => 'custom',
                    'pricing_unit' => 'piece',
                    'total_qty' => 2,
                    'unit_price' => 450.00,
                    'setup_charges' => 100.00,
                    'custom_size_notes' => '2.5m x 1m with gold bullion fringe',
                ],
            ],
        ];

        $response = $this->post('/quotations', $payload);
        $response->assertRedirect();

        // Quotation created
        $quote = Quotation::where('quotation_number', 'QT-CUSTOM-001')->first();
        $this->assertNotNull($quote);
        $this->assertEquals(1000.00, (float)$quote->grand_total); // (2 * 450) + 100 = 1000.00

        // Catalog count unchanged
        $this->assertEquals($initialProductCount, Product::count());
    }

    /**
     * Test 16 & 17: 1-Click invoice conversion copies all multi-category line items accurately
     * and prevents duplicate conversions.
     */
    public function test_one_click_invoice_conversion_preserves_all_line_items_and_prevents_duplicates(): void
    {
        // Create mixed quotation
        $quotation = Quotation::create([
            'quotation_number' => 'QT-CONVERT-01',
            'customer_id' => $this->customer->id,
            'category' => 'Multi-Category',
            'quotation_date' => '2026-10-15',
            'delivery_date' => '2026-10-25',
            'total_qty' => 170,
            'subtotal' => 7250.00,
            'discount' => 0.00,
            'setup_charges' => 0.00,
            'print_charges' => 0.00,
            'grand_total' => 7250.00,
            'status' => 'Approved',
        ]);

        // Item 1: School Uniforms
        $quotation->items()->create([
            'product_id' => $this->seededProducts['polo']->id,
            'product_name' => 'Custom Pique Polo Shirt',
            'category' => 'School Uniforms',
            'input_mode' => 'garment_matrix',
            'pricing_unit' => 'piece',
            'size_xs' => 10,
            'size_s' => 20,
            'size_m' => 40,
            'total_qty' => 70,
            'unit_price' => 65.00,
            'line_total' => 4550.00,
        ]);

        // Item 2: Promotional Gifts
        $quotation->items()->create([
            'product_id' => $this->seededProducts['dtf']->id,
            'product_name' => 'Printed Design',
            'category' => 'DTF Printing',
            'input_mode' => 'service_qty',
            'pricing_unit' => 'design',
            'total_qty' => 100,
            'unit_price' => 27.00,
            'line_total' => 2700.00,
        ]);

        // First Conversion Request
        $response = $this->post("/quotations/{$quotation->id}/convert");
        $response->assertRedirect();

        // Quotation status updated to Invoiced
        $this->assertEquals('Invoiced', $quotation->fresh()->status);

        // Sales Invoice created with identical items
        $invoice = SalesInvoice::where('quotation_id', $quotation->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('7250.00', (string)$invoice->grand_total);
        $this->assertEquals(170, $invoice->total_qty);

        $invoiceItems = $invoice->items;
        $this->assertCount(2, $invoiceItems);

        $firstInvoiceItem = $invoiceItems->where('product_name', 'Custom Pique Polo Shirt')->first();
        $this->assertEquals('School Uniforms', $firstInvoiceItem->category);
        $this->assertEquals(10, $firstInvoiceItem->size_xs);
        $this->assertEquals(20, $firstInvoiceItem->size_s);
        $this->assertEquals(40, $firstInvoiceItem->size_m);
        $this->assertEquals(70, $firstInvoiceItem->total_qty);

        $secondInvoiceItem = $invoiceItems->where('product_name', 'Printed Design')->first();
        $this->assertEquals('DTF Printing', $secondInvoiceItem->category);
        $this->assertEquals('service_qty', $secondInvoiceItem->input_mode);
        $this->assertEquals(100, $secondInvoiceItem->total_qty);

        // Repeated Conversion Attempt (Must prevent duplicate creation)
        $invoiceCountBefore = SalesInvoice::count();
        $repeatResponse = $this->post("/quotations/{$quotation->id}/convert");
        $repeatResponse->assertRedirect();
        $this->assertEquals($invoiceCountBefore, SalesInvoice::count());
    }

    /**
     * Test 15: Mixed-category quotation views and PDF routes render successfully.
     */
    public function test_mixed_category_quotation_screens_render_successfully(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'QT-DOC-01',
            'customer_id' => $this->customer->id,
            'category' => 'Multi-Category',
            'quotation_date' => '2026-10-15',
            'total_qty' => 120,
            'subtotal' => 5000.00,
            'grand_total' => 5000.00,
            'status' => 'Draft',
        ]);

        $quotation->items()->create([
            'product_name' => 'Custom Pique Polo Shirt',
            'category' => 'School Uniforms',
            'input_mode' => 'garment_matrix',
            'total_qty' => 100,
            'unit_price' => 45.00,
            'line_total' => 4500.00,
        ]);

        $quotation->items()->create([
            'product_name' => 'Executive Gift Set',
            'category' => 'Promotional Gifts',
            'input_mode' => 'standard_qty',
            'total_qty' => 20,
            'unit_price' => 25.00,
            'line_total' => 500.00,
        ]);

        // Detail view
        $responseShow = $this->get("/quotations/{$quotation->id}");
        $responseShow->assertStatus(200);
        $responseShow->assertSee('Multi-Category');
        $responseShow->assertSee('Custom Pique Polo Shirt');
        $responseShow->assertSee('Executive Gift Set');

        // Print preview view
        $responsePrint = $this->get("/pdf/quotations/{$quotation->id}?print=1");
        $responsePrint->assertStatus(200);
        $responsePrint->assertSee('Custom Pique Polo Shirt');
        $responsePrint->assertSee('Executive Gift Set');

        // DomPDF generation view
        $responsePdf = $this->get("/pdf/quotations/{$quotation->id}");
        $responsePdf->assertStatus(200);
        $this->assertEquals('application/pdf', $responsePdf->headers->get('content-type'));
    }

    /**
     * Test 12: Invalid or manipulated client totals/prices are authoritatively recalculated.
     */
    public function test_manipulated_client_totals_are_recalculated_authoritatively(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'quotation_number' => 'QT-TAMPER-001',
            'quotation_date' => '2026-10-15',
            // Manipulated totals sent from rogue client
            'subtotal' => 1.00,
            'grand_total' => 1.00,
            'total_qty' => 1,
            'items' => [
                [
                    'product_name' => 'Custom Pique Polo Shirt',
                    'category' => 'School Uniforms',
                    'input_mode' => 'garment_matrix',
                    'size_xs' => 10,
                    'size_s' => 10,
                    'total_qty' => 1, // Manipulated
                    'unit_price' => 50.00,
                    'line_total' => 1.00, // Manipulated
                ],
            ],
        ];

        $response = $this->post('/quotations', $payload);
        $response->assertRedirect();

        $quote = Quotation::where('quotation_number', 'QT-TAMPER-001')->first();
        $this->assertNotNull($quote);
        // Correct authoritative calculation: (10 + 10) = 20 qty * 50.00 = 1000.00
        $this->assertEquals(20, $quote->total_qty);
        $this->assertEquals(1000.00, (float)$quote->subtotal);
        $this->assertEquals(1000.00, (float)$quote->grand_total);
    }

    /**
     * Test 14: Draft quotation persists and restores all line-item data.
     */
    public function test_draft_quotation_persists_and_restores_all_line_item_data(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'quotation_number' => 'QT-DRAFT-001',
            'quotation_date' => '2026-10-15',
            'action' => 'draft',
            'notes' => 'Draft proposal waiting on school board review',
            'items' => [
                [
                    'product_name' => 'Student School Blazer with Crest',
                    'category' => 'School Uniforms',
                    'input_mode' => 'garment_matrix',
                    'size_m' => 15,
                    'size_l' => 15,
                    'size_custom' => 2,
                    'custom_size_notes' => 'Extra long sleeve length',
                    'unit_price' => 175.00,
                ],
            ],
        ];

        $response = $this->post('/quotations', $payload);
        $response->assertRedirect();

        $quote = Quotation::where('quotation_number', 'QT-DRAFT-001')->first();
        $this->assertNotNull($quote);
        $this->assertEquals('Draft', $quote->status);
        $this->assertEquals(32, $quote->total_qty);
        $this->assertEquals(5600.00, (float)$quote->grand_total); // 32 * 175 = 5600.00

        $item = $quote->items->first();
        $this->assertEquals(2, $item->size_custom);
        $this->assertEquals('Extra long sleeve length', $item->custom_size_notes);
    }

    /**
     * Test 19: Historical quotations remain readable and unchanged when catalog products change.
     */
    public function test_historical_quotations_remain_intact_after_catalog_product_price_changes(): void
    {
        // 1. Create quotation with current catalog product at 65.00
        $polo = $this->seededProducts['polo'];
        $quotation = Quotation::create([
            'quotation_number' => 'QT-HIST-001',
            'customer_id' => $this->customer->id,
            'category' => 'School Uniforms',
            'quotation_date' => '2026-01-10',
            'total_qty' => 10,
            'subtotal' => 650.00,
            'grand_total' => 650.00,
            'status' => 'Approved',
        ]);
        $quotation->items()->create([
            'product_id' => $polo->id,
            'product_name' => $polo->name,
            'category' => $polo->category,
            'input_mode' => 'garment_matrix',
            'total_qty' => 10,
            'unit_price' => 65.00,
            'line_total' => 650.00,
        ]);

        // 2. Later, catalog product price increases to 90.00 and is renamed
        $polo->update([
            'name' => 'Custom Pique Polo Shirt (2027 Premium Edition)',
            'unit_price' => 90.00,
            'is_active' => false, // Deactivated
        ]);

        // 3. Historical quotation must still show historical name and 65.00 price
        $historicalItem = $quotation->fresh()->items->first();
        $this->assertEquals('Custom Pique Polo Shirt', $historicalItem->product_name);
        $this->assertEquals(65.00, (float)$historicalItem->unit_price);
        $this->assertEquals(650.00, (float)$historicalItem->line_total);
    }

    /**
     * Test 20: Validation failure rolls back database cleanly.
     */
    public function test_validation_failure_rolls_back_database_cleanly(): void
    {
        $quotationCountBefore = Quotation::count();
        $itemCountBefore = QuotationItem::count();

        // Send payload missing required quotation_number
        $response = $this->post('/quotations', [
            'customer_id' => $this->customer->id,
            // quotation_number omitted
            'quotation_date' => '2026-10-15',
            'items' => [
                [
                    'product_name' => 'Test Item',
                    'total_qty' => 10,
                    'unit_price' => 50.00,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('quotation_number');
        $this->assertEquals($quotationCountBefore, Quotation::count());
        $this->assertEquals($itemCountBefore, QuotationItem::count());
    }
}
