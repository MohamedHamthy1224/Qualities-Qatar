<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\DeliveryNote;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DocumentOutputTest extends TestCase
{
    use RefreshDatabase;

    private Quotation $quotation;

    private SalesInvoice $invoice;

    private SalesInvoice $secondInvoice;

    private DeliveryNote $delivery;

    private CustomerReceipt $receipt;

    private CustomerReceipt $splitReceipt;

    protected function setUp(): void
    {
        parent::setUp();

        $customer = Customer::create([
            'customer_code' => 'DOC-CUSTOMER', 'name' => 'Document Review Customer',
            'company_name' => 'Example School', 'phone' => '+97450000000',
            'email' => 'review@example.test', 'address' => 'Client supplied address',
        ]);
        $totals = [
            'customer_id' => $customer->id, 'total_qty' => 5,
            'subtotal' => 149, 'setup_charges' => 10, 'print_charges' => 4,
            'discount' => 3, 'grand_total' => 160,
            'notes' => 'Match navy thread to the approved artwork.',
        ];
        $this->quotation = Quotation::create($totals + [
            'quotation_number' => 'QT-DOC-REVIEW', 'quotation_date' => '2026-10-15',
            'delivery_date' => '2026-10-25', 'category' => 'Multi-Category', 'status' => 'Approved',
        ]);
        $this->invoice = SalesInvoice::create($totals + [
            'invoice_number' => 'INV-DOC-REVIEW', 'invoice_date' => '2026-10-15',
            'due_date' => '2026-10-25', 'quotation_id' => $this->quotation->id,
            'paid_amount' => 45, 'balance_due' => 115, 'status' => 'Partially Paid',
        ]);
        $items = [
            [
                'product_name' => 'Navy cotton polo shirt with embroidered school crest',
                'category' => 'School Uniforms', 'input_mode' => 'garment_matrix', 'pricing_unit' => 'piece',
                'size_s' => 1, 'size_m' => 1, 'custom_size_notes' => 'Left chest crest; navy collar',
                'total_qty' => 2, 'unit_price' => 50, 'setup_charges' => 12,
                'print_charges' => 8, 'line_discount' => 5, 'line_total' => 115,
            ],
            [
                'product_name' => 'DTF artwork transfer service', 'category' => 'DTF Printing',
                'input_mode' => 'service_qty', 'pricing_unit' => 'design', 'total_qty' => 3,
                'unit_price' => 10, 'print_charges' => 6, 'line_discount' => 2, 'line_total' => 34,
                'custom_size_notes' => 'A4 full colour transfer',
            ],
        ];
        foreach ($items as $item) {
            $this->quotation->items()->create($item);
            $this->invoice->items()->create($item);
        }
        $this->secondInvoice = SalesInvoice::create([
            'invoice_number' => 'INV-DOC-SECOND', 'invoice_date' => '2026-10-15',
            'customer_id' => $customer->id, 'subtotal' => 55, 'grand_total' => 55,
            'paid_amount' => 55, 'balance_due' => 0, 'status' => 'Paid',
        ]);
        $this->receipt = CustomerReceipt::create([
            'receipt_number' => 'RCT-DOC-REVIEW', 'customer_id' => $customer->id,
            'invoice_id' => $this->invoice->id, 'amount_received' => 45,
            'payment_date' => '2026-10-15', 'payment_method' => 'Bank Transfer',
            'reference_number' => 'BANK-REVIEW', 'notes' => 'One payment for two invoices',
        ]);
        $this->splitReceipt = CustomerReceipt::create([
            'receipt_number' => 'RCT-DOC-SPLIT', 'customer_id' => $customer->id,
            'parent_receipt_id' => $this->receipt->id, 'invoice_id' => $this->secondInvoice->id,
            'amount_received' => 55, 'payment_date' => '2026-10-15', 'payment_method' => 'Bank Transfer',
        ]);
        $this->delivery = DeliveryNote::create([
            'delivery_number' => 'DN-DOC-REVIEW', 'invoice_id' => $this->invoice->id,
            'customer_id' => $customer->id, 'dispatch_date' => '2026-10-25',
            'recipient_name' => 'School Administrator', 'recipient_phone' => '+97450000000',
            'driver_name' => 'Assigned Driver', 'status' => 'Dispatched', 'driver_notes' => 'Call before arrival.',
        ]);
        foreach ($this->invoice->items as $item) {
            $this->delivery->items()->create([
                'invoice_item_id' => $item->id, 'product_name' => $item->product_name,
                'size_s' => $item->size_s, 'size_m' => $item->size_m, 'total_qty' => $item->total_qty,
            ]);
        }
    }

    public function test_sales_outputs_preserve_saved_amounts_sizes_and_distinct_item_units(): void
    {
        foreach ([
            "/pdf/quotations/{$this->quotation->id}?print=1",
            "/pdf/invoices/{$this->invoice->id}?print=1",
            "/pdf/invoices/{$this->invoice->id}?thermal=1",
        ] as $url) {
            $response = $this->get($url)->assertOk();
            foreach (['Navy cotton polo shirt', 'DTF artwork transfer service', 'S: 1, M: 1',
                'design', 'Item setup: QAR 12.00', 'Item printing: QAR 8.00',
                'Item discount: - QAR 5.00', 'QAR 149.00', 'QAR 160.00',
                'Document setup', 'Document printing', 'Document discount',
                'Match navy thread'] as $expected) {
                $response->assertSee($expected);
            }
            $response->assertDontSee('5 pieces')->assertDontSee('5 pcs');
        }
        $this->get("/pdf/invoices/{$this->invoice->id}?print=1")
            ->assertSee('QAR 45.00')->assertSee('QAR 115.00');
    }

    public function test_split_receipt_outputs_show_one_canonical_payment_and_all_allocations(): void
    {
        $response = $this->get("/pdf/receipts/{$this->splitReceipt->id}?print=1")->assertOk();
        $response->assertSee('RCT-DOC-REVIEW')->assertDontSee('RCT-DOC-SPLIT')
            ->assertSee('QAR 100.00')->assertSee('INV-DOC-REVIEW')->assertSee('INV-DOC-SECOND')
            ->assertSee('45.00')->assertSee('55.00')->assertSee('115.00')->assertSee('0.00')
            ->assertDontSee('RECEIVED & VERIFIED');
    }

    public function test_delivery_output_uses_original_units_and_real_dispatch_details(): void
    {
        $this->get("/pdf/delivery/{$this->delivery->id}?print=1")->assertOk()
            ->assertSee('design')->assertSee('piece')->assertSee('S: 1, M: 1')
            ->assertSee('School Administrator')->assertSee('Assigned Driver')
            ->assertSee('Call before arrival.')->assertDontSee('Delivery Van 04');
    }

    public function test_document_identity_and_terms_are_configurable_and_unconfirmed_defaults_are_omitted(): void
    {
        config()->set('company.name', 'Client Confirmed Company');
        config()->set('company.phone', '+97451112222');
        config()->set('company.email', 'documents@example.test');
        config()->set('company.address', 'Confirmed client address');
        config()->set('company.registration_number', 'CONFIRMED-CR');
        config()->set('company.quotation_terms', 'Client approved quotation terms.');
        config()->set('company.invoice_terms', 'Client approved invoice terms.');
        foreach ([
            "/pdf/quotations/{$this->quotation->id}?print=1",
            "/pdf/invoices/{$this->invoice->id}?print=1",
            "/pdf/invoices/{$this->invoice->id}?thermal=1",
            "/pdf/delivery/{$this->delivery->id}?print=1",
            "/pdf/receipts/{$this->receipt->id}?print=1",
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('Client Confirmed Company')
                ->assertSee('+97451112222')->assertSee('documents@example.test')
                ->assertSee('Confirmed client address')->assertSee('CONFIRMED-CR')
                ->assertDontSee('128492')->assertDontSee('50% Advance')
                ->assertDontSee('cannot be returned')->assertDontSee('30 days from');
        }
        $this->get("/pdf/quotations/{$this->quotation->id}?print=1")->assertSee('Client approved quotation terms.');
        $this->get("/pdf/invoices/{$this->invoice->id}?print=1")->assertSee('Client approved invoice terms.');
    }

    public function test_supplied_logo_is_embedded_in_documents_and_missing_logos_use_company_text(): void
    {
        $this->assertFileExists(public_path('branding/qualities-logo.png'));
        $this->get('/')->assertOk()->assertSee('/branding/qualities-logo.png', false)
            ->assertSee('alt="Qualities Uniforms &amp; Gifts Solutions"', false);
        foreach ([
            "/pdf/quotations/{$this->quotation->id}?print=1",
            "/pdf/invoices/{$this->invoice->id}?print=1",
            "/pdf/invoices/{$this->invoice->id}?thermal=1",
            "/pdf/delivery/{$this->delivery->id}?print=1",
            "/pdf/receipts/{$this->receipt->id}?print=1",
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('data:image/png;base64,', false)
                ->assertSee('alt="Qualities Uniforms &amp; Gifts Solutions"', false);
        }

        config()->set('company.logo_path', 'branding/missing-logo.png');
        $this->get('/')->assertOk()->assertSee('Qualities Uniforms &amp; Gifts Solutions', false)
            ->assertDontSee('/branding/missing-logo.png', false);
        $this->get("/pdf/invoices/{$this->invoice->id}?print=1")->assertOk()
            ->assertSee('<div class="monogram">Q</div>', false)
            ->assertSee('Qualities Uniforms &amp; Gifts Solutions', false)
            ->assertDontSee('data:image/png;base64,', false);
    }

    public function test_all_downloads_generate_valid_pdf_documents(): void
    {
        foreach ([
            'quotation' => "/pdf/quotations/{$this->quotation->id}",
            'invoice' => "/pdf/invoices/{$this->invoice->id}",
            'delivery' => "/pdf/delivery/{$this->delivery->id}",
            'receipt' => "/pdf/receipts/{$this->splitReceipt->id}",
        ] as $name => $url) {
            $response = $this->get($url)->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('content-type'));
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->saveReviewOutput($name, $response);
        }

        if (getenv('PDF_REVIEW_OUTPUT') === '1') {
            $thermalHtml = $this->get("/pdf/invoices/{$this->invoice->id}?thermal=1")->assertOk()->getContent();
            $thermal = Pdf::loadHTML($thermalHtml)
                ->setPaper([0, 0, 226.77, 1000])->setOption('defaultMediaType', 'print');
            $this->saveReviewOutput('invoice-thermal', TestResponse::fromBaseResponse(response($thermal->output())));
        }
    }

    public function test_long_invoice_download_handles_many_detailed_lines(): void
    {
        $template = $this->invoice->items->first()->only([
            'product_name', 'category', 'input_mode', 'pricing_unit', 'size_s', 'size_m',
            'custom_size_notes', 'total_qty', 'unit_price', 'setup_charges', 'print_charges',
            'line_discount', 'line_total',
        ]);
        for ($line = 3; $line <= 16; $line++) {
            $this->invoice->items()->create(array_replace($template, [
                'product_name' => "Line {$line}: Corporate cotton polo with customized embroidered name and department crest",
            ]));
        }
        $this->invoice->update([
            'total_qty' => 33, 'subtotal' => 1759, 'grand_total' => 1770,
            'balance_due' => 1725,
        ]);
        $response = $this->get("/pdf/invoices/{$this->invoice->id}")->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->saveReviewOutput('invoice-multipage', $response);
    }

    private function saveReviewOutput(string $name, TestResponse $response): void
    {
        // Opt-in visual QA uses isolated test data and never creates live orders.
        if (getenv('PDF_REVIEW_OUTPUT') === '1') {
            $directory = storage_path('framework/testing/pdf-review');
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            file_put_contents($directory.'/'.$name.'.pdf', $response->getContent());
        }
    }
}
