@extends('layouts.account')

@section('title', '#' . $ticket->tid . ' ' . $ticket->subject . ' — ' . config('site.short_name'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_support') . ' · #' . $ticket->tid"
        :title="$ticket->subject"
        :lede="$ticket->status . ($department ? ' · ' . $department : '')"
        :back-href="route('account.support.index')"
        :back-label="__('account.nav_support')"
    >
        @if ($ticket->status !== 'Closed')
            <x-slot:actions>
                <form method="POST" action="{{ route('account.support.close', $ticket) }}">
                    @csrf
                    <button type="submit" class="account-btn-ghost">{{ __('account.support_close') }}</button>
                </form>
            </x-slot:actions>
        @endif
    </x-account.page-header>

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-600/20 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <div class="account-page-stack">
        @if (! $loaded)
            <div class="account-empty">
                <p class="account-empty-title">{{ __('account.support_unavailable') }}</p>
            </div>
        @endif

        @foreach ($replies as $reply)
            <section class="account-panel" @if ($reply['staff']) style="border-color:color-mix(in srgb, var(--color-rose) 30%, var(--color-border))" @endif>
                <p class="account-list-title">
                    {{ $reply['staff'] ? __('account.support_staff_reply', ['name' => $reply['author']]) : ($reply['author'] ?: __('account.support_you')) }}
                </p>
                <p class="account-list-meta">{{ $reply['date'] }}</p>
                <div class="mt-4 text-sm leading-relaxed" style="overflow-wrap:anywhere">{!! nl2br(e($reply['message'])) !!}</div>
            </section>
        @endforeach

        @if ($loaded)
            <section class="account-panel">
                <h2 class="account-panel-title">{{ $ticket->status === 'Closed' ? __('account.support_reopen') : __('account.support_reply') }}</h2>
                <form method="POST" action="{{ route('account.support.reply', $ticket) }}" class="mt-5">
                    @csrf
                    <div class="account-form-grid">
                        <div class="account-field is-full">
                            <label for="message" class="sr-only">{{ __('account.support_message') }}</label>
                            <textarea id="message" name="message" rows="6" required>{{ old('message') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-5">
                        <button type="submit" class="account-btn-primary">{{ __('account.support_send') }}</button>
                    </div>
                </form>
            </section>
        @endif
    </div>
@endsection
