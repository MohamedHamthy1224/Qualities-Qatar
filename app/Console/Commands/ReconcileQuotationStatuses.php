<?php

namespace App\Console\Commands;

use App\Models\Quotation;
use App\Services\DocumentNumber;
use Illuminate\Console\Command;
use RuntimeException;

class ReconcileQuotationStatuses extends Command
{
    protected $signature = 'quotations:reconcile-statuses {--apply : Persist Invoiced for quotations with one matching linked invoice}';

    protected $description = 'Audit quotation/invoice status mismatches; preview only unless --apply is supplied';

    public function handle(): int
    {
        $candidates = Quotation::query()
            ->where(fn ($query) => $query->where('status', '!=', 'Invoiced')->orWhereNull('status'))
            ->whereHas('invoice')
            ->with('invoice')->withCount('invoice')->orderBy('id')->get();
        $safe = $candidates->filter(fn (Quotation $quotation) => $quotation->invoice_count === 1
            && $quotation->invoice->customer_id === $quotation->customer_id
        );
        $unsafe = $candidates->diff($safe);
        $missing = Quotation::query()->where('status', 'Invoiced')->whereDoesntHave('invoice')->orderBy('id')->get();

        $this->info($this->option('apply') ? 'Apply quotation status reconciliation' : 'Dry run: no records will be changed.');
        if ($safe->isNotEmpty()) {
            $this->table(['Quotation', 'Current status', 'Linked invoice', 'Correct status'], $safe->map(fn (Quotation $quotation) => [
                $quotation->quotation_number, $quotation->status, $quotation->invoice->invoice_number, 'Invoiced',
            ])->all());
        }
        foreach ($unsafe as $quotation) {
            $this->warn("{$quotation->quotation_number}: duplicate invoices or a different invoice customer; manual review required.");
        }
        foreach ($missing as $quotation) {
            $this->warn("{$quotation->quotation_number}: marked Invoiced but no linked invoice; no status guessed.");
        }

        $changed = 0;
        if ($this->option('apply') && $safe->isNotEmpty()) {
            // Use the same serialization as invoice creation, then recheck each link under lock.
            $changed = DocumentNumber::transaction(function () use ($safe) {
                $changed = 0;
                foreach ($safe as $candidate) {
                    $quotation = Quotation::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                    $invoices = $quotation->invoice()->lockForUpdate()->get();
                    if ($invoices->count() !== 1 || $invoices->first()->customer_id !== $quotation->customer_id) {
                        throw new RuntimeException('An invoice link changed during reconciliation. No status updates were committed. Run the audit again.');
                    }
                    if ($quotation->status !== 'Invoiced') {
                        $quotation->update(['status' => 'Invoiced']);
                        $changed++;
                    }
                }

                return $changed;
            });
        }

        $this->info('Quotations checked: '.Quotation::count().". Safe mismatches: {$safe->count()}. Updated: {$changed}. Review required: ".($unsafe->count() + $missing->count()).'.');

        return $unsafe->isNotEmpty() || $missing->isNotEmpty() ? self::FAILURE : self::SUCCESS;
    }
}
