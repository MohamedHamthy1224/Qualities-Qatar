<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\ProductionOrder;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['production_status' => 'nullable|in:ongoing,completed', 'per_page' => 'nullable|integer|in:8,10,15,20,25,50', 'page' => 'nullable|integer|min:1']);
        $productionStatus = $request->input('production_status') ?: 'ongoing';
        $activeOrdersCount = ProductionOrder::ongoing()->count();
        $completedOrdersCount = ProductionOrder::where('stage', 'Completed')->count();
        $stageCounts = ProductionOrder::selectRaw('stage, COUNT(*) as total')->groupBy('stage')->pluck('total', 'stage');
        $overviewStages = ['Confirmed', 'Cutting & Stitching', 'Embroidery / DTF Printing', 'Quality Check', 'Ready for Delivery', 'Completed'];
        $stages = array_filter(ProductionOrder::STAGES, fn ($label, $stage) => in_array($stage, $overviewStages, true) || $stageCounts->get($stage, 0) > 0, ARRAY_FILTER_USE_BOTH);
        $stages += $stageCounts->keys()->diff(array_keys(ProductionOrder::STAGES))->mapWithKeys(fn ($stage) => [$stage => $stage])->all();

        // Both charts describe the full production register, independently of the list below.
        $workload = [];
        foreach ($stages as $stage => $label) {
            $count = (int) $stageCounts->get($stage, 0);
            if ($stage !== 'Completed' && $count > 0) {
                $workload[] = ['stage' => $stage, 'label' => $label, 'count' => $count];
            }
        }
        $maxWorkload = $workload === [] ? 0 : max(array_column($workload, 'count'));
        $productionTotal = $activeOrdersCount + $completedOrdersCount;
        $productionCharts = [
            'workload' => array_map(fn ($entry) => $entry + [
                'percent' => round($entry['count'] / $maxWorkload * 100, 2),
            ], $workload),
            'max' => $maxWorkload,
            'active' => $activeOrdersCount,
            'completed' => $completedOrdersCount,
            'total' => $productionTotal,
            'activePercent' => $productionTotal > 0 ? round($activeOrdersCount / $productionTotal * 100, 2) : 0.0,
        ];

        $pendingQuotationsCount = Quotation::whereIn('status', ['Draft', 'Sent'])->count();
        // Count ready production jobs once; a pending delivery note is a separate dispatch record.
        $readyDeliveriesCount = ProductionOrder::where('stage', 'Ready for Delivery')->count();

        $overduePaymentsQAR = SalesInvoice::where('balance_due', '>', 0)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->sum('balance_due');

        $totalReceivablesQAR = SalesInvoice::where('balance_due', '>', 0)->sum('balance_due');
        $totalCollectedTodayQAR = CustomerReceipt::whereDate('payment_date', now()->toDateString())->sum('amount_received');

        $ordersQuery = ProductionOrder::with('customer', 'invoice.items', 'quotation.items');
        if ($productionStatus === 'completed') {
            $ordersQuery->where('stage', 'Completed')->orderByDesc('updated_at');
        } else {
            $ordersQuery->ongoing()->orderByRaw('target_date IS NULL')->orderBy('target_date');
        }
        $recentOrders = $ordersQuery->orderByDesc('id')->paginate((int) ($request->query('per_page') ?: 8))->withQueryString();

        // Recent Quotations
        $recentQuotations = Quotation::with('customer')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        // Customers list for quick actions modal
        $customers = Customer::orderBy('name')->get();

        return view('dashboard.index', compact(
            'activeOrdersCount',
            'completedOrdersCount',
            'productionStatus',
            'stageCounts',
            'stages',
            'productionCharts',
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
