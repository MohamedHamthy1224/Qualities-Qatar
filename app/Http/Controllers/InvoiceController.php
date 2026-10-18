<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\SalesInvoice;
use App\Services\DocumentNumber;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function __construct(protected PricingService $pricingService) {}

    public function index(Request $request)
    {
        $pagination = validator($request->query(), [
            'per_page' => 'nullable|integer|in:10,15,20,25,50',
            'page' => 'nullable|integer|min:1',
        ])->validate();

        $query = SalesInvoice::with(['customer', 'items', 'receipts']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', function ($cq) use ($s) {
                        $cq->where('name', 'like', "%{$s}%")
                            ->orWhere('company_name', 'like', "%{$s}%");
                    });
            });
        }

        $invoices = $query->orderBy('id', 'desc')
            ->paginate((int) ($pagination['per_page'] ?? 15))->withQueryString();
        $totalInvoiced = SalesInvoice::sum('grand_total');
        $totalOutstanding = SalesInvoice::sum('balance_due');
        $unpaidCount = SalesInvoice::where('status', 'Unpaid')->count();

        return view('invoices.index', compact('invoices', 'totalInvoiced', 'totalOutstanding', 'unpaidCount'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::active()->orderBy('name')->get();
        $categories = Product::CATEGORIES;
        $suggestedNumber = DocumentNumber::next(SalesInvoice::class, 'invoice_number', 'INV-'.date('Y').'-');

        return view('invoices.create', compact('customers', 'products', 'categories', 'suggestedNumber'));
    }

    public function store(StoreDocumentRequest $request)
    {
        return DocumentNumber::transaction(function () use ($request) {
            if (SalesInvoice::where('invoice_number', $request->invoice_number)->exists()) {
                throw ValidationException::withMessages(['invoice_number' => 'This invoice number was just used. Please submit again with the refreshed number.']);
            }
            Customer::whereKey($request->customer_id)->lockForUpdate()->firstOrFail();
            $pricing = $this->pricingService->calculateQuotationTotals(
                $request->items,
                (float) $request->input('discount', 0),
                (float) $request->input('setup_charges', 0),
                (float) $request->input('print_charges', 0),
            );

            $invoice = SalesInvoice::create([
                'invoice_number' => $request->invoice_number,
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'total_qty' => $pricing['total_qty'],
                'subtotal' => $pricing['subtotal'],
                'discount' => $pricing['discount'],
                'setup_charges' => $pricing['setup_charges'],
                'print_charges' => $pricing['print_charges'],
                'grand_total' => $pricing['grand_total'],
                'paid_amount' => 0.00,
                'balance_due' => $pricing['grand_total'],
                'status' => $pricing['grand_total'] > 0 ? 'Unpaid' : 'Paid',
                'notes' => $request->notes,
            ]);

            foreach ($pricing['items'] as $pi) {
                unset($pi['base_amount']);
                $invoice->items()->create($pi);
            }

            // Create initial Production Order on Kanban
            $year = date('Y');
            $orderNumber = DocumentNumber::next(ProductionOrder::class, 'order_number', 'PO-'.$year.'-');

            ProductionOrder::create([
                'order_number' => $orderNumber,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                ...ProductionOrder::summaryAttributesForInvoice($invoice),
                'stage' => 'Confirmed',
                'priority' => 'Normal',
                'target_date' => $invoice->due_date ?? now()->addDays(7)->toDateString(),
                'notes' => 'Queued from Sales Invoice '.$invoice->invoice_number,
            ]);

            $invoice->customer?->recalculateBalance();

            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Invoice {$invoice->invoice_number} created successfully.");
        });
    }

    public function show(SalesInvoice $invoice)
    {
        $invoice->load(['customer', 'items', 'receipts', 'deliveryNotes.items', 'productionOrder', 'quotation']);

        return view('invoices.show', compact('invoice'));
    }
}
