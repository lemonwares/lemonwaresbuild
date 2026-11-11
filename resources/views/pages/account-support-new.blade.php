@extends('layouts.account')

@section('title', __('account.support_new') . ' — ' . config('site.short_name'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_support')"
        :title="__('account.support_new')"
        :lede="__('account.support_new_lede')"
        :back-href="route('account.support.index')"
        :back-label="__('account.nav_support')"
    />

    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <section class="account-panel">
        <form method="POST" action="{{ route('account.support.store') }}">
            @csrf
            <div class="account-form-grid">
                <div class="account-field">
                    <label for="deptid">{{ __('account.support_department') }}</label>
                    <select id="deptid" name="deptid" required>
                        @foreach ($departments as $id => $name)
                            <option value="{{ $id }}" @selected((int) old('deptid') === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="account-field">
                    <label for="priority">{{ __('account.support_priority') }}</label>
                    <select id="priority" name="priority" required>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', 'Medium') === $priority)>{{ __('account.support_priorities.' . $priority) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="account-field is-full">
                    <label for="subject">{{ __('account.support_subject') }}</label>
                    <input id="subject" name="subject" type="text" maxlength="200" value="{{ old('subject') }}" required>
                </div>
                <div class="account-field is-full">
                    <label for="message">{{ __('account.support_message') }}</label>
                    <textarea id="message" name="message" rows="8" required>{{ old('message') }}</textarea>
                </div>
            </div>
            <div class="mt-5">
                <button type="submit" class="account-btn-primary">{{ __('account.support_submit') }}</button>
            </div>
        </form>
    </section>
@endsection
