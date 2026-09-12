@extends('layouts.admin')

@section('title', 'Admin Login — ' . config('site.short_name'))

@section('content')
    <div class="flex min-h-screen items-center justify-center bg-blush-soft px-5 py-10">
        <div class="w-full max-w-md rounded-4xl border border-border bg-white p-6 shadow-[0_20px_60px_rgba(72,79,86,0.12)] sm:p-8">
            <div class="mb-8 text-center">
                <img
                    src="{{ asset('lemonwareslogo.webp') }}"
                    alt="{{ config('site.name') }}"
                    width="220"
                    height="56"
                    class="mx-auto mb-4 h-10 w-auto"
                >
                <p class="section-label mb-2">Staff CRM</p>
                <h1 class="heading">Sign In</h1>
                <p class="lede mt-3">Use your Lemonwares staff account to monitor customers, email, and hosting.</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-black">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-black">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" />
                    @error('email')
                        <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-black">Password</label>
                    <input id="password" name="password" type="password" required class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" />
                    @error('password')
                        <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" data-submit-button class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-rose px-6 py-4 text-base font-bold text-white shadow-[0_10px_24px_rgba(224,69,69,0.35)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#cf3a3a] hover:shadow-[0_14px_30px_rgba(224,69,69,0.42)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose/40 active:translate-y-0 active:shadow-[0_6px_16px_rgba(224,69,69,0.28)]">
                    <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-submit-spinner></span>
                    <span data-submit-label>Sign In to Staff CRM</span>
                    <span class="hidden" data-submit-loading>Signing In...</span>
                </button>
            </form>
        </div>
    </div>
@endsection

