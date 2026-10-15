<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\ProductionOrder;
use App\Models\DeliveryNote;
use App\Models\CustomerReceipt;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // High-priority daily operational metrics
        $todayOrdersCount = ProductionOrder::whereDate('created_at', now()->toDateString())->count();
        if ($todayOrdersCount === 0) {
            // Count active orders in pipeline for display if none created today
            $todayOrdersCount = ProductionOrder::whereNotIn('stage', ['Completed'])->count();
        }

        $pendingQuotationsCount = Quotation::whereIn('status', ['Draft', 'Sent'])->count();
        $readyDeliveriesCount = ProductionOrder::where('stage', 'Ready for Delivery')->count()
            + DeliveryNote::where('status', 'Pending')->count();

        $overduePaymentsQAR = SalesInvoice::where('balance_due', '>', 0)
            ->where(function ($q) {
                $q->where('due_date', '<', now()->toDateString())
                  ->orWhereNull('due_date');
            })
            ->sum('balance_due');

        $totalReceivablesQAR = SalesInvoice::where('balance_due', '>', 0)->sum('balance_due');
        $totalCollectedTodayQAR = CustomerReceipt::whereDate('payment_date', now()->toDateString())->sum('amount_received');

        // Recent Active Orders list
        $recentOrders = ProductionOrder::with('customer', 'invoice', 'quotation')
            ->orderByRaw("CASE stage 
                WHEN 'Confirmed' THEN 1 
                WHEN 'Cutting & Stitching' THEN 2 
                WHEN 'Embroidery / DTF Printing' THEN 3 
                WHEN 'Quality Check' THEN 4 
                WHEN 'Ready for Delivery' THEN 5 
                ELSE 6 END")
            ->orderBy('id', 'desc')
            ->take(8)
            ->get();

        // Recent Quotations
        $recentQuotations = Quotation::with('customer')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Customers list for quick actions modal
        $customers = Customer::orderBy('name')->get();

        return view('dashboard.index', compact(
            'todayOrdersCount',
            'pendingQuotationsCount',
            'readyDeliveriesCount',
            'overduePaymentsQAR',
            'totalReceivablesQAR',
            'totalCollectedTodayQAR',
            'recentOrders',
            'recentQuotations',
            'customers'
        ));
    }
}
