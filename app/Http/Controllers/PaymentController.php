<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\CustomerReceipt;
use App\Services\DocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['per_page' => 'nullable|integer|in:8,10,15,20,25,50', 'page' => 'nullable|integer|min:1']);

        $query = CustomerReceipt::with(['customer', 'invoice', 'splitReceipts.invoice'])->whereNull('parent_receipt_id');

        if ($request->filled('method')) {
            $query->where('payment_method', $request->input('method'));
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('receipt_number', 'like', "%{$s}%")
                  ->orWhere('reference_number', 'like', "%{$s}%")
                  ->orWhereHas('splitReceipts', fn ($split) => $split->where('receipt_number', 'like', "%{$s}%"))
                  ->orWhereHas('customer', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%")
                         ->orWhere('company_name', 'like', "%{$s}%");
                  });
            });
        }

        $receipts = $query->orderBy('id', 'desc')->paginate((int) ($request->query('per_page') ?: 15))->withQueryString();
        $totalCollected = CustomerReceipt::sum('amount_received');
        $todayCollected = CustomerReceipt::whereDate('payment_date', now()->toDateString())->sum('amount_received');

        return view('payments.index', compact('receipts', 'totalCollected', 'todayCollected'));
    }

    public function create(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $selectedCustomer = null;
        $selectedInvoice = null;

        if ($request->old('customer_id', $request->input('customer_id'))) {
            $selectedCustomer = Customer::with('invoices')->find($request->old('customer_id', $request->input('customer_id')));
        }

        if ($request->old('invoice_id', $request->input('invoice_id'))) {
            $selectedInvoice = SalesInvoice::with('customer')->find($request->old('invoice_id', $request->input('invoice_id')));
            if ($selectedInvoice && (!$selectedCustomer || $selectedCustomer->is($selectedInvoice->customer))) {
                $selectedCustomer = $selectedInvoice->customer;
            } else {
                $selectedInvoice = null;
            }
        }

        $suggestedNumber = DocumentNumber::next(CustomerReceipt::class, 'receipt_number', 'RCPT-' . date('Y') . '-');
        $customerLocked = $selectedCustomer && ($request->filled('customer_id') || $request->filled('invoice_id'));

        return view('payments.create', compact('customers', 'selectedCustomer', 'selectedInvoice', 'suggestedNumber', 'customerLocked'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'invoice_id'     => ['nullable', Rule::exists('sales_invoices', 'id')->where('customer_id', $request->input('customer_id'))],
            'receipt_number' => 'required|string|max:40|unique:customer_receipts,receipt_number',
            'amount_received'=> 'required|numeric|decimal:0,2|min:0.01|max:9999999999.99',
            'payment_method' => 'required|in:Cash,Bank Transfer,Card,Cheque',
            'payment_date'   => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
        ]);

        return DocumentNumber::transaction(function () use ($validated) {
            // Serialize allocations for a customer so simultaneous receipts cannot pay the same balance twice.
            $customer = Customer::whereKey($validated['customer_id'])->lockForUpdate()->firstOrFail();
            if (CustomerReceipt::where('receipt_number', $validated['receipt_number'])->exists()) {
                throw ValidationException::withMessages(['receipt_number' => 'This receipt number has already been recorded.']);
            }
            $invoices = SalesInvoice::where('customer_id', $customer->id)
                ->when($validated['invoice_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
                ->orderBy('invoice_date')->orderBy('id')->lockForUpdate()->get();

            $balances = $invoices->mapWithKeys(function ($invoice) {
                $total = (int) round((float) $invoice->grand_total * 100);
                $paid = (int) round((float) $invoice->receipts()->sum('amount_received') * 100);
                return [$invoice->id => max(0, $total - $paid)];
            });
            $remaining = (int) round((float) $validated['amount_received'] * 100);
            if ($remaining > $balances->sum()) {
                throw ValidationException::withMessages([
                    'amount_received' => 'The payment exceeds the available invoice balance of QAR ' . number_format($balances->sum() / 100, 2) . '.',
                ]);
            }

            $receipt = null;
            $suffix = 1;
            foreach ($invoices as $invoice) {
                $allocated = min($remaining, $balances[$invoice->id]);
                if ($allocated <= 0) {
                    continue;
                }
                $number = $validated['receipt_number'];
                if ($receipt) {
                    do {
                        $number = $validated['receipt_number'] . '-' . ++$suffix;
                    } while (CustomerReceipt::where('receipt_number', $number)->exists());
                }
                $allocation = CustomerReceipt::create([
                    'receipt_number' => $number,
                    'parent_receipt_id' => $receipt?->id,
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'amount_received' => $allocated / 100,
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => $validated['payment_date'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $receipt ??= $allocation;
                $remaining -= $allocated;
                $invoice->updatePaymentStatus();
                if ($remaining === 0) {
                    break;
                }
            }
            $customer->recalculateBalance();

            return redirect()->route('payments.show', $receipt)
                ->with('success', "Payment {$receipt->receipt_number} of QAR " . number_format((float) $validated['amount_received'], 2) . ' recorded and allocated successfully.');
        });
    }

    public function show(CustomerReceipt $receipt)
    {
        $receipt->load(['customer', 'invoice']);

        return view('payments.show', compact('receipt'));
    }
}
