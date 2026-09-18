@extends('layouts.client-auth')

@section('title', __('account.staff_accept_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.staff_accept_lede', ['email' => $invite->email]))

@section('content')
    <div class="auth-heading">
        <h1 class="font-bold tracking-tight text-black">{{ __('account.staff_accept_title') }}</h1>
        <p class="font-light text-on-blush/65">{{ __('account.staff_accept_lede', ['email' => $invite->email]) }}</p>
    </div>

    @if ($errors->any())
        <div class="auth-error" role="alert">
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('account.invites.accept', $invite->token) }}" class="auth-form-fields" data-submit-form>
        @csrf
        <div>
            <label class="mb-2 block text-sm font-semibold text-black">{{ __('account.email') }}</label>
            <input type="email" value="{{ $invite->email }}" disabled class="footer-input w-full rounded-xl border border-border bg-blush-soft px-4 py-3 text-on-blush/70">
        </div>
        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-black">{{ __('account.password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
        </div>
        <div>
            <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-black">{{ __('account.password_confirm') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
        </div>
        <x-ui.submit-button :label="__('account.staff_accept_action')" :loading="__('account.creating')" class="btn btn-primary w-full" />
    </form>
@endsection
