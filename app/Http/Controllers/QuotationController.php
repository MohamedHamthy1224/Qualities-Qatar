<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Services\DocumentNumber;
use App\Services\PricingService;
use App\Services\QuotationInvoicingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    public function __construct(
        protected PricingService $pricingService,
        protected QuotationInvoicingService $quotationInvoicing
    ) {}

    public function index(Request $request)
    {
        $pagination = validator($request->query(), [
            'per_page' => 'nullable|integer|in:10,15,20,25,50',
            'page' => 'nullable|integer|min:1',
        ])->validate();

        $query = Quotation::with(['customer', 'items', 'invoice']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $cat = $request->category;
            $query->where(function ($q) use ($cat) {
                $q->where('category', $cat)
                    ->orWhereHas('items', function ($iq) use ($cat) {
                        $iq->where('category', $cat);
                    });
            });
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('quotation_number', 'like', "%{$s}%")
                    ->orWhereHas('customer', function ($cq) use ($s) {
                        $cq->where('name', 'like', "%{$s}%")
                            ->orWhere('company_name', 'like', "%{$s}%");
                    });
            });
        }

        $quotations = $query->orderBy('id', 'desc')
            ->paginate((int) ($pagination['per_page'] ?? 15))->withQueryString();
        $totalQuotesCount = Quotation::count();
        $approvedQuotesCount = Quotation::whereIn('status', ['Approved', 'Invoiced'])->count();

        return view('quotations.index', compact('quotations', 'totalQuotesCount', 'approvedQuotesCount'));
    }

    /**
     * API: Get active products filtered by category.
     */
    public function getProducts(Request $request)
    {
        $category = $request->input('category');
        $query = Product::active();

        if ($category && $category !== 'all' && $category !== 'All Categories') {
            $query->where('category', $category);
        }

        $products = $query->orderBy('name')->get();

        return response()->json([
            'category' => $category ?? 'all',
            'count' => $products->count(),
            'products' => $products,
        ]);
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $categories = Product::CATEGORIES;
        $products = Product::active()->orderBy('name')->get();

        $suggestedNumber = DocumentNumber::next(Quotation::class, 'quotation_number', 'QT-'.date('Y').'-');

        return view('quotations.create', compact('customers', 'categories', 'products', 'suggestedNumber'));
    }

    public function store(StoreDocumentRequest $request)
    {
        return DocumentNumber::transaction(function () use ($request) {
            if (Quotation::where('quotation_number', $request->quotation_number)->exists()) {
                throw ValidationException::withMessages(['quotation_number' => 'This quotation number was just used. Please submit again with the refreshed number.']);
            }
            $artworkPath = null;
            if ($request->hasFile('artwork_file')) {
                $artworkPath = $request->file('artwork_file')->store('artworks', 'public');
            }

            // Authoritative server-side calculation via PricingService
            $pricing = $this->pricingService->calculateQuotationTotals(
                rawItems: $request->items,
                overallDiscount: (float) ($request->discount ?? 0),
                overallSetup: (float) ($request->setup_charges ?? 0),
                overallPrint: (float) ($request->print_charges ?? 0),
                tax: 0
            );

            $headerCategory = $pricing['primary_category'];

            $quotation = Quotation::create([
                'quotation_number' => $request->quotation_number,
                'customer_id' => $request->customer_id,
                'category' => $headerCategory,
                'quotation_date' => $request->quotation_date,
                'delivery_date' => $request->delivery_date,
                'total_qty' => $pricing['total_qty'],
                'subtotal' => $pricing['subtotal'],
                'discount' => $pricing['discount'],
                'setup_charges' => $pricing['setup_charges'],
                'print_charges' => $pricing['print_charges'],
                'grand_total' => $pricing['grand_total'],
                'status' => $request->action === 'send' ? 'Sent' : 'Draft',
                'artwork_file' => $artworkPath,
                'notes' => $request->notes,
            ]);

            foreach ($pricing['items'] as $itemData) {
                unset($itemData['base_amount']);
                $quotation->items()->create($itemData);
            }

            return redirect()->route('quotations.show', $quotation)
                ->with('success', "Quotation {$quotation->quotation_number} with ".count($pricing['items']).' line items created successfully.');
        });
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['customer', 'items.product', 'invoice', 'productionOrder']);

        return view('quotations.show', compact('quotation'));
    }

    /** Compatibility for existing links; approval now creates the invoice automatically. */
    public function convertToInvoice(Quotation $quotation)
    {
        $invoice = $this->quotationInvoicing->convertApproved($quotation);

        return redirect()->route('invoices.show', $invoice)
            ->with('info', "Sales Invoice {$invoice->invoice_number} is ready for Quotation {$quotation->quotation_number}.");
    }

    public function updateStatus(Request $request, Quotation $quotation)
    {
        $validated = $request->validate(['status' => 'required|in:Draft,Sent,Approved,Cancelled']);
        if ($validated['status'] === 'Approved') {
            $invoice = $this->quotationInvoicing->approve($quotation);

            return back()->with('success', "Quotation {$quotation->quotation_number} approved. Sales Invoice {$invoice->invoice_number} is available in Sales Invoices.");
        }

        DB::transaction(function () use ($request, $quotation) {
            $quotation = Quotation::whereKey($quotation->id)->lockForUpdate()->firstOrFail();
            if ($quotation->invoice()->exists() || $quotation->status === 'Invoiced') {
                throw ValidationException::withMessages(['status' => 'An invoiced quotation cannot change status. Open its linked invoice instead.']);
            }
            $quotation->update(['status' => $request->status]);
        });

        return back()->with('success', "Quotation status updated to {$request->status}.");
    }
}
