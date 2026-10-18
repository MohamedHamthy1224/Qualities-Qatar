@props(['paginator', 'label' => 'records', 'perPageName' => null, 'pageSizes' => [10, 15, 20, 25, 50]])
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $windowPage = min($current, $last);
    $pages = collect([1, $last])->merge(range(max(1, $windowPage - 1), min($last, $windowPage + 1)))->unique()->sort()->values();
    $controlId = 'page-size-'.substr(md5($label.$paginator->getPageName()), 0, 10);
@endphp
<div class="ui-pagination" data-pagination-footer aria-label="{{ $label }} pagination">
    <p class="ui-pagination__summary" role="status" aria-live="polite" aria-atomic="true">Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} {{ $label }}</p>
    <div class="ui-pagination__actions">
        @if($perPageName)
            <form method="GET" action="{{ $paginator->path() }}" class="ui-pagination-size-form">
                @foreach(array_diff_key(request()->query(), array_flip([$perPageName, $paginator->getPageName()])) as $key => $value)
                    @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <label class="ui-pagination__per-page" for="{{ $controlId }}">Rows
                    <select class="ui-pagination__select" id="{{ $controlId }}" name="{{ $perPageName }}" data-server-page-size aria-label="Rows per page for {{ $label }}">
                        @foreach(collect($pageSizes)->push($paginator->perPage())->unique()->sort() as $size)
                            <option value="{{ $size }}" @selected($paginator->perPage() === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </label>
                <noscript><button type="submit" class="ui-pagination__button">Apply</button></noscript>
            </form>
        @endif
        <nav class="ui-pagination__pages" aria-label="{{ $label }} pages">
            @if($paginator->onFirstPage())<span class="ui-pagination__button" aria-disabled="true">Previous</span>
            @else<a class="ui-pagination__button" href="{{ $paginator->url(min($last, $current - 1)) }}" rel="prev">Previous</a>@endif
            @foreach($pages as $page)
                @if($loop->index && $page > $pages[$loop->index - 1] + 1)<span class="ui-pagination__ellipsis" aria-hidden="true">…</span>@endif
                @if($page === $current)<span class="ui-pagination__button ui-pagination__page" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                @else<a class="ui-pagination__button ui-pagination__page" href="{{ $paginator->url($page) }}" aria-label="Page {{ $page }}">{{ $page }}</a>@endif
            @endforeach
            @if($paginator->hasMorePages())<a class="ui-pagination__button" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else<span class="ui-pagination__button" aria-disabled="true">Next</span>@endif
        </nav>
    </div>
</div>
