@extends('layouts.client-auth')

@section('title', __('account.reset_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.reset_title'))

@section('content')
    {{-- Heading --}}
    <div class="mb-8">
        <p class="section-label mb-3">{{ __('account.auth_area') }}</p>
        <h1 class="text-3xl font-bold tracking-tight" style="color:var(--color-ink);">
            {{ __('account.reset_title') }}
        </h1>
        <p class="mt-2 text-sm font-light" style="color:var(--color-ink-3);">
            {{ __('account.reset_lede') }}
        </p>
    </div>

    {{-- Error --}}
    @if ($errors->any())
        <div class="auth-error mb-6" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/>
                <line x1="12" x2="12.01" y1="16" y2="16"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" data-submit-form>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="auth-label">{{ __('account.email') }}</label>
            <input
                id="email" name="email" type="email"
                value="{{ old('email', $email) }}"
                required autocomplete="email"
                class="auth-input @error('email') border-red @enderror"
                placeholder="you@company.com"
            >
        </div>

        <div>
            <label for="password" class="auth-label">{{ __('account.password') }}</label>
            <input
                id="password" name="password" type="password"
                required autocomplete="new-password"
                class="auth-input @error('password') border-red @enderror"
                placeholder="New password"
            >
        </div>

        <div>
            <label for="password_confirmation" class="auth-label">{{ __('account.password_confirm') }}</label>
            <input
                id="password_confirmation" name="password_confirmation" type="password"
                required autocomplete="new-password"
                class="auth-input"
                placeholder="Confirm new password"
            >
        </div>

        <div class="pt-1">
            <x-ui.submit-button
                :label="__('account.reset_title')"
                :loading="__('account.saving')"
                class="btn btn-primary w-full py-3.5 text-sm"
            />
        </div>
    </form>

    {{-- Divider --}}
    <div class="auth-divider"></div>

    <p class="text-center text-sm" style="color:var(--color-ink-3);">
        <a href="{{ route('login') }}"
           class="font-bold transition hover:underline"
           style="color:var(--color-ink);">
            ← {{ __('account.sign_in') }}
        </a>
    </p>
@endsection
