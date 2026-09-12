@extends('layouts.client-auth')

@section('title', __('account.register_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.register_lede'))

@section('content')
    <div class="mx-auto max-w-md rounded-4xl border border-border bg-white p-6 sm:p-8">
            <p class="section-label mb-2">{{ __('site.common.client_login') }}</p>
            <h1 class="heading">{{ __('account.register_title') }}</h1>
            <p class="lede mt-3">{{ __('account.register_lede') }}</p>

            @if ($errors->any())
                <p class="mt-6 mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $prefillName ?? '') }}" required autocomplete="name" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-black">{{ __('account.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $prefillEmail ?? '') }}" required autocomplete="email" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-black">{{ __('account.password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-black">{{ __('account.password_confirm') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <button type="submit" class="btn btn-primary w-full">{{ __('account.create_account') }}</button>
            </form>

            <p class="mt-6 text-sm font-semibold">
                <a href="{{ route('login') }}" class="text-rose hover:underline">{{ __('account.has_account') }}</a>
            </p>
        </div>
@endsection
