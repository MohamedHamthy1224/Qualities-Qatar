@extends('layouts.app')

@section('title', 'Production Progress')
@section('page_title', 'Production Progress')

@section('content')
<div class="production-page" data-production-page data-view="{{ $viewMode }}">
    <p class="production-secondary">Update each job’s production status. Delivery and payments are tracked separately.</p>
    <div id="productionUpdateError" class="hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-3 text-sm text-rose-800" role="alert">
        <span data-production-error-message></span>
        <a href="{{ request()->fullUrl() }}" class="underline font-semibold ml-2">Refresh jobs</a>
    </div>
    <div id="productionUpdateNotice" class="hidden rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800" role="status" aria-atomic="true"></div>

    <div id="productionFilterBar" class="production-filter-panel">
        <form id="productionFilters" method="GET" action="{{ route('production.index') }}" class="production-filters">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <input type="hidden" name="per_page" value="{{ $orders->perPage() }}">
            <div class="production-filter-field production-search">
                <label for="productionSearch">Search jobs</label>
                <input id="productionSearch" type="search" name="search" value="{{ request('search') }}" placeholder="Order #, customer or item">
            </div>
            <div class="production-filter-field">
                <label for="productionCategory">Category</label>
                <select id="productionCategory" name="category">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="production-filter-field">
                <label for="productionPriority">Priority</label>
                <select id="productionPriority" name="priority">
                    <option value="">All priorities</option>
                    @foreach(\App\Models\ProductionOrder::PRIORITIES as $priority)
                        <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}</option>
                    @endforeach
                </select>
            </div>
            <div class="production-filter-field">
                <label for="productionStatus">Jobs</label>
                <select id="productionStatus" name="status">
                    <option value="">All jobs</option>
                    <option value="ongoing" @selected(request('status') === 'ongoing')>Ongoing</option>
                    <option value="completed" @selected(request('status') === 'completed')>Production complete</option>
                </select>
            </div>
            <div class="production-filter-field">
                <label for="productionStage">Production status</label>
                <select id="productionStage" name="stage">
                    <option value="">All statuses</option>
                    @foreach($stages as $stage => $label)
                        <option value="{{ $stage }}" @selected(request('stage') === $stage)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="production-filter-actions">
                <button type="submit">Filter</button>
                @if(request()->hasAny(['search', 'category', 'priority', 'status', 'stage']))
                    <a href="{{ route('production.index', ['view' => $viewMode]) }}">Reset</a>
                @endif
            </div>
        </form>
        <div id="productionResults" class="production-results">
            <span>Results:</span>
            <strong data-production-total-active>{{ $totalActive }} ongoing</strong>
            <span aria-hidden="true">·</span>
            <strong data-production-total-completed>{{ $totalCompleted }} production complete</strong>
            <span data-production-urgent-group @if($urgentCount === 0) hidden @endif>
                <span aria-hidden="true">·</span>
                <strong data-production-urgent-count>{{ $urgentCount }} urgent</strong>
            </span>
        </div>
    </div>

    <section class="production-table-panel" aria-labelledby="productionTableTitle">
        <div class="production-table-heading">
            <h2 id="productionTableTitle" tabindex="-1">Production jobs</h2>
            <div class="production-table-tools">
                <p class="production-table-meta">Status options match each job’s categories.</p>
                <div class="production-view-switch" role="group" aria-label="Production view">
                    <a class="production-view-option" data-production-view="table" href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" @if($viewMode === 'table') aria-current="true" @endif>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M3 15h18M9 4v16"/></svg>
                        Table
                    </a>
                    <a class="production-view-option" data-production-view="grid" href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" @if($viewMode === 'grid') aria-current="true" @endif>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        Grid
                    </a>
                </div>
            </div>
        </div>
        <div class="production-table-wrap" tabindex="0" role="region" aria-label="Production jobs and status controls">
            <table class="production-table" id="productionJobsTable" role="table">
                <thead role="rowgroup">
                    <tr role="row">
                        <th scope="col" role="columnheader">Job</th>
                        <th scope="col" role="columnheader">Customer / Category</th>
                        <th scope="col" role="columnheader">Items / Quantity</th>
                        <th scope="col" role="columnheader">Target due</th>
                        <th scope="col" role="columnheader">Priority</th>
                        <th scope="col" role="columnheader">Production status</th>
                    </tr>
                </thead>
                <tbody id="productionTableRows" role="rowgroup">
                    @include('production.partials.table-rows', ['orders' => $orders])
                </tbody>
            </table>
        </div>
        <div id="productionPagination">
            @include('production.partials.pagination', ['orders' => $orders])
        </div>
    </section>
</div>
@endsection
