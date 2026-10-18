<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductionOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);

        return view('production.index', $this->tableData($filters));
    }

    public function updateStage(Request $request, ProductionOrder $order)
    {
        $validated = $request->validate([
            'stage' => 'required|string|max:40',
            'previous_stage' => 'sometimes|string|max:40',
            'notes' => 'nullable|string|max:10000',
        ]);
        $filters = $this->validatedFilters($request);

        $order = DB::transaction(function () use ($order, $validated) {
            $lockedOrder = ProductionOrder::with(['customer', 'invoice.items', 'quotation.items'])
                ->lockForUpdate()->findOrFail($order->id);

            if (isset($validated['previous_stage']) && $validated['previous_stage'] !== $lockedOrder->stage) {
                abort(409, 'This job was updated by another user. Refresh the jobs before changing its status.');
            }

            validator(['stage' => $validated['stage']], [
                'stage' => ['required', Rule::in(array_keys($lockedOrder->stage_options))],
            ], ['stage.in' => 'Choose a production status that applies to this job’s categories.'])->validate();

            $attributes = ['stage' => $validated['stage']];
            if (array_key_exists('notes', $validated)) {
                $attributes['notes'] = $validated['notes'];
            }
            $lockedOrder->fill($attributes);
            if ($lockedOrder->isDirty()) {
                $lockedOrder->save();
            }

            return $lockedOrder;
        });

        $message = "Order {$order->order_number} moved to {$order->stage_label}.";
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($this->updatePayload($order, $filters, $message));
        }

        return back()->with('success', $message);
    }

    public function updatePriority(Request $request, ProductionOrder $order)
    {
        $validated = $request->validate([
            'priority' => ['required', Rule::in(ProductionOrder::PRIORITIES)],
            'previous_priority' => ['sometimes', Rule::in(ProductionOrder::PRIORITIES)],
        ]);
        $filters = $this->validatedFilters($request);

        $order = DB::transaction(function () use ($order, $validated) {
            $lockedOrder = ProductionOrder::with(['customer', 'invoice.items', 'quotation.items'])
                ->lockForUpdate()->findOrFail($order->id);
            if (isset($validated['previous_priority']) && $validated['previous_priority'] !== $lockedOrder->priority) {
                abort(409, 'This job’s priority was updated by another user. Refresh the jobs before trying again.');
            }
            $lockedOrder->fill(['priority' => $validated['priority']]);
            if ($lockedOrder->isDirty()) {
                $lockedOrder->save();
            }

            return $lockedOrder;
        });

        $message = "Order {$order->order_number} priority updated to {$order->priority}.";
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($this->updatePayload($order, $filters, $message));
        }

        return back()->with('success', $message);
    }

    private function categories()
    {
        return collect(Product::CATEGORIES)->push('Multi-Category')
            ->merge(ProductionOrder::distinct()->pluck('category'))->filter()->unique()->values();
    }

    private function filterStages(): array
    {
        $historical = ProductionOrder::distinct()->pluck('stage')->filter()
            ->mapWithKeys(fn ($stage) => [$stage => ProductionOrder::STAGES[$stage] ?? $stage])->all();

        return ProductionOrder::STAGES + $historical;
    }

    private function validatedFilters(Request $request): array
    {
        // Query filters must not be confused with a posted row's stage or priority.
        return validator($request->query(), [
            'search' => 'nullable|string|max:255',
            'category' => ['nullable', Rule::in($this->categories()->all())],
            'priority' => ['nullable', Rule::in(ProductionOrder::PRIORITIES)],
            'status' => 'nullable|in:ongoing,completed',
            // A historical status filter remains valid after its final job moves on.
            'stage' => 'nullable|string|max:40',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|in:8,10,15,20,25,50',
            'view' => 'nullable|in:table,grid',
        ])->validate();
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = ProductionOrder::query();
        if (filled($filters['category'] ?? null)) {
            $category = $filters['category'];
            $query->where(function ($q) use ($category) {
                $q->where('category', $category)
                    ->orWhereHas('invoice.items', fn ($items) => $items->where('category', $category))
                    ->orWhereHas('quotation.items', fn ($items) => $items->where('category', $category));
            });
        }
        if (filled($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }
        if (($filters['status'] ?? null) === 'ongoing') {
            $query->ongoing();
        } elseif (($filters['status'] ?? null) === 'completed') {
            $query->where('stage', 'Completed');
        }
        if (filled($filters['stage'] ?? null)) {
            $query->where('stage', $filters['stage']);
        }
        if (filled($filters['search'] ?? null)) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('item_name', 'like', "%{$search}%")
                    ->orWhereHas('invoice.items', fn ($items) => $items->where('product_name', 'like', "%{$search}%"))
                    ->orWhereHas('quotation.items', fn ($items) => $items->where('product_name', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function tableData(array $filters, bool $clampPage = false): array
    {
        $query = $this->filteredQuery($filters);
        $stageCounts = (clone $query)->selectRaw('stage, COUNT(*) as total')->groupBy('stage')->pluck('total', 'stage');
        $totalCompleted = (int) $stageCounts->get('Completed', 0);
        $totalActive = (int) $stageCounts->sum() - $totalCompleted;
        $urgentCount = (clone $query)->ongoing()->where('priority', 'Urgent')->count();

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 20);
        $lastPage = max(1, (int) ceil($stageCounts->sum() / $perPage));
        if ($clampPage) {
            $page = min($page, $lastPage);
        }
        $orders = (clone $query)->with(['customer', 'invoice.items', 'quotation.items'])
            ->orderByRaw('target_date IS NULL')->orderBy('target_date')->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withPath(route('production.index'))->appends(array_diff_key($filters, ['page' => true]));

        $stages = $this->filterStages();
        if (filled($filters['stage'] ?? null)) {
            $stages[$filters['stage']] ??= $filters['stage'];
        }

        return [
            'orders' => $orders,
            'viewMode' => $filters['view'] ?? 'table',
            'stages' => $stages,
            'categories' => $this->categories(),
            'stageCounts' => $stageCounts,
            'totalActive' => $totalActive,
            'totalCompleted' => $totalCompleted,
            'urgentCount' => $urgentCount,
        ];
    }

    private function updatePayload(ProductionOrder $order, array $filters, string $message): array
    {
        $data = $this->tableData($filters, true);

        return [
            'success' => true,
            'message' => $message,
            'stage' => $order->stage,
            'stage_label' => $order->stage_label,
            'stage_options' => $order->stage_options,
            'priority' => $order->priority,
            'stage_counts' => $data['stageCounts'],
            'totalActive' => $data['totalActive'],
            'totalCompleted' => $data['totalCompleted'],
            'urgentCount' => $data['urgentCount'],
            'matches_filters' => $this->filteredQuery($filters)->whereKey($order->id)->exists(),
            'rows_html' => view('production.partials.table-rows', ['orders' => $data['orders']])->render(),
            'pagination_html' => view('production.partials.pagination', ['orders' => $data['orders']])->render(),
            'page_url' => $data['orders']->url($data['orders']->currentPage()),
        ];
    }
}
