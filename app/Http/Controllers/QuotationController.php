<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with(['customer', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
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

        $quotations = $query->orderBy('id', 'desc')->paginate(15);
        $totalQuotesCount = Quotation::count();
        $approvedQuotesCount = Quotation::whereIn('status', ['Approved', 'Invoiced'])->count();

        return view('quotations.index', compact('quotations', 'totalQuotesCount', 'approvedQuotesCount'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        // Generate next quotation number
        $year = date('Y');
        $nextNum = Quotation::whereYear('created_at', $year)->count() + 1;
        $suggestedNumber = 'QT-' . $year . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        return view('quotations.create', compact('customers', 'products', 'suggestedNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_number' => 'required|string|unique:quotations,quotation_number',
            'category' => 'required|string',
            'quotation_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'artwork_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf,svg,webp|max:10240',
        ]);

        return DB::transaction(function () use ($request) {
            $artworkPath = null;
            if ($request->hasFile('artwork_file')) {
                $artworkPath = $request->file('artwork_file')->store('artworks', 'public');
            }

            $totalQty = 0;
            $subtotal = 0;
            $setupChargesTotal = (float)($request->setup_charges ?? 0);
            $printChargesTotal = (float)($request->print_charges ?? 0);
            $discountTotal = (float)($request->discount ?? 0);

            // Pre-calculate items
            $preparedItems = [];
            foreach ($request->items as $item) {
                $sizeXs = (int)($item['size_xs'] ?? 0);
                $sizeS = (int)($item['size_s'] ?? 0);
                $sizeM = (int)($item['size_m'] ?? 0);
                $sizeL = (int)($item['size_l'] ?? 0);
                $sizeXl = (int)($item['size_xl'] ?? 0);
                $size2xl = (int)($item['size_2xl'] ?? 0);
                $size3xl = (int)($item['size_3xl'] ?? 0);
                $sizeCustom = (int)($item['size_custom'] ?? 0);

                $itemQty = $sizeXs + $sizeS + $sizeM + $sizeL + $sizeXl + $size2xl + $size3xl + $sizeCustom;
                if ($itemQty <= 0 && isset($item['total_qty']) && (int)$item['total_qty'] > 0) {
                    $itemQty = (int)$item['total_qty'];
                }

                $unitPrice = (float)($item['unit_price'] ?? 0);
                $lineDiscount = (float)($item['line_discount'] ?? 0);
                $itemSetup = (float)($item['setup_charges'] ?? 0);
                $itemPrint = (float)($item['print_charges'] ?? 0);

                $lineTotal = max(0, ($itemQty * $unitPrice) - $lineDiscount + $itemSetup + $itemPrint);

                $totalQty += $itemQty;
                $subtotal += $lineTotal;

                $preparedItems[] = [
                    'product_name' => $item['product_name'],
                    'size_xs' => $sizeXs,
                    'size_s' => $sizeS,
                    'size_m' => $sizeM,
                    'size_l' => $sizeL,
                    'size_xl' => $sizeXl,
                    'size_2xl' => $size2xl,
                    'size_3xl' => $size3xl,
                    'size_custom' => $sizeCustom,
                    'custom_size_notes' => $item['custom_size_notes'] ?? null,
                    'total_qty' => $itemQty,
                    'unit_price' => $unitPrice,
                    'line_discount' => $lineDiscount,
                    'setup_charges' => $itemSetup,
                    'print_charges' => $itemPrint,
                    'line_total' => $lineTotal,
                ];
            }

            $grandTotal = max(0, $subtotal + $setupChargesTotal + $printChargesTotal - $discountTotal);

            $quotation = Quotation::create([
                'quotation_number' => $request->quotation_number,
                'customer_id' => $request->customer_id,
                'category' => $request->category,
                'quotation_date' => $request->quotation_date,
                'delivery_date' => $request->delivery_date,
                'total_qty' => $totalQty,
                'subtotal' => $subtotal,
                'discount' => $discountTotal,
                'setup_charges' => $setupChargesTotal,
                'print_charges' => $printChargesTotal,
                'grand_total' => $grandTotal,
                'status' => $request->action === 'send' ? 'Sent' : 'Draft',
                'artwork_file' => $artworkPath,
                'notes' => $request->notes,
            ]);

            foreach ($preparedItems as $pi) {
                $quotation->items()->create($pi);
            }

            return redirect()->route('quotations.show', $quotation)
                ->with('success', "Quotation {$quotation->quotation_number} created successfully.");
        });
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['customer', 'items', 'invoice', 'productionOrder']);

        return view('quotations.show', compact('quotation'));
    }

    /**
     * 1-Click Conversion: Converts an approved Quotation into an official Sales Invoice
     * and automatically queues it to the Production Kanban Pipeline!
     */
    public function convertToInvoice(Quotation $quotation)
    {
        if ($quotation->invoice) {
            return redirect()->route('invoices.show', $quotation->invoice)
                ->with('info', "Quotation already converted to Invoice {$quotation->invoice->invoice_number}.");
        }

        return DB::transaction(function () use ($quotation) {
            $year = date('Y');
            $count = SalesInvoice::whereYear('created_at', $year)->count() + 1;
            $invoiceNumber = 'INV-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            // Create Sales Invoice
            $invoice = SalesInvoice::create([
                'invoice_number' => $invoiceNumber,
                'quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => $quotation->delivery_date ?? now()->addDays(15)->toDateString(),
                'total_qty' => $quotation->total_qty,
                'subtotal' => $quotation->subtotal,
                'discount' => $quotation->discount,
                'setup_charges' => $quotation->setup_charges,
                'print_charges' => $quotation->print_charges,
                'grand_total' => $quotation->grand_total,
                'paid_amount' => 0.00,
                'balance_due' => $quotation->grand_total,
                'status' => 'Unpaid',
                'notes' => "Converted from Quotation {$quotation->quotation_number}. " . $quotation->notes,
            ]);

            // Copy items with full size breakdown
            foreach ($quotation->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_name' => $item->product_name,
                    'size_xs' => $item->size_xs,
                    'size_s' => $item->size_s,
                    'size_m' => $item->size_m,
                    'size_l' => $item->size_l,
                    'size_xl' => $item->size_xl,
                    'size_2xl' => $item->size_2xl,
                    'size_3xl' => $item->size_3xl,
                    'size_custom' => $item->size_custom,
                    'custom_size_notes' => $item->custom_size_notes,
                    'total_qty' => $item->total_qty,
                    'unit_price' => $item->unit_price,
                    'line_discount' => $item->line_discount,
                    'setup_charges' => $item->setup_charges,
                    'print_charges' => $item->print_charges,
                    'line_total' => $item->line_total,
                ]);
            }

            // Create Production Order in Kanban Pipeline (Confirmed stage)
            $poCount = ProductionOrder::count() + 1;
            $orderNumber = 'PO-' . $year . '-' . str_pad($poCount, 3, '0', STR_PAD_LEFT);
            $firstItem = $quotation->items->first();

            ProductionOrder::create([
                'order_number' => $orderNumber,
                'invoice_id' => $invoice->id,
                'quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'category' => $quotation->category,
                'item_name' => $firstItem ? $firstItem->product_name : 'Custom Order',
                'total_qty' => $quotation->total_qty,
                'stage' => 'Confirmed',
                'priority' => 'Normal',
                'target_date' => $quotation->delivery_date ?? now()->addDays(7)->toDateString(),
                'notes' => 'Auto-queued from converted Quotation ' . $quotation->quotation_number,
            ]);

            // Update Quotation status
            $quotation->update(['status' => 'Invoiced']);

            // Update Customer balance
            $quotation->customer?->recalculateBalance();

            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Quotation {$quotation->quotation_number} converted to Invoice {$invoice->invoice_number} and queued to Production Kanban!");
        });
    }

    public function updateStatus(Request $request, Quotation $quotation)
    {
        $request->validate(['status' => 'required|in:Draft,Sent,Approved,Invoiced,Cancelled']);
        $quotation->update(['status' => $request->status]);

        return back()->with('success', "Quotation status updated to {$request->status}.");
    }
}
