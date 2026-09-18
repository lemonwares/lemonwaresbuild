@extends('layouts.client-auth')

@section('title', __('account.login_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.login_lede'))

@section('content')
    <div class="auth-heading">
        <h1 class="font-bold tracking-tight text-black">{{ __('account.login_title') }}</h1>
        <p class="font-light text-on-blush/65">{{ __('account.login_lede') }}</p>
    </div>

    <x-ui.flash show-status />

    @if ($errors->any())
        <div class="auth-error" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/>
                <line x1="12" x2="12.01" y1="16" y2="16"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="auth-form-fields" data-submit-form>
        @csrf
        @if (request()->filled('redirect'))
            <input type="hidden" name="redirect" value="{{ request('redirect') }}">
        @endif

        <div>
            <label for="email" class="auth-label">{{ __('account.email') }}</label>
            <input
                id="email" name="email" type="email"
                value="{{ old('email', request('email')) }}"
                required autocomplete="email"
                class="auth-input @error('email') border-rose/50 @enderror"
                placeholder="you@company.com"
            >
        </div>

        <div>
            <div class="mb-1 flex items-center justify-between">
                <label for="password" class="auth-label mb-0">{{ __('account.password') }}</label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-rose transition hover:underline">
                    {{ __('account.forgot') }}
                </a>
            </div>
            <input
                id="password" name="password" type="password"
                required autocomplete="current-password"
                class="auth-input"
                placeholder="••••••••"
            >
        </div>

        <label class="flex cursor-pointer items-center gap-2">
            <input type="checkbox" name="remember" value="1" class="size-3.5 rounded border border-border accent-[#c51a13]">
            <span class="text-sm font-medium text-on-blush/75">{{ __('account.remember') }}</span>
        </label>

        <x-ui.submit-button
            :label="__('account.sign_in')"
            :loading="__('account.signing_in')"
            class="btn btn-primary auth-submit"
        />
    </form>

    <div class="auth-divider"></div>

    <p class="text-center text-sm text-on-blush/60">
        {{ __('account.no_account') }}
        <a href="{{ route('register') }}" class="ml-1 font-bold text-black transition hover:text-rose">
            {{ __('account.create_account') }}
        </a>
    </p>
@endsection
