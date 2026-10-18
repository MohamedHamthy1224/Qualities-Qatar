<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Services\QuotationInvoicingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuotationStatusConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public static function linkedQuotationStates(): array
    {
        $cases = [];
        foreach (['Draft', 'Sent', 'Approved', 'Cancelled'] as $status) {
            foreach (['approve', 'convertApproved'] as $action) {
                $cases[$status.' through '.$action] = [$status, $action];
            }
        }

        return $cases;
    }

    #[DataProvider('linkedQuotationStates')]
    public function test_existing_invoice_actions_repair_the_quotation_status_without_other_business_changes(string $status, string $action): void
    {
        [$quotation, $invoice] = $this->linkedQuotation($status);
        $before = $this->businessSnapshot();
        $quotationBefore = $quotation->fresh()->getAttributes();
        unset($quotationBefore['status'], $quotationBefore['updated_at']);
        $this->travel(1)->hour();

        $returnedInvoice = app(QuotationInvoicingService::class)->{$action}($quotation);

        $this->assertTrue($invoice->is($returnedInvoice));
        $this->assertSame('Invoiced', $quotation->fresh()->status);
        $this->assertSame($before, $this->businessSnapshot());
        $quotationAfter = $quotation->fresh()->getAttributes();
        unset($quotationAfter['status'], $quotationAfter['updated_at']);
        $this->assertSame($quotationBefore, $quotationAfter);

        // A second request must not recreate any documents or touch an already-correct quotation.
        $repairedQuotation = $quotation->fresh()->getAttributes();
        $this->travel(1)->hour();
        $returnedAgain = app(QuotationInvoicingService::class)->{$action}($quotation);
        $this->assertTrue($invoice->is($returnedAgain));
        $this->assertSame($repairedQuotation, $quotation->fresh()->getAttributes());
        $this->assertSame($before, $this->businessSnapshot());
    }

    public function test_status_requests_cannot_revert_a_linked_quotation_to_draft_sent_or_cancelled(): void
    {
        [$quotation] = $this->linkedQuotation('Invoiced');
        $before = $this->businessSnapshot();
        $quotationBefore = $quotation->fresh()->getAttributes();

        foreach (['Draft', 'Sent', 'Cancelled'] as $status) {
            $this->from('/quotations/'.$quotation->id)
                ->patch('/quotations/'.$quotation->id.'/status', ['status' => $status])
                ->assertRedirect('/quotations/'.$quotation->id)
                ->assertSessionHasErrors('status');

            $this->assertSame($quotationBefore, $quotation->fresh()->getAttributes());
            $this->assertSame($before, $this->businessSnapshot());
        }
    }

    public function test_sent_can_be_recorded_for_an_uninvoiced_draft_without_creating_an_invoice_or_production_job(): void
    {
        $quotation = $this->quotation();
        $before = $this->businessSnapshot();

        $this->from('/quotations/'.$quotation->id)
            ->patch('/quotations/'.$quotation->id.'/status', ['status' => 'Sent'])
            ->assertRedirect('/quotations/'.$quotation->id)
            ->assertSessionHasNoErrors();

        $this->assertSame('Sent', $quotation->fresh()->status);
        $this->assertSame($before, $this->businessSnapshot());
        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertDatabaseCount('production_orders', 0);
    }

    public function test_reconciliation_is_dry_run_by_default_and_repairs_only_linked_statuses_when_applied(): void
    {
        $linked = [];
        $unlinked = [];
        foreach (['Draft', 'Sent', 'Approved', 'Cancelled'] as $status) {
            [$quotation] = $this->linkedQuotation($status);
            $linked[$quotation->id] = $status;
            $unlinkedQuotation = $this->quotation();
            $unlinkedQuotation->update(['status' => $status]);
            $unlinked[$unlinkedQuotation->id] = $status;
        }
        $before = $this->businessSnapshot();
        $allQuotationsBefore = $this->quotationSnapshot();

        $this->artisan('quotations:reconcile-statuses')
            ->expectsOutputToContain('Dry run: no records will be changed.')
            ->assertExitCode(0);
        $this->assertSame($allQuotationsBefore, $this->quotationSnapshot());
        $this->assertSame($before, $this->businessSnapshot());

        $this->travel(1)->hour();
        $this->artisan('quotations:reconcile-statuses', ['--apply' => true])->assertExitCode(0);
        foreach ($linked as $id => $oldStatus) {
            $this->assertSame('Invoiced', Quotation::findOrFail($id)->status, $oldStatus);
        }
        foreach ($unlinked as $id => $status) {
            $this->assertSame($status, Quotation::findOrFail($id)->status);
        }
        $this->assertSame($before, $this->businessSnapshot());

        $repaired = $this->quotationSnapshot();
        $this->travel(1)->hour();
        $this->artisan('quotations:reconcile-statuses', ['--apply' => true])->assertExitCode(0);
        $this->assertSame($repaired, $this->quotationSnapshot());
        $this->assertSame($before, $this->businessSnapshot());
    }

    public function test_reconciliation_flags_a_missing_invoice_without_guessing_a_replacement_status_or_creating_documents(): void
    {
        $quotation = $this->quotation();
        $quotation->update(['status' => 'Invoiced']);
        $before = $this->businessSnapshot();
        $quotationBefore = $this->quotationSnapshot();

        $this->artisan('quotations:reconcile-statuses', ['--apply' => true])
            ->expectsOutputToContain($quotation->quotation_number.': marked Invoiced but no linked invoice; no status guessed.')
            ->assertExitCode(1);

        $this->assertSame($quotationBefore, $this->quotationSnapshot());
        $this->assertSame($before, $this->businessSnapshot());
    }

    public function test_reconciliation_leaves_duplicate_invoice_and_cross_customer_links_for_manual_review(): void
    {
        [$duplicateQuotation, $invoice] = $this->linkedQuotation('Draft');
        $duplicate = $invoice->replicate();
        $duplicate->invoice_number = 'INV-STATUS-DUPLICATE';
        $duplicate->save();
        [$otherCustomerQuotation, $otherInvoice] = $this->linkedQuotation('Sent');
        $otherInvoice->update(['customer_id' => $duplicateQuotation->customer_id]);
        $before = $this->businessSnapshot();
        $quotationBefore = $this->quotationSnapshot();

        $this->artisan('quotations:reconcile-statuses', ['--apply' => true])
            ->expectsOutputToContain($duplicateQuotation->quotation_number.': duplicate invoices or a different invoice customer; manual review required.')
            ->expectsOutputToContain($otherCustomerQuotation->quotation_number.': duplicate invoices or a different invoice customer; manual review required.')
            ->assertExitCode(1);

        $this->assertSame($quotationBefore, $this->quotationSnapshot());
        $this->assertSame($before, $this->businessSnapshot());
    }

    private function linkedQuotation(string $status): array
    {
        $quotation = $this->quotation();
        $invoice = app(QuotationInvoicingService::class)->approve($quotation);
        $invoice->update(['paid_amount' => 25, 'balance_due' => 75, 'status' => 'Partially Paid']);

        // Deliberately retain a legacy balance: status repair must not recalculate money.
        $quotation->customer->update(['balance' => 432.10]);
        $invoice->productionOrder->update(['stage' => 'Quality Check', 'priority' => 'High']);
        DB::table('quotations')->where('id', $quotation->id)->update(['status' => $status]);

        return [$quotation->fresh(), $invoice->fresh()];
    }

    private function quotation(): Quotation
    {
        $number = 10 + Quotation::count();
        $customer = Customer::create(['customer_code' => 'CUST-STATUS-'.$number, 'name' => 'Status School']);
        $quotation = Quotation::create([
            'quotation_number' => 'QT-2026-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'quotation_date' => '2026-10-14',
            'delivery_date' => '2026-10-21',
            'category' => 'School Uniforms',
            'status' => 'Draft',
            'total_qty' => 4,
            'subtotal' => 100,
            'grand_total' => 100,
            'notes' => 'Keep the original quotation and invoice unchanged.',
        ]);
        $quotation->items()->create([
            'product_name' => 'School Polo',
            'category' => 'School Uniforms',
            'input_mode' => 'garment_matrix',
            'pricing_unit' => 'piece',
            'size_s' => 2,
            'size_m' => 2,
            'total_qty' => 4,
            'unit_price' => 25,
            'line_total' => 100,
        ]);

        return $quotation;
    }

    private function quotationSnapshot(): array
    {
        return DB::table('quotations')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    private function businessSnapshot(): array
    {
        $snapshot = [];
        foreach (['customers', 'quotation_items', 'sales_invoices', 'invoice_items', 'production_orders', 'customer_receipts', 'delivery_notes', 'delivery_items'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }

        return $snapshot;
    }
}
