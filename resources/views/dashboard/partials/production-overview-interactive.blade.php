@if($productionCharts['total'])
    @php
        // Presentation only: use the exact percentages already used by the original chart.
        $completedPercent = 100 - $productionCharts['activePercent'];
        $donutSeries = [
            ['key' => 'ongoing', 'label' => 'Ongoing', 'count' => $productionCharts['active'], 'percent' => $productionCharts['activePercent'], 'offset' => 0],
            ['key' => 'completed', 'label' => 'Production complete', 'count' => $productionCharts['completed'], 'percent' => $completedPercent, 'offset' => $productionCharts['activePercent']],
        ];
    @endphp
    <div class="production-overview__body">
        <div class="production-donut">
            <div class="production-donut__visual">
                <svg class="production-donut__svg" viewBox="0 0 240 240" role="group" aria-label="Production jobs by status">
                    <circle class="production-donut__track" cx="120" cy="120" r="86" fill="none" stroke-width="30" aria-hidden="true" />
                    <g transform="rotate(-90 120 120)">
                    @foreach($donutSeries as $series)
                        @if($series['count'] > 0)
                            <a class="production-donut__segment" href="{{ route('production.index', ['status' => $series['key']]) }}" tabindex="0"
                                data-donut-series="{{ $series['key'] }}" data-donut-label="{{ $series['label'] }}" data-donut-count="{{ $series['count'] }}" data-donut-percent="{{ number_format($series['percent'], 1) }}"
                                aria-label="{{ $series['label'] }}: {{ $series['count'] }} {{ $series['count'] === 1 ? 'job' : 'jobs' }}, {{ number_format($series['percent'], 1) }}% of total. View production jobs.">
                                <circle class="production-donut__arc" cx="120" cy="120" r="86" fill="none" stroke-width="30" pathLength="100"
                                    stroke-dasharray="{{ $series['percent'] }} {{ 100 - $series['percent'] }}" stroke-dashoffset="{{ -$series['offset'] }}" />
                            </a>
                        @endif
                    @endforeach
                    </g>
                </svg>
                <div class="production-donut__center" aria-hidden="true">
                    <strong>{{ number_format($completedPercent, 1) }}<span>%</span></strong>
                    <span>complete</span>
                </div>
            </div>
            <div class="production-donut__feedback">
                <div class="production-donut__tooltip" id="production-donut-tooltip" role="tooltip" data-donut-tooltip hidden>
                    <span class="production-donut__tooltip-label" data-donut-tooltip-label></span>
                    <strong data-donut-tooltip-count></strong>
                    <span data-donut-tooltip-percent></span>
                </div>
                <span class="production-donut__hint" data-donut-hint>Select a status to view jobs</span>
            </div>
        </div>
        <div class="production-overview__breakdown">
            <p class="production-overview__total"><strong>{{ number_format($productionCharts['total']) }}</strong><span>Total jobs</span></p>
            <ul class="production-overview__legend" aria-label="Production job totals">
                @foreach($donutSeries as $series)
                    <li>
                        <a href="{{ route('production.index', ['status' => $series['key']]) }}" data-donut-series="{{ $series['key'] }}"
                            data-donut-label="{{ $series['label'] }}" data-donut-count="{{ $series['count'] }}" data-donut-percent="{{ number_format($series['percent'], 1) }}"
                            aria-label="{{ $series['label'] }}: {{ $series['count'] }} {{ $series['count'] === 1 ? 'job' : 'jobs' }}, {{ number_format($series['percent'], 1) }}% of total. View production jobs.">
                            <span class="production-overview__swatch" aria-hidden="true"></span>
                            <span class="production-overview__legend-copy"><span>{{ $series['label'] }}</span><small>{{ number_format($series['percent'], 1) }}% of jobs</small></span>
                            <strong>{{ number_format($series['count']) }}</strong>
                            <svg class="production-overview__arrow" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <p class="dashboard-chart-summary production-overview__note">Production completion is tracked separately from delivery and payment.</p>
@else
    <div class="production-overview__empty">
        <svg viewBox="0 0 240 240" class="production-overview__empty-ring" aria-hidden="true"><circle cx="120" cy="120" r="86" fill="none" stroke-width="30" /></svg>
        <div><strong>No production jobs yet</strong><p>Your saved production statuses will appear here.</p></div>
    </div>
@endif
