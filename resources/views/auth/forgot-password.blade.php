@extends('layouts.client-auth')

@section('title', __('account.forgot_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.forgot_lede'))

@section('content')
    <div class="mx-auto max-w-md rounded-4xl border border-border bg-white p-6 sm:p-8">
            <p class="section-label mb-2">{{ __('account.auth_area') }}</p>
            <h1 class="heading">{{ __('account.forgot_title') }}</h1>
            <p class="lede mt-3">{{ __('account.forgot_lede') }}</p>

            <x-ui.flash show-status />

            @if ($errors->any())
                <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5" data-submit-form>
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-black">{{ __('account.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <x-ui.submit-button :label="__('account.send_reset')" :loading="__('account.processing')" class="btn btn-primary w-full" />
            </form>

            <p class="mt-6 text-sm font-semibold">
                <a href="{{ route('login') }}" class="text-rose hover:underline">{{ __('account.sign_in') }}</a>
            </p>
        </div>
@endsection
