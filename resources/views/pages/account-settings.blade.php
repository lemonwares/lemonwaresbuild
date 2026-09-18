@extends('layouts.account')

@section('title', __('account.settings') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.settings_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_settings')"
        :title="__('account.settings')"
        :lede="__('account.settings_lede')"
    />

    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <div class="account-page-stack">
        <section class="account-panel">
            <h2 class="account-panel-title">{{ __('account.notification_prefs_title') }}</h2>
            <p class="account-panel-lede">{{ __('account.notification_prefs_lede') }}</p>

            <form method="POST" action="{{ route('account.notifications.update') }}" class="mt-5 space-y-3" data-submit-form>
                @csrf
                @method('PUT')
                <label class="account-check" style="align-items:flex-start">
                    <input type="hidden" name="notify_in_app" value="0">
                    <input type="checkbox" name="notify_in_app" value="1" class="mt-0.5 size-4 rounded border-border text-rose focus:ring-rose" @checked(old('notify_in_app', $user->notify_in_app ?? true))>
                    <span class="text-sm text-black">
                        <span class="font-semibold">{{ __('account.notify_in_app') }}</span>
                        <span class="mt-1 block text-on-blush/65">{{ __('account.notify_in_app_help') }}</span>
                    </span>
                </label>
                <label class="account-check" style="align-items:flex-start">
                    <input type="hidden" name="notify_email" value="0">
                    <input type="checkbox" name="notify_email" value="1" class="mt-0.5 size-4 rounded border-border text-rose focus:ring-rose" @checked(old('notify_email', $user->notify_email ?? true))>
                    <span class="text-sm text-black">
                        <span class="font-semibold">{{ __('account.notify_email') }}</span>
                        <span class="mt-1 block text-on-blush/65">{{ __('account.notify_email_help') }}</span>
                    </span>
                </label>
                <x-ui.submit-button :label="__('account.notification_prefs_save')" :loading="__('account.saving')" class="account-btn-primary" />
            </form>
        </section>

        <section class="account-panel">
            <h2 class="account-panel-title">{{ __('account.contacts_title') }}</h2>
            <p class="account-panel-lede">{{ __('account.contacts_lede') }}</p>

            <div class="account-list mt-5">
                @forelse ($contacts as $contact)
                    <div class="account-list-item">
                        <div class="account-list-copy">
                            <p class="account-list-title">{{ $contact->name }}</p>
                            <p class="account-list-meta">{{ $contact->email }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="account-pill">{{ $contact->roleLabel() }}</span>
                                @if ($contact->notify)
                                    <span class="account-pill is-muted">{{ __('account.contact_gets_mail') }}</span>
                                @endif
                                @if ($contact->unavailable_backup)
                                    <span class="account-pill is-muted">{{ __('account.contact_is_backup') }}</span>
                                @endif
                            </div>
                        </div>
                        <x-ui.confirm-modal
                            :action="route('account.contacts.destroy', $contact)"
                            :title="__('account.contact_remove_title')"
                            :body="__('account.contact_remove_body', ['email' => $contact->email])"
                            :confirm-label="__('account.contact_remove')"
                            :cancel-label="__('account.cancel')"
                            :open-label="__('account.contact_remove')"
                            open-class="account-btn-danger"
                        >
                            @method('DELETE')
                        </x-ui.confirm-modal>
                    </div>
                @empty
                    <div class="account-empty">
                        <p class="account-empty-title">{{ __('account.no_contacts') }}</p>
                    </div>
                @endforelse
            </div>
        </section>

        @if ($contacts->count() < 8)
            <section class="account-panel">
                <h2 class="account-panel-title">{{ __('account.contact_add') }}</h2>
                <p class="account-panel-lede">{{ __('account.contact_add_lede') }}</p>

                <form method="POST" action="{{ route('account.contacts.store') }}" class="mt-5 space-y-5" data-submit-form>
                    @csrf
                    <div class="account-form-grid">
                        <div class="account-field">
                            <label for="contact_name">{{ __('account.contact_name') }}</label>
                            <input id="contact_name" name="name" type="text" value="{{ old('name') }}" required>
                        </div>
                        <div class="account-field">
                            <label for="contact_email">{{ __('account.contact_email') }}</label>
                            <input id="contact_email" name="email" type="email" value="{{ old('email') }}" required>
                        </div>
                        <div class="account-field is-full">
                            <label for="contact_role">{{ __('account.contact_role') }}</label>
                            <select id="contact_role" name="role" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected(old('role', 'support') === $role)>{{ __('account.contact_roles.' . $role) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <label class="account-check" style="align-items:flex-start">
                        <input type="hidden" name="notify" value="0">
                        <input type="checkbox" name="notify" value="1" class="mt-0.5 size-4 rounded border-border text-rose focus:ring-rose" @checked(old('notify', '1') === '1' || old('notify') === true)>
                        <span class="text-sm text-black">
                            <span class="font-semibold">{{ __('account.contact_notify') }}</span>
                            <span class="mt-0.5 block text-on-blush/65">{{ __('account.contact_notify_help') }}</span>
                        </span>
                    </label>
                    <label class="account-check" style="align-items:flex-start">
                        <input type="hidden" name="unavailable_backup" value="0">
                        <input type="checkbox" name="unavailable_backup" value="1" class="mt-0.5 size-4 rounded border-border text-rose focus:ring-rose" @checked(old('unavailable_backup'))>
                        <span class="text-sm text-black">
                            <span class="font-semibold">{{ __('account.contact_backup') }}</span>
                            <span class="mt-0.5 block text-on-blush/65">{{ __('account.contact_backup_help') }}</span>
                        </span>
                    </label>
                    <x-ui.submit-button :label="__('account.contact_save')" :loading="__('account.adding_contact')" class="account-btn-primary" />
                </form>
            </section>
        @endif
    </div>
@endsection
