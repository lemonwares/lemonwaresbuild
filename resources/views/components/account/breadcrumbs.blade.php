@props(['items' => []])

<nav class="acc-crumbs" aria-label="{{ __('account.breadcrumbs') }}">
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
