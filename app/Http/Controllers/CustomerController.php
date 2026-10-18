<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\DocumentNumber;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => 'nullable|string|max:255', 'filter' => 'nullable|in:balance', 'per_page' => 'nullable|integer|in:8,10,15,20,25,50', 'page' => 'nullable|integer|min:1']);
        $query = Customer::withCount(['quotations', 'invoices', 'deliveryNotes', 'receipts']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('company_name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('customer_code', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filter === 'balance') {
            $query->where('balance', '>', 0);
        }

        $customers = $query->orderBy('name')->orderBy('id')->paginate((int) ($request->query('per_page') ?: 15))->withQueryString();
        $totalOutstanding = Customer::sum('balance');
        $totalCustomers = Customer::count();

        return view('customers.index', compact('customers', 'totalOutstanding', 'totalCustomers'));
    }

    public function show(Request $request, Customer $customer)
    {
        $request->validate([
            'quotes_page' => 'nullable|integer|min:1',
            'invoices_page' => 'nullable|integer|min:1',
            'delivery_page' => 'nullable|integer|min:1',
            'receipts_page' => 'nullable|integer|min:1',
        ]);

        $customer->loadCount(['quotations', 'invoices', 'deliveryNotes', 'receipts']);
        $quotations = $customer->quotations()->paginate(15, ['*'], 'quotes_page')
            ->withQueryString()->fragment('customer-quotations');
        $invoices = $customer->invoices()->paginate(15, ['*'], 'invoices_page')
            ->withQueryString()->fragment('customer-invoices');
        $deliveryNotes = $customer->deliveryNotes()->with('invoice')->paginate(15, ['*'], 'delivery_page')
            ->withQueryString()->fragment('customer-delivery');
        $receipts = $customer->receipts()->paginate(15, ['*'], 'receipts_page')
            ->withQueryString()->fragment('customer-receipts');

        return view('customers.show', compact('customer', 'quotations', 'invoices', 'deliveryNotes', 'receipts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:10000',
        ]);

        $customer = DocumentNumber::transaction(fn () => Customer::create(array_merge($validated, [
            'customer_code' => DocumentNumber::next(Customer::class, 'customer_code', 'CUST-QAT-'),
            'balance' => 0.00,
        ])), 3);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => $customer,
            ]);
        }

        return redirect()->route('customers.show', $customer)
            ->with('success', "Customer {$customer->name} created successfully with code {$customer->customer_code}.");
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validateWithBag('customerProfile', [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:10000',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer profile updated successfully.');
    }

    /**
     * API endpoint for dynamic dropdowns: Returns unpaid invoices and balance for a customer.
     */
    public function getUnpaidInvoices(Customer $customer)
    {
        $invoices = $customer->invoices()
            ->where('balance_due', '>', 0)
            ->select('id', 'invoice_number', 'grand_total', 'paid_amount', 'balance_due', 'invoice_date')
            ->get();

        return response()->json([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'balance' => (float) $customer->balance,
            'invoices' => $invoices,
        ]);
    }
}
