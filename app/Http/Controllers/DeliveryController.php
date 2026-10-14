<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\DeliveryNote;
use App\Models\DeliveryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = DeliveryNote::with(['customer', 'invoice', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('delivery_number', 'like', "%{$s}%")
                  ->orWhere('recipient_name', 'like', "%{$s}%")
                  ->orWhereHas('customer', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%");
                  });
            });
        }

        $deliveryNotes = $query->orderBy('id', 'desc')->paginate(15);
        $pendingCount = DeliveryNote::where('status', 'Pending')->count();
        $dispatchedCount = DeliveryNote::where('status', 'Dispatched')->count();
        $deliveredCount = DeliveryNote::where('status', 'Delivered')->count();

        return view('delivery.index', compact('deliveryNotes', 'pendingCount', 'dispatchedCount', 'deliveredCount'));
    }

    public function create(Request $request)
    {
        $invoices = SalesInvoice::with(['customer', 'items'])->orderBy('id', 'desc')->get();
        $selectedInvoice = null;

        if ($request->filled('invoice_id')) {
            $selectedInvoice = SalesInvoice::with(['customer', 'items'])->find($request->invoice_id);
        }

        $year = date('Y');
        $count = DeliveryNote::whereYear('created_at', $year)->count() + 1;
        $suggestedNumber = 'DN-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        return view('delivery.create', compact('invoices', 'selectedInvoice', 'suggestedNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:sales_invoices,id',
            'delivery_number' => 'required|string|unique:delivery_notes,delivery_number',
            'dispatch_date' => 'required|date',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:255',
            'driver_notes' => 'nullable|string',
            'status' => 'required|in:Pending,Dispatched,Delivered',
            'items' => 'required|array|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $invoice = SalesInvoice::findOrFail($request->invoice_id);

            $deliveryNote = DeliveryNote::create([
                'delivery_number' => $request->delivery_number,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'dispatch_date' => $request->dispatch_date,
                'recipient_name' => $request->recipient_name,
                'recipient_phone' => $request->recipient_phone,
                'driver_name' => $request->driver_name,
                'driver_notes' => $request->driver_notes,
                'status' => $request->status,
                'delivered_at' => $request->status === 'Delivered' ? now() : null,
            ]);

            foreach ($request->items as $item) {
                $sizeXs = (int)($item['size_xs'] ?? 0);
                $sizeS = (int)($item['size_s'] ?? 0);
                $sizeM = (int)($item['size_m'] ?? 0);
                $sizeL = (int)($item['size_l'] ?? 0);
                $sizeXl = (int)($item['size_xl'] ?? 0);
                $size2xl = (int)($item['size_2xl'] ?? 0);
                $size3xl = (int)($item['size_3xl'] ?? 0);
                $sizeCustom = (int)($item['size_custom'] ?? 0);

                $totalQty = $sizeXs + $sizeS + $sizeM + $sizeL + $sizeXl + $size2xl + $size3xl + $sizeCustom;
                if ($totalQty <= 0 && isset($item['total_qty']) && (int)$item['total_qty'] > 0) {
                    $totalQty = (int)$item['total_qty'];
                }

                $deliveryNote->items()->create([
                    'product_name' => $item['product_name'],
                    'size_xs' => $sizeXs,
                    'size_s' => $sizeS,
                    'size_m' => $sizeM,
                    'size_l' => $sizeL,
                    'size_xl' => $sizeXl,
                    'size_2xl' => $size2xl,
                    'size_3xl' => $size3xl,
                    'size_custom' => $sizeCustom,
                    'total_qty' => $totalQty,
                ]);
            }

            return redirect()->route('delivery.show', $deliveryNote)
                ->with('success', "Delivery Note {$deliveryNote->delivery_number} created successfully.");
        });
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'invoice', 'items']);

        return view('delivery.show', compact('deliveryNote'));
    }

    public function updateStatus(Request $request, DeliveryNote $deliveryNote)
    {
        $request->validate(['status' => 'required|in:Pending,Dispatched,Delivered']);

        $data = ['status' => $request->status];
        if ($request->status === 'Delivered' && !$deliveryNote->delivered_at) {
            $data['delivered_at'] = now();
        }

        $deliveryNote->update($data);

        return back()->with('success', "Delivery Note status updated to {$request->status}.");
    }
}
