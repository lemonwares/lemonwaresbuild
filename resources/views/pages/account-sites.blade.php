@extends('layouts.account')

@section('title', __('account.service_hosting') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.hosting_lede'))

@section('content')
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="section-label mb-2">{{ __('account.nav_hosting') }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ __('account.service_hosting') }}</h1>
            <p class="lede mt-2">{{ __('account.hosting_lede') }}</p>
        </div>
        <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="btn btn-ghost">{{ __('account.buy_hosting') }}</a>
    </div>

    <section class="rounded-3xl border border-border bg-white p-6">
        @forelse ($sharedHosting as $site)
            <article class="mb-3 rounded-2xl border border-border px-4 py-4 last:mb-0">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-black">{{ $site->displayName() }}</p>
                        <p class="mt-1 text-sm text-on-blush/65">{{ $site->plan_name }}{{ $site->spec_label ? ' · ' . $site->spec_label : '' }}</p>
                    </div>
                    <p class="text-sm font-semibold text-rose">{{ $site->statusLabel() }}</p>
                </div>
                <div class="mt-3 flex flex-wrap gap-3">
                    <a href="{{ route('account.hosting.show', $site) }}" class="text-sm font-semibold text-rose hover:underline">{{ __('account.view_service') }}</a>
                    @if ($site->panelUrl())
                        <a href="{{ $site->panelUrl() }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-rose hover:underline">{{ __('account.open_panel') }}</a>
                    @endif
                </div>
            </article>
        @empty
            <p class="body-text">{{ __('account.no_hosting') }}</p>
        @endforelse
    </section>
@endsection
