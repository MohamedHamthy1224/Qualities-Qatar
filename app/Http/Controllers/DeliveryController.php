<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\DeliveryNote;
use App\Models\DeliveryItem;
use App\Services\DocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $pagination = validator($request->query(), [
            'per_page' => 'nullable|integer|in:10,15,20,25,50',
            'page' => 'nullable|integer|min:1',
        ])->validate();

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

        $deliveryNotes = $query->orderBy('id', 'desc')
            ->paginate((int) ($pagination['per_page'] ?? 15))->withQueryString();
        $pendingCount = DeliveryNote::where('status', 'Pending')->count();
        $dispatchedCount = DeliveryNote::where('status', 'Dispatched')->count();
        $deliveredCount = DeliveryNote::where('status', 'Delivered')->count();

        return view('delivery.index', compact('deliveryNotes', 'pendingCount', 'dispatchedCount', 'deliveredCount'));
    }

    public function create(Request $request)
    {
        $invoices = SalesInvoice::with(['customer', 'items', 'deliveryNotes.items'])->orderBy('id', 'desc')->get();
        $selectedInvoice = null;

        if ($request->old('invoice_id', $request->input('invoice_id'))) {
            $selectedInvoice = $invoices->firstWhere('id', $request->old('invoice_id', $request->input('invoice_id')));
        }

        $invoiceOptions = $invoices->mapWithKeys(function ($invoice) {
            return [$invoice->id => array_merge(DeliveryNote::availabilityFor($invoice), [
                'customer_name' => $invoice->customer?->name ?? '',
                'customer_phone' => $invoice->customer?->phone ?? '',
            ])];
        });
        $suggestedNumber = DocumentNumber::next(DeliveryNote::class, 'delivery_number', 'DN-' . date('Y') . '-');

        return view('delivery.create', compact('invoices', 'selectedInvoice', 'suggestedNumber', 'invoiceOptions'));
    }

    public function store(Request $request)
    {
        $rules = [
            'invoice_id' => 'required|exists:sales_invoices,id',
            'delivery_number' => 'required|string|max:50|unique:delivery_notes,delivery_number',
            'dispatch_date' => 'required|date',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:255',
            'driver_notes' => 'nullable|string',
            'status' => 'required|in:Pending,Dispatched,Delivered',
            'items' => 'required|array|min:1',
            'items.*.invoice_item_id' => 'required|integer|distinct',
            'items.*.total_qty' => 'required|integer|min:0|max:2147483647',
        ];
        foreach (DeliveryItem::SIZE_FIELDS as $field) {
            $rules['items.*.' . $field] = 'nullable|integer|min:0|max:2147483647';
        }
        $validated = $request->validate($rules);

        return DocumentNumber::transaction(function () use ($validated) {
            // Lock the invoice before checking remaining quantities and creating any reservation.
            $invoice = SalesInvoice::whereKey($validated['invoice_id'])->lockForUpdate()->firstOrFail();
            if (DeliveryNote::where('delivery_number', $validated['delivery_number'])->exists()) {
                throw ValidationException::withMessages(['delivery_number' => 'This delivery note number has already been recorded.']);
            }
            $availability = DeliveryNote::availabilityFor($invoice);
            if ($availability['needs_review']) {
                throw ValidationException::withMessages(['invoice_id' => 'Historical delivery items do not match this invoice unambiguously. Review its existing delivery notes before creating another dispatch.']);
            }
            $availableItems = $availability['items']->keyBy('invoice_item_id');
            $deliveryItems = [];
            foreach ($validated['items'] as $index => $item) {
                $available = $availableItems->get($item['invoice_item_id']);
                if (!$available) {
                    throw ValidationException::withMessages(["items.$index.invoice_item_id" => 'This item does not belong to the selected invoice.']);
                }
                $sizes = [];
                foreach (DeliveryItem::SIZE_FIELDS as $field) {
                    $sizes[$field] = (int) ($item[$field] ?? 0);
                    if ($sizes[$field] > $available[$field]) {
                        throw ValidationException::withMessages(["items.$index.$field" => 'The quantity exceeds the remaining invoice quantity for this size.']);
                    }
                }
                $quantity = $available['has_sizes'] ? array_sum($sizes) : (int) $item['total_qty'];
                if ($quantity > $available['total_qty']) {
                    throw ValidationException::withMessages(["items.$index.total_qty" => 'The quantity exceeds the remaining invoice quantity.']);
                }
                if ($quantity === 0) {
                    continue;
                }
                $deliveryItems[] = array_merge($sizes, [
                    'invoice_item_id' => $available['invoice_item_id'],
                    'product_name' => $available['product_name'],
                    'total_qty' => $quantity,
                ]);
            }
            if (!$deliveryItems) {
                throw ValidationException::withMessages(['items' => 'Enter a positive quantity for at least one invoice item.']);
            }

            $deliveryNote = DeliveryNote::create([
                'delivery_number' => $validated['delivery_number'],
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'dispatch_date' => $validated['dispatch_date'],
                'recipient_name' => $validated['recipient_name'],
                'recipient_phone' => $validated['recipient_phone'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'driver_notes' => $validated['driver_notes'] ?? null,
                'status' => $validated['status'],
                'delivered_at' => $validated['status'] === 'Delivered' ? now() : null,
            ]);

            $deliveryNote->items()->createMany($deliveryItems);

            return redirect()->route('delivery.show', $deliveryNote)
                ->with('success', "Delivery Note {$deliveryNote->delivery_number} created successfully.");
        });
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'invoice', 'items.invoiceItem']);

        return view('delivery.show', compact('deliveryNote'));
    }

    public function updateStatus(Request $request, DeliveryNote $deliveryNote)
    {
        $request->validate(['status' => 'required|in:Pending,Dispatched,Delivered']);

        $data = ['status' => $request->status, 'delivered_at' => $request->status === 'Delivered' ? ($deliveryNote->delivered_at ?? now()) : null];

        $deliveryNote->update($data);

        return back()->with('success', "Delivery Note status updated to {$request->status}.");
    }
}
