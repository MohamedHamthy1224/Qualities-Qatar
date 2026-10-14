<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\InvoiceItem;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
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

        $invoices = $query->orderBy('id', 'desc')->paginate(15);
        $totalInvoiced = SalesInvoice::sum('grand_total');
        $totalOutstanding = SalesInvoice::sum('balance_due');
        $unpaidCount = SalesInvoice::where('status', 'Unpaid')->count();

        return view('invoices.index', compact('invoices', 'totalInvoiced', 'totalOutstanding', 'unpaidCount'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        $year = date('Y');
        $count = SalesInvoice::whereYear('created_at', $year)->count() + 1;
        $suggestedNumber = 'INV-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        return view('invoices.create', compact('customers', 'products', 'suggestedNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_number' => 'required|string|unique:sales_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $totalQty = 0;
            $subtotal = 0;
            $setupChargesTotal = (float)($request->setup_charges ?? 0);
            $printChargesTotal = (float)($request->print_charges ?? 0);
            $discountTotal = (float)($request->discount ?? 0);

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

            $invoice = SalesInvoice::create([
                'invoice_number' => $request->invoice_number,
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'total_qty' => $totalQty,
                'subtotal' => $subtotal,
                'discount' => $discountTotal,
                'setup_charges' => $setupChargesTotal,
                'print_charges' => $printChargesTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => 0.00,
                'balance_due' => $grandTotal,
                'status' => 'Unpaid',
                'notes' => $request->notes,
            ]);

            foreach ($preparedItems as $pi) {
                $invoice->items()->create($pi);
            }

            // Create initial Production Order on Kanban
            $year = date('Y');
            $poCount = ProductionOrder::count() + 1;
            $orderNumber = 'PO-' . $year . '-' . str_pad($poCount, 3, '0', STR_PAD_LEFT);
            $firstItem = $preparedItems[0]['product_name'] ?? 'Custom Order';

            ProductionOrder::create([
                'order_number' => $orderNumber,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'category' => 'Uniforms & Apparel',
                'item_name' => $firstItem,
                'total_qty' => $totalQty,
                'stage' => 'Confirmed',
                'priority' => 'Normal',
                'target_date' => $invoice->due_date ?? now()->addDays(7)->toDateString(),
                'notes' => 'Queued from Sales Invoice ' . $invoice->invoice_number,
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
