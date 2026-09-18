@extends('layouts.account')

@section('title', __('account.nav_staff') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.staff_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_staff')"
        :title="__('account.staff_title')"
        :lede="__('account.staff_lede')"
    />

    <div class="account-page-stack">
        @if ($errors->any())
            <p class="rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
        @endif

        <section class="account-metrics" aria-label="{{ __('account.nav_staff') }}">
            <div class="account-metric">
                <span class="account-metric-label">{{ __('account.staff_members') }}</span>
                <span class="account-metric-value">{{ $staff->count() }}</span>
                <span class="account-metric-meta">{{ __('account.staff_lede') }}</span>
            </div>
            <div class="account-metric">
                <span class="account-metric-label">{{ __('account.staff_pending') }}</span>
                <span class="account-metric-value">{{ $invites->count() }}</span>
                <span class="account-metric-meta">{{ __('account.staff_invite_lede') }}</span>
            </div>
        </section>

        <section class="account-panel">
            <h2 class="account-panel-title">{{ __('account.staff_invite_title') }}</h2>
            <p class="account-panel-lede">{{ __('account.staff_invite_lede') }}</p>

            <form method="POST" action="{{ route('account.staff.store') }}" class="mt-5 space-y-5" data-submit-form>
                @csrf
                <div class="account-form-grid">
                    <div class="account-field">
                        <label for="name">{{ __('account.name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name">
                    </div>
                    <div class="account-field">
                        <label for="email">{{ __('account.email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
                    </div>
                </div>

                <fieldset>
                    <legend class="account-label mb-3 block text-sm font-semibold text-black">{{ __('account.staff_permissions') }}</legend>
                    <div class="account-check-grid">
                        @foreach ($permissions as $key => $label)
                            @if ($key === 'staff')
                                @continue
                            @endif
                            <label class="account-check">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(collect(old('permissions', ['overview', 'products', 'subscriptions', 'invoices', 'activity', 'notifications']))->contains($key)) class="rounded border-border text-rose focus:ring-rose">
                                <span class="text-sm text-black">{{ __('account.permission_'.$key) }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <x-ui.submit-button :label="__('account.staff_send_invite')" :loading="__('account.sending')" class="account-btn-primary" />
            </form>
        </section>

        <section class="account-panel account-panel-flush">
            <div class="account-panel-toolbar">
                <div>
                    <h2 class="account-panel-title">{{ __('account.staff_members') }}</h2>
                    <p class="account-panel-lede">{{ __('account.staff_lede') }}</p>
                </div>
            </div>

            @if ($staff->isEmpty())
                <div class="account-panel-pad">
                    <div class="account-empty">
                        <p class="account-empty-title">{{ __('account.staff_empty') }}</p>
                    </div>
                </div>
            @else
                <div class="account-list p-4 lg:hidden">
                    @foreach ($staff as $member)
                        <div class="account-list-item">
                            <div class="account-list-copy">
                                <p class="account-list-title">{{ $member->name }}</p>
                                <p class="account-list-meta">{{ $member->email }}</p>
                                <p class="account-list-meta">{{ count($member->account_permissions ?? []) }} {{ __('account.staff_permissions') }}</p>
                            </div>
                            <form method="POST" action="{{ route('account.staff.destroy', $member) }}" data-submit-form onsubmit="return confirm(@json(__('account.staff_remove_confirm')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="account-btn-danger">{{ __('account.staff_remove') }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="account-table-wrap hidden lg:block">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>{{ __('account.name') }}</th>
                                <th>{{ __('account.email') }}</th>
                                <th>{{ __('account.staff_permissions') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($staff as $member)
                                <tr>
                                    <td><strong>{{ $member->name }}</strong></td>
                                    <td>{{ $member->email }}</td>
                                    <td><span class="account-pill is-muted">{{ count($member->account_permissions ?? []) }}</span></td>
                                    <td>
                                        <div class="account-table-actions">
                                            <form method="POST" action="{{ route('account.staff.destroy', $member) }}" data-submit-form onsubmit="return confirm(@json(__('account.staff_remove_confirm')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="account-btn-danger">{{ __('account.staff_remove') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="account-panel account-panel-flush">
            <div class="account-panel-toolbar">
                <div>
                    <h2 class="account-panel-title">{{ __('account.staff_pending') }}</h2>
                    <p class="account-panel-lede">{{ __('account.staff_invite_lede') }}</p>
                </div>
            </div>

            @if ($invites->isEmpty())
                <div class="account-panel-pad">
                    <div class="account-empty">
                        <p class="account-empty-title">{{ __('account.staff_no_invites') }}</p>
                    </div>
                </div>
            @else
                <div class="account-list p-4 lg:hidden">
                    @foreach ($invites as $invite)
                        <div class="account-list-item">
                            <div class="account-list-copy">
                                <p class="account-list-title">{{ $invite->name }}</p>
                                <p class="account-list-meta">{{ $invite->email }}</p>
                                <p class="account-list-meta">{{ __('account.staff_expires') }}: {{ $invite->expires_at?->timezone(config('app.timezone'))->format('d M Y') }}</p>
                            </div>
                            <form method="POST" action="{{ route('account.staff.invites.destroy', $invite) }}" data-submit-form>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="account-btn-ghost">{{ __('account.staff_cancel_invite') }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="account-table-wrap hidden lg:block">
                    <table class="account-table">
                        <thead>
                            <tr>
                                <th>{{ __('account.name') }}</th>
                                <th>{{ __('account.email') }}</th>
                                <th>{{ __('account.staff_expires') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invites as $invite)
                                <tr>
                                    <td><strong>{{ $invite->name }}</strong></td>
                                    <td>{{ $invite->email }}</td>
                                    <td>{{ $invite->expires_at?->timezone(config('app.timezone'))->format('d M Y') }}</td>
                                    <td>
                                        <div class="account-table-actions">
                                            <form method="POST" action="{{ route('account.staff.invites.destroy', $invite) }}" data-submit-form>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="account-btn-ghost">{{ __('account.staff_cancel_invite') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
