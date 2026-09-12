@extends('layouts.client-auth')

@section('title', __('account.login_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.login_lede'))

@section('content')
    <div class="mx-auto max-w-md rounded-4xl border border-border bg-white p-6 sm:p-8">
        <p class="section-label mb-2">{{ __('account.client_area') }}</p>
        <h1 class="heading">{{ __('account.login_title') }}</h1>
        <p class="lede mt-3">{{ __('account.login_lede') }}</p>

        <x-ui.flash show-status class="mt-6" />

        @if ($errors->any())
            <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5" data-submit-form>
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-black">{{ __('account.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-black">{{ __('account.password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
            </div>
            <label class="flex items-center gap-2 text-sm text-on-blush/80">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-border">
                {{ __('account.remember') }}
            </label>
            <x-ui.submit-button :label="__('account.sign_in')" :loading="__('account.signing_in')" class="btn btn-primary w-full" />
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm font-semibold">
            <a href="{{ route('password.request') }}" class="text-rose hover:underline">{{ __('account.forgot') }}</a>
            <a href="{{ route('register') }}" class="text-on-blush hover:text-rose">{{ __('account.no_account') }}</a>
        </div>
    </div>
@endsection
