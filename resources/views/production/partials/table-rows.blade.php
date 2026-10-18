@forelse($orders as $order)
    @php
        $dueDate = $order->target_date?->toDateString();
        $today = now()->timezone('Asia/Qatar')->toDateString();
        $isLate = $dueDate && $dueDate < $today && $order->stage !== 'Completed';
        $workflowCategories = $order->workflow_categories;
        $workflowOptions = $order->workflow_stage_options;
        $isLegacyStage = !array_key_exists($order->stage, $workflowOptions);
        $updateQuery = array_replace(request()->query(), ['page' => $orders->currentPage()]);
        $progress = $order->workflow_progress;
    @endphp
    <tr class="production-order-row" id="production-order-{{ $order->id }}" data-order-id="{{ $order->id }}" data-order-number="{{ $order->order_number }}" data-stage="{{ $order->stage }}" role="row">
        <td data-label="Job" role="cell">
            @if($order->invoice)
                <a class="production-order-number" href="{{ route('invoices.show', $order->invoice) }}">{{ $order->order_number }}</a>
                <span class="production-order-reference">{{ $order->invoice->invoice_number }}</span>
            @elseif($order->quotation)
                <a class="production-order-number" href="{{ route('quotations.show', $order->quotation) }}">{{ $order->order_number }}</a>
                <span class="production-order-reference">{{ $order->quotation->quotation_number }}</span>
            @else
                <span class="production-order-number">{{ $order->order_number }}</span>
            @endif
            <div class="production-progress" data-current-stage="{{ $progress['current'] }}" data-total-stages="{{ $progress['total'] }}">
                @if($progress['current'] !== null)
                    <div class="production-progress-meta">
                        <span>{{ $progress['label'] }}</span>
                        <span>Stage {{ $progress['current'] }} of {{ $progress['total'] }}</span>
                    </div>
                    <div class="production-progress-track">
                        <progress value="{{ $progress['current'] }}" max="{{ $progress['total'] }}" aria-label="Workflow stage for {{ $order->order_number }}" aria-valuetext="{{ $progress['label'] }}, stage {{ $progress['current'] }} of {{ $progress['total'] }}"></progress>
                    </div>
                @else
                    <div class="production-progress-meta"><span>{{ $progress['label'] }}</span><span>Previous workflow</span></div>
                    <div class="production-progress-legacy" aria-hidden="true"></div>
                @endif
            </div>
        </td>
        <td data-label="Customer / Category" role="cell">
            <div class="production-customer-name">{{ $order->customer->name ?? 'Walk-in' }}</div>
            <div class="production-category-list">
                @forelse($workflowCategories as $category)
                    <span class="production-category">{{ $category }}</span>
                @empty
                    <span class="production-secondary">Uncategorized</span>
                @endforelse
            </div>
        </td>
        <td data-label="Items / Quantity" role="cell">
            <p class="production-items">{{ $order->job_summary }}</p>
            <span class="production-quantity">{{ $order->quantity_summary }}</span>
            @if($order->notes)
                <details class="production-notes">
                    <summary>Notes<span class="sr-only"> for {{ $order->order_number }}</span></summary>
                    <p>{{ $order->notes }}</p>
                </details>
            @endif
        </td>
        <td data-label="Target due" class="production-due" data-late="{{ $isLate ? 'true' : 'false' }}" role="cell">
            @if($order->target_date)
                <time datetime="{{ $dueDate }}">{{ $dueDate === $today ? 'Today' : $order->target_date->format('d M Y') }}</time>
                @if($isLate)<span class="production-secondary">Overdue</span>@endif
            @else
                <span class="production-secondary">Not set</span>
            @endif
        </td>
        <td data-label="Priority" role="cell">
            <form class="production-control-field" method="POST" action="{{ route('production.priority', ['order' => $order] + $updateQuery) }}" data-production-update="priority">
                @csrf
                <input type="hidden" name="previous_priority" value="{{ $order->priority }}">
                <label class="production-control-label" for="priority-{{ $order->id }}">Priority<span class="sr-only"> for {{ $order->order_number }}</span></label>
                <select class="production-priority-select" id="priority-{{ $order->id }}" name="priority" data-current-value="{{ $order->priority }}" data-priority="{{ $order->priority }}" aria-describedby="feedback-{{ $order->id }}">
                    @foreach(\App\Models\ProductionOrder::PRIORITIES as $priority)
                        <option value="{{ $priority }}" @selected($order->priority === $priority)>{{ $priority }}</option>
                    @endforeach
                </select>
                <button type="submit" class="production-save-fallback">Save priority</button>
            </form>
        </td>
        <td data-label="Production status" class="production-stage-field" role="cell">
            <form class="production-control-field" method="POST" action="{{ route('production.stage', ['order' => $order] + $updateQuery) }}" data-production-update="stage">
                @csrf
                <input type="hidden" name="previous_stage" value="{{ $order->stage }}">
                <label class="production-control-label" for="stage-{{ $order->id }}">Production status<span class="sr-only"> for {{ $order->order_number }}</span></label>
                <select class="production-stage-select" id="stage-{{ $order->id }}" name="stage" data-current-value="{{ $order->stage }}" aria-describedby="feedback-{{ $order->id }}">
                    @foreach($order->stage_options as $stage => $label)
                        <option value="{{ $stage }}" @selected($order->stage === $stage)>{{ $label }}{{ !array_key_exists($stage, $workflowOptions) ? ' (current, previous workflow)' : '' }}</option>
                    @endforeach
                </select>
                <button type="submit" class="production-save-fallback">Save status</button>
            </form>
            @if($workflowCategories->count() > 1)
                <p class="production-secondary">Combined workflow · applies to the whole job</p>
            @endif
            @if($isLegacyStage)
                <p class="production-legacy-hint">Existing status retained. Choose a relevant stage to continue.</p>
            @endif
            <p id="feedback-{{ $order->id }}" class="production-row-feedback" data-state=""></p>
        </td>
    </tr>
@empty
    <tr role="row">
        <td colspan="6" class="production-empty" role="cell">No production jobs match these filters. <a href="{{ route('production.index', ['view' => request()->query('view', 'table')]) }}">Reset filters</a></td>
    </tr>
@endforelse
