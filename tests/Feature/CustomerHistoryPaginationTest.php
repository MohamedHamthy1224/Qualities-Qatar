<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerHistoryPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_history_is_paginated_without_reducing_full_history_counts_or_balance(): void
    {
        $customer = $this->customerWithHistory();

        $response = $this->get(route('customers.show', $customer));
        $response->assertOk()->assertSee('Sales Invoices (16)')->assertSee('Quotations (16)')
            ->assertSee('Delivery Notes (16)')->assertSee('Payment Receipts (16)')
            ->assertSee('9,876.54');

        foreach (['quotations', 'invoices', 'deliveryNotes', 'receipts'] as $name) {
            $response->assertViewHas($name, fn ($paginator) => $paginator->total() === 16
                && $paginator->count() === 15 && $paginator->currentPage() === 1);
        }

        $response->assertViewHas('customer', fn ($profile) => $profile->invoices_count === 16
            && $profile->quotations_count === 16 && $profile->delivery_notes_count === 16
            && $profile->receipts_count === 16 && ! $profile->relationLoaded('invoices')
            && ! $profile->relationLoaded('quotations'));
        $response->assertViewHas('deliveryNotes', fn ($notes) => $notes->every(fn ($note) => $note->relationLoaded('invoice')));
        $this->assertSame('9876.54', $customer->fresh()->balance);
        $this->assertSame(16, $customer->invoices()->count());
        $this->assertEquals(16136, $customer->invoices()->sum('grand_total'));
    }

    public function test_each_customer_history_table_uses_its_own_page_and_keeps_other_tables_on_their_current_page(): void
    {
        $customer = $this->customerWithHistory();
        $response = $this->get(route('customers.show', $customer).'?quotes_page=2');

        $response->assertOk()
            ->assertViewHas('quotations', fn ($quotes) => $quotes->currentPage() === 2
                && $quotes->count() === 1 && $quotes->first()->quotation_number === 'QT-HISTORY-01')
            ->assertViewHas('invoices', fn ($invoices) => $invoices->currentPage() === 1
                && $invoices->count() === 15 && $invoices->first()->invoice_number === 'INV-HISTORY-16')
            ->assertViewHas('deliveryNotes', fn ($notes) => $notes->currentPage() === 1 && $notes->count() === 15)
            ->assertViewHas('receipts', fn ($receipts) => $receipts->currentPage() === 1 && $receipts->count() === 15)
            ->assertSee('id="customer-quotations"', false)
            ->assertSee('activateHistoryAnchor()', false);
    }

    public function test_customer_history_links_preserve_independent_page_parameters_and_target_the_correct_tab(): void
    {
        $customer = $this->customerWithHistory();
        $pages = ['quotes_page' => 2, 'invoices_page' => 2, 'delivery_page' => 2, 'receipts_page' => 2];
        $response = $this->get(route('customers.show', $customer).'?'.http_build_query($pages));
        $response->assertOk();

        foreach ([
            'quotations' => ['quotes_page', 'customer-quotations'],
            'invoices' => ['invoices_page', 'customer-invoices'],
            'deliveryNotes' => ['delivery_page', 'customer-delivery'],
            'receipts' => ['receipts_page', 'customer-receipts'],
        ] as $variable => [$pageName, $anchor]) {
            $response->assertViewHas($variable, function ($paginator) use ($pageName, $anchor, $pages) {
                $url = $paginator->url(1);
                parse_str(parse_url($url, PHP_URL_QUERY), $query);
                $expected = array_merge($pages, [$pageName => 1]);
                ksort($query);
                ksort($expected);

                return $paginator->count() === 1 && $paginator->currentPage() === 2
                    && $query == $expected && parse_url($url, PHP_URL_FRAGMENT) === $anchor;
            });
        }
    }

    public function test_invalid_history_page_values_return_validation_errors(): void
    {
        $customer = Customer::create(['customer_code' => 'CUST-HISTORY-EMPTY', 'name' => 'Empty client']);
        $this->getJson(route('customers.show', $customer).'?quotes_page=0&invoices_page=bad&delivery_page=-1&receipts_page[]=2')
            ->assertUnprocessable()->assertJsonValidationErrors(['quotes_page', 'invoices_page', 'delivery_page', 'receipts_page']);
        $this->get(route('customers.show', $customer))->assertOk()
            ->assertSee('No invoices found for this client.')
            ->assertViewHas('receipts', fn ($receipts) => $receipts->total() === 0);
    }

    public function test_a_large_out_of_range_history_page_has_bounded_links_and_a_valid_last_page_backlink(): void
    {
        $customer = $this->customerWithHistory();
        $response = $this->get(route('customers.show', $customer).'?invoices_page=1000000000');

        $response->assertOk()->assertSee('Sales Invoices (16)')->assertSee('9,876.54')
            ->assertViewHas('invoices', fn ($invoices) => $invoices->total() === 16
                && $invoices->count() === 0 && $invoices->lastPage() === 2);

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//nav[@aria-label="Customer invoices pages"]//a');
        $this->assertCount(3, $links);

        foreach ($links as $link) {
            parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
            $this->assertContains((int) $query['invoices_page'], [1, 2]);
            $this->assertSame('customer-invoices', parse_url($link->getAttribute('href'), PHP_URL_FRAGMENT));
            if ($link->getAttribute('rel') === 'prev') {
                $this->assertSame('2', $query['invoices_page']);
            }
        }
        $this->assertSame('9876.54', $customer->fresh()->balance);
    }

    private function customerWithHistory(): Customer
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-HISTORY', 'name' => 'History client', 'balance' => 9876.54,
        ]);

        foreach (range(1, 16) as $number) {
            $suffix = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $customer->quotations()->create([
                'quotation_number' => 'QT-HISTORY-'.$suffix, 'quotation_date' => '2026-10-17',
                'category' => 'School Uniforms', 'total_qty' => $number, 'grand_total' => 1000 + $number,
            ]);
            $invoice = $customer->invoices()->create([
                'invoice_number' => 'INV-HISTORY-'.$suffix, 'invoice_date' => '2026-10-17',
                'total_qty' => $number, 'grand_total' => 1000 + $number, 'balance_due' => 500 + $number,
            ]);
            $customer->deliveryNotes()->create([
                'delivery_number' => 'DN-HISTORY-'.$suffix, 'invoice_id' => $invoice->id,
                'dispatch_date' => '2026-10-17', 'recipient_name' => 'History recipient',
            ]);
            $customer->receipts()->create([
                'receipt_number' => 'RCPT-HISTORY-'.$suffix, 'invoice_id' => $invoice->id,
                'payment_date' => '2026-10-17', 'amount_received' => $number,
            ]);
        }

        return $customer;
    }
}
