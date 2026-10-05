@extends('layouts.admin')

@section('title', 'New customer — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="New customer"
        lede="Create an account for someone who ordered by phone, email or in person."
        :back-href="route('admin.customers.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Customers', 'href' => route('admin.customers.index')],
            ['label' => 'New'],
        ]"
        class="mb-5"
    />

    <section class="admin-panel max-w-2xl">
        <form method="POST" action="{{ route('admin.customers.store') }}" class="space-y-4" data-submit-form>
            @csrf
            <label class="admin-field">
                <span>Full name</span>
                <input type="text" name="name" value="{{ old('name') }}" class="admin-input" maxlength="120" required>
                @error('name') <em>{{ $message }}</em> @enderror
            </label>
            <label class="admin-field">
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" class="admin-input" maxlength="190" required>
                @error('email') <em>{{ $message }}</em> @enderror
            </label>
            <label class="admin-field">
                <span>Phone</span>
                <input type="text" name="phone" value="{{ old('phone') }}" class="admin-input" maxlength="40">
            </label>
            <label class="admin-field">
                <span>Company</span>
                <input type="text" name="company" value="{{ old('company') }}" class="admin-input" maxlength="160">
            </label>
            <label class="admin-field">
                <span>Country</span>
                <select name="billing_country" class="admin-input">
                    <option value="">—</option>
                    @foreach ($countries as $code => $label)
                        <option value="{{ $code }}" @selected(old('billing_country') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('billing_country') <em>{{ $message }}</em> @enderror
            </label>
            <label class="admin-field">
                <span>Internal notes</span>
                <textarea name="admin_notes" rows="3" class="admin-input">{{ old('admin_notes') }}</textarea>
            </label>
            <label class="admin-check">
                <input type="hidden" name="send_invite" value="0">
                <input type="checkbox" name="send_invite" value="1" @checked(old('send_invite', true))>
                <span>Email them a link to set their password</span>
            </label>
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Create customer</span>
                <span class="hidden" data-submit-loading>Creating…</span>
            </button>
        </form>
    </section>
@endsection
