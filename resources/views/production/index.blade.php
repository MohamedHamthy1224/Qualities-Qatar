@extends('layouts.app')

@section('title', 'Production Progress Tracker')
@section('page_title', 'Production Pipeline & Manufacturing Kanban')

@section('header_action')
    <div class="flex items-center gap-2">
        <span class="text-xs text-slate-500 font-medium">
            Active in Shop: <strong class="text-slate-900 font-mono-num">{{ $totalActive }} orders</strong>
            @if($urgentCount > 0)
                • <strong class="text-rose-600 font-bold font-mono-num">{{ $urgentCount }} URGENT</strong>
            @endif
        </span>
    </div>
@endsection

@section('content')
<div class="space-y-6 pt-2">

    <!-- Filters Bar -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-xs flex flex-col md:flex-row items-center justify-between gap-3">
        <form method="GET" action="{{ route('production.index') }}" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <div class="relative flex-1 sm:w-72">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order #, customer, uniform item..." class="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#0a192f] focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <select name="category" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 focus:outline-none">
                <option value="">All Categories</option>
                <option value="School Uniforms" {{ request('category') === 'School Uniforms' ? 'selected' : '' }}>School Uniforms</option>
                <option value="Corporate Uniforms" {{ request('category') === 'Corporate Uniforms' ? 'selected' : '' }}>Corporate Uniforms</option>
                <option value="Sportswear" {{ request('category') === 'Sportswear' ? 'selected' : '' }}>Sportswear</option>
                <option value="Embroidery" {{ request('category') === 'Embroidery' ? 'selected' : '' }}>Embroidery</option>
                <option value="DTF Printing" {{ request('category') === 'DTF Printing' ? 'selected' : '' }}>DTF Printing</option>
            </select>

            <select name="priority" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 focus:outline-none">
                <option value="">All Priorities</option>
                <option value="Urgent" {{ request('priority') === 'Urgent' ? 'selected' : '' }}>Urgent</option>
                <option value="High" {{ request('priority') === 'High' ? 'selected' : '' }}>High Priority</option>
                <option value="Normal" {{ request('priority') === 'Normal' ? 'selected' : '' }}>Normal</option>
            </select>

            <button type="submit" class="bg-slate-900 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-800">
                Filter
            </button>
            @if(request()->hasAny(['search', 'category', 'priority']))
                <a href="{{ route('production.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
            @endif
        </form>

        <div class="text-xs text-slate-500 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Drag cards between columns or use the dropdown to change stages</span>
        </div>
    </div>

    <!-- Kanban Pipeline Board (6 Columns) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5 pb-6 overflow-x-auto">
        @foreach($stages as $stageKey => $stageTitle)
            @php
                $orders = $kanbanColumns[$stageKey] ?? collect();
                $headerColor = match($stageKey) {
                    'Confirmed' => 'border-t-4 border-slate-500 bg-slate-50',
                    'Cutting & Stitching' => 'border-t-4 border-amber-500 bg-amber-50/50',
                    'Embroidery / DTF Printing' => 'border-t-4 border-purple-500 bg-purple-50/50',
                    'Quality Check' => 'border-t-4 border-sky-500 bg-sky-50/50',
                    'Ready for Delivery' => 'border-t-4 border-emerald-500 bg-emerald-50/50',
                    'Completed' => 'border-t-4 border-green-600 bg-green-50/40',
                    default => 'border-t-4 border-slate-500 bg-slate-50'
                };
            @endphp

            <!-- Kanban Column -->
            <div class="bg-slate-100/80 rounded-xl p-2.5 flex flex-col min-w-[240px] border border-slate-200/80 shadow-2xs kanban-column" data-stage="{{ $stageKey }}" ondragover="handleDragOver(event)" ondrop="handleDrop(event, '{{ $stageKey }}')">

                <!-- Column Header -->
                <div class="p-2 rounded-lg mb-2 {{ $headerColor }} flex items-center justify-between border-b border-slate-200 shadow-2xs">
                    <div class="min-w-0">
                        <h3 class="font-bold text-xs text-slate-800 truncate" title="{{ $stageTitle }}">{{ $stageTitle }}</h3>
                    </div>
                    <span class="w-5 h-5 rounded-full bg-white text-slate-800 flex items-center justify-center font-mono-num font-bold text-[10px] shadow-2xs">
                        {{ $orders->count() }}
                    </span>
                </div>

                <!-- Cards Container -->
                <div class="space-y-2.5 flex-1 min-h-[420px]" id="column-{{ Str::slug($stageKey) }}">
                    @forelse($orders as $order)
                        <div class="bg-white rounded-lg p-3 border border-slate-200 shadow-xs hover:shadow-md transition-all cursor-move kanban-card" draggable="true" ondragstart="handleDragStart(event, {{ $order->id }})" id="order-card-{{ $order->id }}">

                            <!-- Order Card Header -->
                            <div class="flex items-center justify-between gap-1 mb-1.5">
                                <span class="font-mono-num font-bold text-[11px] text-[#0a192f]">{{ $order->order_number }}</span>
                                @if($order->priority === 'Urgent')
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-rose-100 text-rose-700 animate-pulse">URGENT</span>
                                @elseif($order->priority === 'High')
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800">HIGH</span>
                                @endif
                            </div>

                            <!-- Customer & Uniform Item -->
                            <div class="font-bold text-xs text-slate-900 leading-tight">{{ $order->customer->name ?? 'Walk-in' }}</div>
                            <div class="text-[11px] text-slate-600 mt-1 font-medium">{{ $order->item_name }}</div>

                            <!-- Quantity Badge & Category -->
                            <div class="mt-2 flex items-center justify-between text-[10px] pt-2 border-t border-slate-100">
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono-num font-bold">
                                    {{ $order->total_qty }} pcs
                                </span>
                                <span class="text-slate-500 font-mono-num">
                                    Due: {{ $order->target_date ? $order->target_date->format('d M') : '—' }}
                                </span>
                            </div>

                            @if($order->notes)
                                <div class="mt-1.5 text-[10px] text-slate-500 italic bg-slate-50 p-1.5 rounded truncate" title="{{ $order->notes }}">
                                    "{{ $order->notes }}"
                                </div>
                            @endif

                            <!-- Stage Changer Dropdown (Instant Counter Staff Tool) -->
                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-[9px] text-slate-400 font-bold uppercase">Move Stage:</span>
                                <select onchange="updateOrderStageAjax({{ $order->id }}, this.value)" class="text-[10px] font-semibold border border-slate-200 rounded px-1.5 py-0.5 bg-slate-50 focus:outline-none">
                                    @foreach($stages as $sk => $st)
                                        <option value="{{ $sk }}" {{ $order->stage === $sk ? 'selected' : '' }}>{{ $sk }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                    @empty
                        <div class="h-28 border border-dashed border-slate-200 rounded-lg flex items-center justify-center text-slate-400 text-[10px] italic">
                            No orders in this stage
                        </div>
                    @endforelse
                </div>

            </div>
        @endforeach
    </div>

</div>

@push('scripts')
<script>
    let draggedOrderId = null;

    function handleDragStart(e, orderId) {
        draggedOrderId = orderId;
        e.dataTransfer.setData('text/plain', orderId);
        e.dataTransfer.effectAllowed = 'move';
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDrop(e, targetStage) {
        e.preventDefault();
        const orderId = e.dataTransfer.getData('text/plain') || draggedOrderId;
        if (orderId) {
            updateOrderStageAjax(orderId, targetStage);
        }
    }

    async function updateOrderStageAjax(orderId, stage) {
        try {
            const res = await fetch(`/production/${orderId}/stage`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ stage: stage })
            });
            const data = await res.json();
            if (data.success) {
                // Refresh board or reload smoothly
                window.location.reload();
            } else {
                alert('Could not update stage.');
            }
        } catch (err) {
            console.error('Failed to move stage:', err);
            // Fallback reload
            window.location.reload();
        }
    }
</script>
@endpush
@endsection
