<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterPaginationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = Customer::create([
            'customer_code' => 'CUST-PAGINATION',
            'name' => 'Pagination Customer',
        ]);
    }

    public function test_quotation_pages_retain_filters_and_leave_register_totals_global(): void
    {
        foreach (range(1, 16) as $number) {
            $this->quotation(sprintf('QT-MATCH-%02d', $number));
        }
        $this->quotation('QT-APPROVED', ['status' => 'Approved', 'category' => 'Promotional Gifts']);
        $this->quotation('QT-INVOICED', ['status' => 'Invoiced']);

        $filters = ['search' => 'QT-MATCH', 'status' => 'Sent', 'category' => 'School Uniforms'];
        $response = $this->get('/quotations?'.http_build_query($filters))->assertOk()
            ->assertViewHas('totalQuotesCount', 18)->assertViewHas('approvedQuotesCount', 2);
        $paginator = $response->viewData('quotations');
        $this->assertSame(15, $paginator->count());
        $this->assertSame(16, $paginator->total());
        $this->assertSame('QT-MATCH-16', $paginator->first()->quotation_number);
        $this->assertLinkParameters($paginator->nextPageUrl(), $filters + ['page' => '2']);

        $this->get('/quotations?'.http_build_query($filters + ['page' => 2]))->assertOk()
            ->assertViewHas('quotations', fn ($quotes) => $quotes->count() === 1
                && $quotes->first()->quotation_number === 'QT-MATCH-01');
        $sized = $this->get('/quotations?'.http_build_query($filters + ['per_page' => 10, 'page' => 2]))
            ->assertOk()->assertViewHas('totalQuotesCount', 18)->assertViewHas('approvedQuotesCount', 2)
            ->viewData('quotations');
        $this->assertSame(10, $sized->perPage());
        $this->assertSame(6, $sized->count());
        $this->assertLinkParameters($sized->previousPageUrl(), $filters + ['per_page' => '10', 'page' => '1']);
    }

    public function test_invoice_pages_retain_filters_and_leave_financial_summaries_global(): void
    {
        foreach (range(1, 16) as $number) {
            $this->invoice(sprintf('INV-MATCH-%02d', $number));
        }
        $this->invoice('INV-PAID', ['status' => 'Paid', 'grand_total' => 99, 'balance_due' => 0]);

        $filters = ['search' => 'INV-MATCH', 'status' => 'Unpaid'];
        $response = $this->get('/invoices?'.http_build_query($filters))->assertOk()
            ->assertViewHas('totalInvoiced', fn ($total) => (float) $total === 259.0)
            ->assertViewHas('totalOutstanding', fn ($total) => (float) $total === 160.0)
            ->assertViewHas('unpaidCount', 16);
        $paginator = $response->viewData('invoices');
        $this->assertSame(15, $paginator->count());
        $this->assertSame(16, $paginator->total());
        $this->assertSame('INV-MATCH-16', $paginator->first()->invoice_number);
        $this->assertLinkParameters($paginator->nextPageUrl(), $filters + ['page' => '2']);

        $this->get('/invoices?'.http_build_query($filters + ['page' => 2]))->assertOk()
            ->assertViewHas('invoices', fn ($invoices) => $invoices->count() === 1
                && $invoices->first()->invoice_number === 'INV-MATCH-01');
        $sized = $this->get('/invoices?'.http_build_query($filters + ['per_page' => 10, 'page' => 2]))
            ->assertOk()->assertViewHas('totalOutstanding', fn ($total) => (float) $total === 160.0)
            ->assertViewHas('unpaidCount', 16)->viewData('invoices');
        $this->assertSame(10, $sized->perPage());
        $this->assertSame(6, $sized->count());
        $this->assertLinkParameters($sized->previousPageUrl(), $filters + ['per_page' => '10', 'page' => '1']);
    }

    public function test_delivery_pages_retain_filters_and_leave_status_summaries_global(): void
    {
        $invoice = $this->invoice('INV-DELIVERY-PAGINATION');
        foreach (range(1, 16) as $number) {
            $this->delivery($invoice, sprintf('DN-MATCH-%02d', $number));
        }
        $this->delivery($invoice, 'DN-DELIVERED', ['status' => 'Delivered']);
        $this->delivery($invoice, 'DN-DISPATCHED', ['status' => 'Dispatched']);

        $filters = ['search' => 'DN-MATCH', 'status' => 'Pending'];
        $response = $this->get('/delivery?'.http_build_query($filters))->assertOk()
            ->assertViewHas('pendingCount', 16)->assertViewHas('dispatchedCount', 1)
            ->assertViewHas('deliveredCount', 1);
        $paginator = $response->viewData('deliveryNotes');
        $this->assertSame(15, $paginator->count());
        $this->assertSame(16, $paginator->total());
        $this->assertSame('DN-MATCH-16', $paginator->first()->delivery_number);
        $this->assertLinkParameters($paginator->nextPageUrl(), $filters + ['page' => '2']);

        $this->get('/delivery?'.http_build_query($filters + ['page' => 2]))->assertOk()
            ->assertViewHas('deliveryNotes', fn ($notes) => $notes->count() === 1
                && $notes->first()->delivery_number === 'DN-MATCH-01');
        $sized = $this->get('/delivery?'.http_build_query($filters + ['per_page' => 10, 'page' => 2]))
            ->assertOk()->assertViewHas('pendingCount', 16)->assertViewHas('deliveredCount', 1)
            ->viewData('deliveryNotes');
        $this->assertSame(10, $sized->perPage());
        $this->assertSame(6, $sized->count());
        $this->assertLinkParameters($sized->previousPageUrl(), $filters + ['per_page' => '10', 'page' => '1']);
    }

    public function test_registers_reject_unbounded_page_sizes_and_invalid_page_numbers(): void
    {
        foreach (['/quotations', '/invoices', '/delivery'] as $path) {
            $this->getJson($path.'?per_page=100000')->assertUnprocessable()->assertJsonValidationErrors('per_page');
            $this->getJson($path.'?per_page[]=10')->assertUnprocessable()->assertJsonValidationErrors('per_page');
            $this->getJson($path.'?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
            $this->getJson($path.'?page=two')->assertUnprocessable()->assertJsonValidationErrors('page');
        }
    }

    private function assertLinkParameters(?string $url, array $expected): void
    {
        $this->assertNotNull($url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parameters);
        foreach ($expected as $key => $value) {
            $this->assertSame((string) $value, $parameters[$key] ?? null, "Pagination must retain {$key}.");
        }
    }

    private function quotation(string $number, array $attributes = []): Quotation
    {
        return Quotation::create(array_merge([
            'quotation_number' => $number, 'customer_id' => $this->customer->id,
            'quotation_date' => '2026-10-17', 'category' => 'School Uniforms', 'status' => 'Sent',
        ], $attributes));
    }

    private function invoice(string $number, array $attributes = []): SalesInvoice
    {
        return SalesInvoice::create(array_merge([
            'invoice_number' => $number, 'customer_id' => $this->customer->id,
            'invoice_date' => '2026-10-17', 'status' => 'Unpaid', 'grand_total' => 10, 'balance_due' => 10,
        ], $attributes));
    }

    private function delivery(SalesInvoice $invoice, string $number, array $attributes = []): DeliveryNote
    {
        return DeliveryNote::create(array_merge([
            'delivery_number' => $number, 'customer_id' => $this->customer->id, 'invoice_id' => $invoice->id,
            'dispatch_date' => '2026-10-17', 'recipient_name' => 'School stores', 'status' => 'Pending',
        ], $attributes));
    }
}
