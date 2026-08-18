@extends('layouts.account')

@section('title', __('account.settings') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.settings_lede'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-2">{{ __('account.nav_settings') }}</p>
        <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ __('account.settings') }}</h1>
        <p class="lede mt-2">{{ __('account.settings_lede') }}</p>
    </div>

    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <section class="mb-6 rounded-3xl border border-border bg-white p-6 sm:p-8">
        <h2 class="text-lg font-bold text-black">{{ __('account.contacts_title') }}</h2>
        <p class="mt-2 text-sm text-on-blush/70">{{ __('account.contacts_lede') }}</p>

        <div class="mt-6 space-y-3">
            @forelse ($contacts as $contact)
                <div class="flex flex-col gap-3 rounded-2xl border border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-black">{{ $contact->name }}</p>
                        <p class="text-sm text-on-blush/70">{{ $contact->email }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="rounded-full bg-blush-soft px-2.5 py-1 text-xs font-semibold uppercase tracking-widest text-rose">{{ $contact->roleLabel() }}</span>
                            @if ($contact->notify)
                                <span class="rounded-full bg-blush-soft px-2.5 py-1 text-xs font-semibold text-on-blush/70">{{ __('account.contact_gets_mail') }}</span>
                            @endif
                            @if ($contact->unavailable_backup)
                                <span class="rounded-full bg-blush-soft px-2.5 py-1 text-xs font-semibold text-on-blush/70">{{ __('account.contact_is_backup') }}</span>
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
                        open-class="rounded-xl border border-border px-3 py-2 text-sm font-semibold text-black transition hover:border-rose hover:text-rose"
                    >
                        @method('DELETE')
                    </x-ui.confirm-modal>
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-border px-4 py-6 text-sm text-on-blush/65">{{ __('account.no_contacts') }}</p>
            @endforelse
        </div>
    </section>

    @if ($contacts->count() < 8)
        <section class="rounded-3xl border border-border bg-white p-6 sm:p-8">
            <h2 class="text-lg font-bold text-black">{{ __('account.contact_add') }}</h2>
            <p class="mt-1 text-sm text-on-blush/65">{{ __('account.contact_add_lede') }}</p>

            <form method="POST" action="{{ route('account.contacts.store') }}" class="mt-6 grid gap-5 sm:grid-cols-2" data-submit-form>
                @csrf
                <div>
                    <label for="contact_name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.contact_name') }}</label>
                    <input id="contact_name" name="name" type="text" value="{{ old('name') }}" required class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="contact_email" class="mb-2 block text-sm font-semibold text-black">{{ __('account.contact_email') }}</label>
                    <input id="contact_email" name="email" type="email" value="{{ old('email') }}" required class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div class="sm:col-span-2">
                    <label for="contact_role" class="mb-2 block text-sm font-semibold text-black">{{ __('account.contact_role') }}</label>
                    <select id="contact_role" name="role" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', 'support') === $role)>{{ __('account.contact_roles.' . $role) }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="inline-flex items-start gap-3 text-sm text-black sm:col-span-2">
                    <input type="hidden" name="notify" value="0">
                    <input type="checkbox" name="notify" value="1" class="mt-0.5 size-4 rounded border-border text-rose focus:ring-rose" @checked(old('notify', '1') === '1' || old('notify') === true)>
                    <span>
                        <span class="font-semibold">{{ __('account.contact_notify') }}</span>
                        <span class="mt-0.5 block text-on-blush/65">{{ __('account.contact_notify_help') }}</span>
                    </span>
                </label>
                <label class="inline-flex items-start gap-3 text-sm text-black sm:col-span-2">
                    <input type="hidden" name="unavailable_backup" value="0">
                    <input type="checkbox" name="unavailable_backup" value="1" class="mt-0.5 size-4 rounded border-border text-rose focus:ring-rose" @checked(old('unavailable_backup'))>
                    <span>
                        <span class="font-semibold">{{ __('account.contact_backup') }}</span>
                        <span class="mt-0.5 block text-on-blush/65">{{ __('account.contact_backup_help') }}</span>
                    </span>
                </label>
                <div class="sm:col-span-2">
                    <x-ui.submit-button :label="__('account.contact_save')" :loading="__('account.adding_contact')" class="btn btn-primary" />
                </div>
            </form>
        </section>
    @endif
@endsection
