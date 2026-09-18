@extends('layouts.account')

@section('title', __('account.nav_subscriptions') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.subscriptions_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_subscriptions')"
        :title="__('account.subscriptions_title')"
        :lede="__('account.subscriptions_lede')"
    />

    @if ($subscriptions->isEmpty())
        <div class="account-empty">
            <p class="account-empty-title">{{ __('account.subscriptions_empty') }}</p>
            <p class="account-empty-lede">{{ __('account.subscriptions_lede') }}</p>
        </div>
    @else
        {{-- Mobile / tablet: card list --}}
        <div class="account-list lg:hidden">
            @foreach ($subscriptions as $sub)
                <div class="account-list-item">
                    <div class="account-list-copy">
                        <p class="account-list-title">{{ $sub['label'] }}</p>
                        @if ($sub['domain'])
                            <p class="account-list-meta">{{ $sub['domain'] }}</p>
                        @endif
                        <p class="account-list-meta">
                            {{ $sub['status'] ?: '—' }}
                            · {{ $sub['billing_cycle'] ?: '—' }}
                            · {{ $sub['next_due'] ? \Illuminate\Support\Carbon::parse($sub['next_due'])->timezone(config('app.timezone'))->format('d M Y') : '—' }}
                        </p>
                    </div>
                    <div class="account-list-side">
                        @if ($sub['url'])
                            <a href="{{ $sub['url'] }}" class="account-btn-ghost">{{ __('account.manage') }}</a>
                        @endif
                        @if ($sub['renew_url'])
                            <form method="POST" action="{{ $sub['renew_url'] }}" data-submit-form>
                                @csrf
                                <x-ui.submit-button :label="__('account.renew')" :loading="__('account.starting_payment')" class="account-btn-primary" />
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop table --}}
        <section class="account-panel account-panel-flush hidden lg:block">
            <div class="account-table-wrap">
                <table class="account-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.subscription') }}</th>
                            <th>{{ __('account.status_label') }}</th>
                            <th>{{ __('account.billing_cycle') }}</th>
                            <th>{{ __('account.next_due') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $sub)
                            <tr>
                                <td>
                                    <strong>{{ $sub['label'] }}</strong>
                                    @if ($sub['domain'])
                                        <div class="account-list-meta">{{ $sub['domain'] }}</div>
                                    @endif
                                </td>
                                <td><span class="account-pill is-muted">{{ $sub['status'] ?: '—' }}</span></td>
                                <td>{{ $sub['billing_cycle'] ?: '—' }}</td>
                                <td>
                                    {{ $sub['next_due'] ? \Illuminate\Support\Carbon::parse($sub['next_due'])->timezone(config('app.timezone'))->format('d M Y') : '—' }}
                                </td>
                                <td>
                                    <div class="account-table-actions">
                                        @if ($sub['url'])
                                            <a href="{{ $sub['url'] }}" class="account-btn-ghost">{{ __('account.manage') }}</a>
                                        @endif
                                        @if ($sub['renew_url'])
                                            <form method="POST" action="{{ $sub['renew_url'] }}" data-submit-form>
                                                @csrf
                                                <x-ui.submit-button :label="__('account.renew')" :loading="__('account.starting_payment')" class="account-btn-primary" />
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
