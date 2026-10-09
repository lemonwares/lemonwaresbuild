@props(['paginator'])

@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = collect([1, $current - 1, $current, $current + 1, $last])
        ->filter(fn ($page) => $page >= 1 && $page <= $last)
        ->unique()
        ->sort()
        ->values();
@endphp

<footer class="acc-pager">
    <p class="acc-pager-summary">
        {{ __('account.showing_results', [
            'from' => number_format($paginator->firstItem() ?? 0),
            'to' => number_format($paginator->lastItem() ?? 0),
            'total' => number_format($paginator->total()),
        ]) }}
    </p>

    @if ($paginator->hasPages())
        <nav class="acc-pager-links" aria-label="{{ __('account.pagination') }}">
            @if ($paginator->onFirstPage())
                <span class="acc-page is-disabled" aria-disabled="true">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="acc-page" rel="prev" aria-label="{{ __('account.previous') }}">‹</a>
            @endif

            @foreach ($pages as $index => $page)
                @if ($index > 0 && $page - $pages[$index - 1] > 1)
                    <span class="acc-page is-gap">…</span>
                @endif
                @if ($page === $current)
                    <span class="acc-page is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="acc-page">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="acc-page" rel="next" aria-label="{{ __('account.next') }}">›</a>
            @else
                <span class="acc-page is-disabled" aria-disabled="true">›</span>
            @endif
        </nav>
    @endif
</footer>
