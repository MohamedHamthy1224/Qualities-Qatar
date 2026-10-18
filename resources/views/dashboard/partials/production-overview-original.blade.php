        @if($productionCharts['total'])
            <div class="dashboard-production-split">
                <div class="dashboard-pie dashboard-production-visual" style="--chart-active-angle: {{ round($productionCharts['activePercent'] * 3.6, 4) }}deg" aria-hidden="true">
                    <span class="future-donut-center">
                        <strong>{{ number_format(100 - $productionCharts['activePercent'], 1) }}%</strong>
                        <span>complete</span>
                    </span>
                </div>
                <div class="dashboard-production-breakdown">
                    <p class="dashboard-pie-total"><strong>{{ $productionCharts['total'] }}</strong><span>Total jobs</span></p>
                    <ul class="dashboard-chart-legend" aria-label="Production job totals">
                        <li>
                            <a href="{{ route('production.index', ['status' => 'ongoing']) }}">
                                <span class="dashboard-chart-swatch" data-series="active" aria-hidden="true"></span>
                                <span class="dashboard-chart-legend-label">Ongoing<span class="dashboard-chart-percentage">{{ number_format($productionCharts['activePercent'], 1) }}% of jobs</span></span>
                                <strong>{{ $productionCharts['active'] }}</strong>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('production.index', ['status' => 'completed']) }}">
                                <span class="dashboard-chart-swatch" data-series="completed" aria-hidden="true"></span>
                                <span class="dashboard-chart-legend-label">Production complete<span class="dashboard-chart-percentage">{{ number_format(100 - $productionCharts['activePercent'], 1) }}% of jobs</span></span>
                                <strong>{{ $productionCharts['completed'] }}</strong>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <p class="dashboard-chart-summary">Production completion is tracked separately from delivery and payment.</p>
        @else
            <div class="dashboard-chart-empty">
                <div class="dashboard-pie" data-empty="true" aria-hidden="true"></div>
                <strong>No production jobs yet</strong>
                <p>The chart will show your saved production statuses.</p>
            </div>
        @endif
