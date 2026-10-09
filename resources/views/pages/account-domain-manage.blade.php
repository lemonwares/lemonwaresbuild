@extends('layouts.account')

@section('title', $domain->domain . ' — ' . config('site.short_name'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_domains')"
        :title="$domain->domain"
        :lede="$domain->status . ($domain->expiry_date ? ' · ' . __('account.domain_expires_on', ['date' => $domain->expiry_date->format('d M Y')]) : '')"
        :back-href="route('account.domains.index')"
        :back-label="__('account.nav_domains')"
    />

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-600/20 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif
    @if (session('epp_code'))
        <p class="mb-5 rounded-xl border border-border bg-white px-4 py-3 text-sm">
            {{ __('account.domain_epp_code') }}: <span class="account-mono" style="user-select:all">{{ session('epp_code') }}</span>
        </p>
    @endif
    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <div class="account-page-stack">
        <section class="account-panel">
            <dl class="account-dl">
                <div>
                    <dt>{{ __('account.status_label') }}</dt>
                    <dd><span class="account-pill is-muted">{{ $domain->status }}</span></dd>
                </div>
                <div>
                    <dt>{{ __('account.domain_registered_on') }}</dt>
                    <dd>{{ $domain->registration_date?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.domain_expiry') }}</dt>
                    <dd>{{ $domain->expiry_date?->format('d M Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.domain_renewal_price') }}</dt>
                    <dd>{{ $domain->recurring_amount !== null ? number_format((float) $domain->recurring_amount, 2) : '—' }}</dd>
                </div>
            </dl>
        </section>

        @if (! $manageable)
            <div class="account-empty">
                <p class="account-empty-title">{{ __('account.domain_not_manageable') }}</p>
                <p class="account-empty-lede">{{ __('account.domain_not_manageable_lede') }}</p>
            </div>
        @else
            <section class="account-panel">
                <h2 class="account-panel-title">{{ __('account.domain_nameservers') }}</h2>
                <p class="account-panel-lede">{{ __('account.domain_nameservers_lede') }}</p>
                <form method="POST" action="{{ route('account.domains.nameservers', $domain->whmcs_domain_id) }}" class="mt-5">
                    @csrf
                    <div class="account-form-grid">
                        @foreach (range(1, 5) as $n)
                            <div class="account-field">
                                <label for="ns{{ $n }}">{{ __('account.domain_nameserver', ['n' => $n]) }}</label>
                                <input id="ns{{ $n }}" name="ns{{ $n }}" type="text" value="{{ old('ns' . $n, $nameservers[$n - 1] ?? '') }}" @required($n <= 2) autocomplete="off" spellcheck="false">
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5">
                        <button type="submit" class="account-btn-primary">{{ __('account.domain_save') }}</button>
                    </div>
                </form>
            </section>

            <section class="account-panel">
                <h2 class="account-panel-title">{{ __('account.domain_settings') }}</h2>
                <div class="account-list mt-5">
                    <div class="account-list-item">
                        <div class="account-list-copy">
                            <p class="account-list-title">{{ __('account.domain_auto_renew') }}</p>
                            <p class="account-list-meta">{{ $domain->auto_renew ? __('account.domain_on') : __('account.domain_off') }}</p>
                        </div>
                        <form method="POST" action="{{ route('account.domains.auto-renew', $domain->whmcs_domain_id) }}" class="account-list-side">
                            @csrf
                            <input type="hidden" name="auto_renew" value="{{ $domain->auto_renew ? 0 : 1 }}">
                            <button type="submit" class="account-btn-ghost">{{ $domain->auto_renew ? __('account.domain_turn_off') : __('account.domain_turn_on') }}</button>
                        </form>
                    </div>

                    @if ($locked !== null)
                        <div class="account-list-item">
                            <div class="account-list-copy">
                                <p class="account-list-title">{{ __('account.domain_transfer_lock') }}</p>
                                <p class="account-list-meta">{{ $locked ? __('account.domain_locked') : __('account.domain_unlocked') }}</p>
                            </div>
                            <form method="POST" action="{{ route('account.domains.lock', $domain->whmcs_domain_id) }}" class="account-list-side">
                                @csrf
                                <input type="hidden" name="locked" value="{{ $locked ? 0 : 1 }}">
                                <button type="submit" class="account-btn-ghost">{{ $locked ? __('account.domain_unlock') : __('account.domain_lock') }}</button>
                            </form>
                        </div>
                    @endif

                    <div class="account-list-item">
                        <div class="account-list-copy">
                            <p class="account-list-title">{{ __('account.domain_epp_code') }}</p>
                            <p class="account-list-meta">{{ __('account.domain_epp_lede') }}</p>
                        </div>
                        <form method="POST" action="{{ route('account.domains.epp', $domain->whmcs_domain_id) }}" class="account-list-side">
                            @csrf
                            <button type="submit" class="account-btn-ghost">{{ __('account.domain_epp_request') }}</button>
                        </form>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection
