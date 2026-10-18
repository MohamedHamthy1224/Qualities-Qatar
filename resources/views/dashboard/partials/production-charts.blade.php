<div class="dashboard-charts-grid" aria-label="Production charts">
    <section data-chart="workload" class="dashboard-chart-card" id="dashboardWorkloadChart" aria-labelledby="dashboard-workload-title">
        <div class="dashboard-chart-heading">
            <div>
                <h2 id="dashboard-workload-title">Production workload</h2>
                <p>Ongoing jobs by current stage</p>
            </div>
            <span class="dashboard-chart-unit">{{ $productionCharts['active'] }} {{ $productionCharts['active'] === 1 ? 'job' : 'jobs' }}</span>
        </div>

        @if(count($productionCharts['workload']))
            <ul class="dashboard-workload" aria-label="Ongoing production jobs per stage">
                @foreach($productionCharts['workload'] as $item)
                    <li>
                        <a class="dashboard-workload-row" href="{{ route('production.index', ['status' => 'ongoing', 'stage' => $item['stage']]) }}" aria-label="{{ $item['label'] }}: {{ $item['count'] }} {{ $item['count'] === 1 ? 'job' : 'jobs' }}. View jobs.">
                            <span class="dashboard-workload-name">{{ $item['label'] }}</span>
                            <strong class="dashboard-workload-value">{{ $item['count'] }}</strong>
                            <span class="dashboard-workload-track" aria-hidden="true"><span style="--chart-bar-width: {{ $item['percent'] }}%"></span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="dashboard-chart-scale" aria-hidden="true"><span>0 jobs</span><span>{{ $productionCharts['max'] }} {{ $productionCharts['max'] === 1 ? 'job' : 'jobs' }}</span></div>
            <p class="dashboard-chart-summary">Select a bar to open its production jobs.</p>
        @else
            <div class="dashboard-chart-empty">
                <strong>No ongoing production jobs</strong>
                <p>New production jobs will appear here when an invoice is created.</p>
            </div>
        @endif
    </section>

    <section data-chart="production" class="dashboard-chart-card production-overview" id="dashboardProductionChart" aria-labelledby="dashboard-production-title" data-production-donut data-donut-style="interactive">
        <div class="dashboard-chart-heading production-overview__heading">
            <div>
                <h2 id="dashboard-production-title">Production overview</h2>
                <p>All production jobs · ongoing and complete</p>
            </div>
            <div class="production-overview__styles" role="group" aria-label="Production chart style" data-donut-style-controls hidden>
                <button type="button" data-donut-style-choice="interactive" aria-pressed="true">Interactive</button>
                <button type="button" data-donut-style-choice="original" aria-pressed="false">Original</button>
            </div>
        </div>
        <div data-donut-view="interactive">
            @include("dashboard.partials.production-overview-interactive")
        </div>
        <div data-donut-view="original" hidden>
            @include("dashboard.partials.production-overview-original")
        </div>
    </section>
</div>
