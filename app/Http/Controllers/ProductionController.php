<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\Customer;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $stages = ProductionOrder::STAGES;

        $query = ProductionOrder::with(['customer', 'invoice', 'quotation']);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('item_name', 'like', "%{$s}%")
                  ->orWhereHas('customer', function ($cq) use ($s) {
                      $cq->where('name', 'like', "%{$s}%");
                  });
            });
        }

        $allOrders = $query->orderBy('target_date', 'asc')->get();

        // Group by stage for the Kanban Board
        $kanbanColumns = [];
        foreach ($stages as $key => $title) {
            $kanbanColumns[$key] = $allOrders->where('stage', $key);
        }

        $totalActive = $allOrders->where('stage', '!=', 'Completed')->count();
        $urgentCount = $allOrders->where('priority', 'Urgent')->where('stage', '!=', 'Completed')->count();

        return view('production.index', compact('kanbanColumns', 'stages', 'totalActive', 'urgentCount'));
    }

    /**
     * Update order stage (supports both AJAX drag-and-drop and traditional form post).
     */
    public function updateStage(Request $request, ProductionOrder $order)
    {
        $request->validate([
            'stage' => 'required|in:' . implode(',', array_keys(ProductionOrder::STAGES)),
            'notes' => 'nullable|string',
        ]);

        $order->update([
            'stage' => $request->stage,
            'notes' => $request->notes ?? $order->notes,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Order {$order->order_number} moved to {$request->stage}",
                'stage' => $request->stage,
            ]);
        }

        return back()->with('success', "Order {$order->order_number} updated to {$request->stage}.");
    }

    public function updatePriority(Request $request, ProductionOrder $order)
    {
        $request->validate(['priority' => 'required|in:Normal,High,Urgent']);
        $order->update(['priority' => $request->priority]);

        return back()->with('success', "Order {$order->order_number} priority updated to {$request->priority}.");
    }
}
