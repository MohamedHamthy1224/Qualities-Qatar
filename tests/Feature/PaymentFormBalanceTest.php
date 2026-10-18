<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\SalesInvoice;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFormBalanceTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private SalesInvoice $largerInvoice;

    private SalesInvoice $smallerInvoice;

    private SalesInvoice $paidInvoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            // A stale cached customer balance must not hide the second eligible invoice.
            'customer_code' => 'CUST-PAYMENT-BALANCE', 'name' => 'Payment balance customer', 'balance' => '570.00',
        ]);
        $this->largerInvoice = $this->invoice('INV-BALANCE-570', '570.00');
        $this->smallerInvoice = $this->invoice('INV-BALANCE-90', '90.00');
        $this->paidInvoice = $this->invoice('INV-BALANCE-PAID', '0.00', '100.00');
        CustomerReceipt::create([
            'customer_id' => $this->customer->id, 'invoice_id' => $this->paidInvoice->id,
            'receipt_number' => 'RCPT-BALANCE-PAID', 'amount_received' => '100.00',
            'payment_date' => '2026-10-17', 'payment_method' => 'Cash',
        ]);
    }

    public function test_preselected_invoice_card_shows_that_invoice_due_instead_of_customer_total(): void
    {
        foreach ([$this->largerInvoice, $this->smallerInvoice] as $invoice) {
            $response = $this->get(route('payments.create', ['invoice_id' => $invoice->id]))
                ->assertOk()
                ->assertViewHas('selectedInvoice', fn ($selected) => $selected->is($invoice))
                ->assertViewHas('selectedCustomer', fn ($selected) => $selected->is($this->customer))
                ->assertViewHas('customerLocked', true);

            $form = $this->form($response->getContent());
            $this->assertSame('QAR '.number_format((float) $invoice->balance_due, 2), $this->text($form, 'customerBalanceDisplay'));
            $this->assertTarget($form, $invoice->balance_due, 'Selected invoice balance');
            $this->assertSame($invoice->balance_due, $this->element($form, 'amountReceived')->getAttribute('value'));
            $this->assertSame((string) $invoice->id, $this->selectedInvoiceId($form));
            $this->assertInvoiceChoices($form);
        }

        $this->assertSame('570.00', $this->customer->fresh()->balance);
        $this->assertDatabaseCount('customer_receipts', 1);
    }

    public function test_customer_only_form_uses_total_for_auto_allocation_and_leaves_amount_for_entry(): void
    {
        $response = $this->get(route('payments.create', ['customer_id' => $this->customer->id]))
            ->assertOk()->assertViewHas('selectedInvoice', null)->assertViewHas('customerLocked', true);

        $form = $this->form($response->getContent());
        $this->assertSame('QAR 660.00', $this->text($form, 'customerBalanceDisplay'));
        $this->assertTarget($form, '660.00', 'Total open invoice balance');
        $this->assertSame('', $this->selectedInvoiceId($form));
        $this->assertSame('', $this->element($form, 'amountReceived')->getAttribute('value'));
        $this->assertInvoiceChoices($form);
    }

    public function test_blank_form_has_zero_balance_and_no_preselected_customer_or_invoice(): void
    {
        $response = $this->get(route('payments.create'))->assertOk()
            ->assertViewHas('selectedCustomer', null)->assertViewHas('selectedInvoice', null)
            ->assertViewHas('customerLocked', false);

        $form = $this->form($response->getContent());
        $this->assertSame('QAR 0.00', $this->text($form, 'customerBalanceDisplay'));
        $this->assertSame('', $this->element($form, 'amountReceived')->getAttribute('value'));
        $this->assertSame('', $this->selectedInvoiceId($form));
        $this->assertSame('select', $this->element($form, 'customerSelect')->tagName);
        $this->assertSame(1, $form->query('//*[@id="invoiceSelect"]/option')->length);
    }

    public function test_failed_payment_restores_entered_amount_with_invoice_balance_and_unlocked_customer(): void
    {
        $this->from(route('payments.create'))->post(route('payments.store'), [
            'customer_id' => $this->customer->id,
            'invoice_id' => $this->smallerInvoice->id,
            'receipt_number' => 'RCPT-BALANCE-OVERPAY',
            'amount_received' => '91.00',
            'payment_date' => '2026-10-17',
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'RETAIN-BALANCE-REFERENCE',
            'notes' => 'Keep this entered amount after validation.',
        ])->assertRedirect(route('payments.create'))->assertSessionHasErrors('amount_received');

        $response = $this->get(route('payments.create'))->assertOk()
            ->assertViewHas('customerLocked', false)
            ->assertViewHas('selectedInvoice', fn ($invoice) => $invoice->is($this->smallerInvoice));
        $form = $this->form($response->getContent());

        $this->assertSame('QAR 90.00', $this->text($form, 'customerBalanceDisplay'));
        $this->assertTarget($form, '90.00', 'Selected invoice balance');
        $this->assertSame('91.00', $this->element($form, 'amountReceived')->getAttribute('value'));
        $this->assertSame((string) $this->smallerInvoice->id, $this->selectedInvoiceId($form));
        $this->assertSame('select', $this->element($form, 'customerSelect')->tagName);
        $this->assertSame((string) $this->customer->id, $form->query('//*[@id="customerSelect"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('RETAIN-BALANCE-REFERENCE', $this->element($form, 'paymentReference')->getAttribute('value'));
        $this->assertSame('Keep this entered amount after validation.', $this->text($form, 'paymentNotes'));
        $this->assertSame('90.00', $this->smallerInvoice->fresh()->balance_due);
        $this->assertDatabaseCount('customer_receipts', 1);
    }

    public function test_restored_auto_allocation_overrides_an_invoice_query_without_overwriting_entered_amount(): void
    {
        $response = $this->withSession(['_old_input' => [
            'customer_id' => (string) $this->customer->id,
            'invoice_id' => '',
            'amount_received' => '25.50',
        ]])->get(route('payments.create', ['invoice_id' => $this->largerInvoice->id]))
            ->assertOk()->assertViewHas('selectedInvoice', null);

        $form = $this->form($response->getContent());
        $this->assertSame('QAR 660.00', $this->text($form, 'customerBalanceDisplay'));
        $this->assertTarget($form, '660.00', 'Total open invoice balance');
        $this->assertSame('', $this->selectedInvoiceId($form));
        $this->assertSame('25.50', $this->element($form, 'amountReceived')->getAttribute('value'));
        $this->assertInvoiceChoices($form);
    }

    public function test_foreign_invoice_is_not_selected_or_available_for_an_explicit_customer(): void
    {
        $other = Customer::create(['customer_code' => 'CUST-OTHER-BALANCE', 'name' => 'Other customer', 'balance' => '999.00']);
        $foreign = SalesInvoice::create([
            'customer_id' => $other->id, 'invoice_number' => 'INV-FOREIGN-BALANCE',
            'invoice_date' => '2026-10-17', 'grand_total' => '999.00', 'balance_due' => '999.00',
        ]);

        $response = $this->get(route('payments.create', [
            'customer_id' => $this->customer->id, 'invoice_id' => $foreign->id,
        ]))->assertOk()->assertViewHas('selectedInvoice', null)
            ->assertViewHas('selectedCustomer', fn ($selected) => $selected->is($this->customer));
        $form = $this->form($response->getContent());

        $this->assertSame('QAR 660.00', $this->text($form, 'customerBalanceDisplay'));
        $this->assertTarget($form, '660.00', 'Total open invoice balance');
        $this->assertSame('', $this->selectedInvoiceId($form));
        $this->assertSame(0, $form->query('//*[@id="invoiceSelect"]/option[@value="'.$foreign->id.'"]')->length);
        $this->assertInvoiceChoices($form);
    }

    public function test_paid_invoice_preselection_uses_auto_allocation_without_a_zero_amount_default(): void
    {
        $response = $this->get(route('payments.create', ['invoice_id' => $this->paidInvoice->id]))
            ->assertOk()->assertViewHas('customerLocked', true)
            ->assertViewHas('selectedCustomer', fn ($selected) => $selected->is($this->customer));
        $form = $this->form($response->getContent());

        $this->assertSame('QAR 660.00', $this->text($form, 'customerBalanceDisplay'));
        $this->assertTarget($form, '660.00', 'Total open invoice balance');
        $this->assertSame('', $this->selectedInvoiceId($form));
        $this->assertSame('', $this->element($form, 'amountReceived')->getAttribute('value'));
        $this->assertInvoiceChoices($form);
    }

    public function test_unpaid_invoice_api_returns_only_customer_open_choices_and_their_current_total(): void
    {
        $other = Customer::create(['customer_code' => 'CUST-API-OTHER', 'name' => 'Other API customer', 'balance' => '12.00']);
        SalesInvoice::create([
            'customer_id' => $other->id, 'invoice_number' => 'INV-API-OTHER',
            'invoice_date' => '2026-10-17', 'grand_total' => '12.00', 'balance_due' => '12.00',
        ]);

        $response = $this->getJson(route('customers.unpaid-invoices', $this->customer))
            ->assertOk()->assertJsonPath('customer_id', $this->customer->id)->assertJsonCount(2, 'invoices');
        $invoices = collect($response->json('invoices'))->keyBy('id');

        $this->assertEqualsCanonicalizing([$this->largerInvoice->id, $this->smallerInvoice->id], $invoices->keys()->all());
        $this->assertSame('570.00', $invoices[$this->largerInvoice->id]['balance_due']);
        $this->assertSame('90.00', $invoices[$this->smallerInvoice->id]['balance_due']);
        $this->assertEquals(660, $invoices->sum('balance_due'));
        $this->assertEquals(570, $response->json('balance'), 'The existing API cache field is preserved; the form uses eligible invoice dues.');
        $this->assertSame('570.00', $this->customer->fresh()->balance);
        $this->assertDatabaseCount('customer_receipts', 1);
    }

    private function invoice(string $number, string $due, ?string $total = null): SalesInvoice
    {
        $total ??= $due;

        return SalesInvoice::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => $number,
            'invoice_date' => '2026-10-17',
            'grand_total' => $total,
            'paid_amount' => (float) $total - (float) $due,
            'balance_due' => $due,
            'status' => $due === '0.00' ? 'Paid' : 'Unpaid',
        ]);
    }

    private function form(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new DOMXPath($document);
    }

    private function element(DOMXPath $form, string $id): DOMElement
    {
        $nodes = $form->query('//*[@id="'.$id.'"]');
        $this->assertSame(1, $nodes->length, "Expected one {$id} element.");

        return $nodes->item(0);
    }

    private function text(DOMXPath $form, string $id): string
    {
        return preg_replace('/\s+/', ' ', trim($this->element($form, $id)->textContent));
    }

    private function selectedInvoiceId(DOMXPath $form): string
    {
        $options = $form->query('//*[@id="invoiceSelect"]/option[@selected]');

        return $options->length ? $options->item(0)->getAttribute('value') : '';
    }

    private function assertInvoiceChoices(DOMXPath $form): void
    {
        $options = $form->query('//*[@id="invoiceSelect"]/option[@value!=""]');
        $choices = [];
        foreach ($options as $option) {
            $choices[$option->getAttribute('value')] = $option->getAttribute('data-due');
        }

        $this->assertCount(2, $choices);
        $this->assertSame($this->largerInvoice->balance_due, $choices[$this->largerInvoice->id]);
        $this->assertSame($this->smallerInvoice->balance_due, $choices[$this->smallerInvoice->id]);
        $this->assertArrayNotHasKey($this->paidInvoice->id, $choices);
    }

    private function assertTarget(DOMXPath $form, string $balance, string $label): void
    {
        $this->assertSame($label, $this->text($form, 'paymentBalanceLabel'));
        $this->assertNotSame('', $this->element($form, 'amountReceived')->getAttribute('max'));
        $this->assertEquals((float) $balance, (float) $this->element($form, 'amountReceived')->getAttribute('max'));
        $this->assertEquals(660, (float) $this->element($form, 'paymentForm')->getAttribute('data-customer-balance'));
        $this->assertSame(1, $form->query('//*[@id="customerBalanceDisplay"]/ancestor-or-self::*[@role="status" and @aria-live="polite" and @aria-atomic="true"]')->length);
    }
}
