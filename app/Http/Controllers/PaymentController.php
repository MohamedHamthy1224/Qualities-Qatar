<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\CustomerReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerReceipt::with(['customer', 'invoice']);

        if ($request->filled('method')) {
            $query->where('payment_method', $request->input('method'));
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('receipt_number', 'like', "%{$s}%")
                  ->orWhere('reference_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%")
                         ->orWhere('company_name', 'like', "%{$s}%");
                  });
            });
        }

        $receipts = $query->orderBy('id', 'desc')->paginate(15);
        $totalCollected = CustomerReceipt::sum('amount_received');
        $todayCollected = CustomerReceipt::whereDate('payment_date', now()->toDateString())->sum('amount_received');

        return view('payments.index', compact('receipts', 'totalCollected', 'todayCollected'));
    }

    public function create(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $selectedCustomer = null;
        $selectedInvoice = null;

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::with('invoices')->find($request->customer_id);
        }

        if ($request->filled('invoice_id')) {
            $selectedInvoice = SalesInvoice::with('customer')->find($request->invoice_id);
            if ($selectedInvoice) {
                $selectedCustomer = $selectedInvoice->customer;
            }
        }

        $year = date('Y');
        $count = CustomerReceipt::whereYear('created_at', $year)->count() + 1;
        $suggestedNumber = 'RCPT-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        return view('payments.create', compact('customers', 'selectedCustomer', 'selectedInvoice', 'suggestedNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:sales_invoices,id',
            'receipt_number' => 'required|string|unique:customer_receipts,receipt_number',
            'amount_received' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:Cash,Bank Transfer,Card,Cheque',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $receipt = CustomerReceipt::create([
                'receipt_number' => $request->receipt_number,
                'customer_id' => $request->customer_id,
                'invoice_id' => $request->invoice_id,
                'amount_received' => $request->amount_received,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
            ]);

            // If an invoice is specified, allocate and update invoice status
            if ($request->invoice_id) {
                $invoice = SalesInvoice::findOrFail($request->invoice_id);
                $invoice->updatePaymentStatus();
            } else {
                // If no specific invoice was chosen, allocate automatically against oldest unpaid invoices
                $remaining = (float)$request->amount_received;
                $openInvoices = SalesInvoice::where('customer_id', $request->customer_id)
                    ->where('balance_due', '>', 0)
                    ->orderBy('invoice_date', 'asc')
                    ->get();

                foreach ($openInvoices as $inv) {
                    if ($remaining <= 0) break;
                    $inv->updatePaymentStatus();
                }
            }

            // Update customer's total outstanding balance
            $customer = Customer::find($request->customer_id);
            $customer?->recalculateBalance();

            return redirect()->route('payments.show', $receipt)
                ->with('success', "Payment receipt {$receipt->receipt_number} of QAR " . number_format((float)$receipt->amount_received, 2) . " recorded successfully.");
        });
    }

    public function show(CustomerReceipt $receipt)
    {
        $receipt->load(['customer', 'invoice']);

        return view('payments.show', compact('receipt'));
    }
}
