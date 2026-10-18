@php
    $dueDate = $order->target_date?->toDateString();
    $today = now()->timezone('Asia/Qatar')->toDateString();
    $isLate = $dueDate && $dueDate < $today && $order->stage !== 'Completed';
    $priorityTone = match($order->priority) {
        'Urgent' => 'urgent',
        'High' => 'high',
        default => 'normal',
    };
@endphp
<article class="production-job-card kanban-card" draggable="true" ondragstart="handleDragStart(event, {{ $order->id }})" id="order-card-{{ $order->id }}" aria-label="Production job {{ $order->order_number }}">
    <div class="production-job-topline">
        @if($order->invoice)
            <a class="production-job-number" href="{{ route('invoices.show', $order->invoice) }}">{{ $order->order_number }}</a>
        @elseif($order->quotation)
            <a class="production-job-number" href="{{ route('quotations.show', $order->quotation) }}">{{ $order->order_number }}</a>
        @else
            <span class="production-job-number">{{ $order->order_number }}</span>
        @endif
        @if($order->priority !== 'Normal')
            <span class="production-priority-badge" data-tone="{{ $priorityTone }}">{{ $order->priority }}</span>
        @endif
    </div>

    <h4 class="production-job-customer">{{ $order->customer->name ?? 'Walk-in' }}</h4>
    <p class="production-job-description">{{ $order->job_summary }}</p>

    <dl class="production-job-meta">
        <div>
            <dt>Quantity</dt>
            <dd>{{ $order->quantity_summary }}</dd>
        </div>
        <div class="production-job-due" @if($isLate) data-late="true" @endif>
            <dt>Due date</dt>
            <dd>
                @if($order->target_date)
                    <time datetime="{{ $dueDate }}">{{ $dueDate === $today ? 'Today' : $order->target_date->format('d M Y') }}</time>
                    @if($isLate)<span class="production-late-label">Overdue</span>@endif
                @else
                    Not set
                @endif
            </dd>
        </div>
    </dl>

    <details class="production-job-details">
        <summary>
            <span>Update job<span class="sr-only"> {{ $order->order_number }}</span></span>
            <svg class="production-details-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </summary>
        <div class="production-job-editor">
            @if($order->notes)
                <div class="production-job-note">
                    <span class="production-field-label">Notes</span>
                    <p>{{ $order->notes }}</p>
                </div>
            @endif
            <div class="production-job-field">
                <label for="stage-{{ $order->id }}">Production stage</label>
                <select id="stage-{{ $order->id }}" data-current-stage="{{ $order->stage }}" onchange="updateOrderStageAjax({{ $order->id }}, this.value, this)">
                    @foreach(\App\Models\ProductionOrder::STAGES as $key => $label)
                        <option value="{{ $key }}" @selected($order->stage === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <form class="production-job-field" method="POST" action="{{ route('production.priority', $order) }}">
                @csrf
                <label for="priority-{{ $order->id }}">Priority</label>
                <select id="priority-{{ $order->id }}" name="priority" onchange="this.form.submit()">
                    @foreach(\App\Models\ProductionOrder::PRIORITIES as $priority)
                        <option value="{{ $priority }}" @selected($order->priority === $priority)>{{ $priority }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </details>
</article>
