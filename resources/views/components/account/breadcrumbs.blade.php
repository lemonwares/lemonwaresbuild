@props(['items' => [], 'back' => null])

@php
    $previous = url()->previous();
    $backUrl = $back
        ?? (str_starts_with($previous, url('/account')) && $previous !== url()->current() ? $previous : route('account.show'));
@endphp

<nav class="acc-crumbs" aria-label="{{ __('account.breadcrumbs') }}">
    <a href="{{ $backUrl }}" class="acc-crumbs-back" title="{{ __('account.back') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5" />
            <path d="m12 19-7-7 7-7" />
        </svg>
        <span>{{ __('account.back') }}</span>
    </a>
    <span class="acc-crumbs-divider" aria-hidden="true"></span>
    <a href="{{ route('account.show') }}">{{ __('account.nav_overview') }}</a>
    @foreach ($items as $label => $url)
        <span class="acc-crumbs-sep" aria-hidden="true">/</span>
        @if ($loop->last || ! $url)
            <span class="acc-crumbs-current" aria-current="page">{{ $label }}</span>
        @else
            <a href="{{ $url }}">{{ $label }}</a>
        @endif
    @endforeach
</nav>
