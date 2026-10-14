<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
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

        $customers = $query->orderBy('name')->paginate(15);
        $totalOutstanding = Customer::sum('balance');

        return view('customers.index', compact('customers', 'totalOutstanding'));
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'quotations.items',
            'invoices.items',
            'deliveryNotes.items',
            'receipts.invoice',
            'productionOrders'
        ]);

        return view('customers.show', compact('customer'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
        ]);

        // Generate customer code
        $nextId = Customer::max('id') + 1;
        $customerCode = 'CUST-QAT-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $customer = Customer::create(array_merge($validated, [
            'customer_code' => $customerCode,
            'balance' => 0.00,
        ]));

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
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
            'balance' => (float)$customer->balance,
            'invoices' => $invoices,
        ]);
    }
}
