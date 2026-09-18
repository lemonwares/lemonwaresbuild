@extends('layouts.admin')

@section('title', $campaign->subject . ' — Campaign — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="{{ $campaign->subject }}"
        lede="Newsletter campaign"
        :back-href="route('admin.newsletter-campaigns.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Campaigns', 'href' => route('admin.newsletter-campaigns.index')],
            ['label' => $campaign->subject],
        ]"
        class="mb-5"
    >
        <x-slot:actions>
            @if ($campaign->isDraft())
                <a href="{{ route('admin.newsletter-campaigns.edit', $campaign) }}" class="admin-btn-ghost">Edit</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customer-stats" aria-label="Campaign snapshot">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Status</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $campaign->status }}</span>
                <span class="admin-metric-meta">{{ $campaign->creator?->name ?: '—' }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">List size</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $subscriberCount }}</span>
                <span class="admin-metric-meta">Total subscribers</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Targeted</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $campaign->recipients_count ?: '—' }}</span>
                <span class="admin-metric-meta">
                    @if ($campaign->recipient_mode === 'selected')
                        Selected only
                    @else
                        All subscribers
                    @endif
                </span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Sent</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $campaign->sent_count }}</span>
                <span class="admin-metric-meta">{{ $campaign->sent_at?->timezone(config('app.timezone'))->format('d M Y g:i A') ?: 'Not sent' }}</span>
            </div>
        </section>

        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Message</h2>
                </div>
                <dl class="admin-dl">
                    <div class="admin-dl-span">
                        <dt>Subject</dt>
                        <dd>{{ $campaign->subject }}</dd>
                    </div>
                    @if ($campaign->imagePaths() !== [])
                        <div class="admin-dl-span">
                            <dt>Pictures</dt>
                            <dd>
                                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                    @foreach ($campaign->imagePaths() as $path)
                                        <img src="{{ asset('storage/' . $path) }}" alt="" class="admin-campaign-image-thumb" />
                                    @endforeach
                                </div>
                            </dd>
                        </div>
                    @endif
                    <div class="admin-dl-span">
                        <dt>Body</dt>
                        <dd class="whitespace-pre-wrap">{{ $campaign->body }}</dd>
                    </div>
                    @if ($campaign->last_error)
                        <div class="admin-dl-span">
                            <dt>Last error</dt>
                            <dd class="text-rose-700">{{ $campaign->last_error }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Send</h2>
                </div>
                <div class="space-y-4">
                    @if ($campaign->isDraft() || $campaign->status === 'failed')
                        <form
                            method="POST"
                            action="{{ route('admin.newsletter-campaigns.send', $campaign) }}"
                            class="space-y-4"
                            data-submit-form
                            data-campaign-send
                        >
                            @csrf

                            <fieldset class="space-y-2">
                                <legend class="admin-field-label">Recipients</legend>
                                <label class="admin-check mt-0">
                                    <input type="radio" name="recipient_mode" value="all" data-recipient-mode @checked(old('recipient_mode', 'all') === 'all')>
                                    <span>All subscribers ({{ $subscriberCount }})</span>
                                </label>
                                <label class="admin-check mt-0">
                                    <input type="radio" name="recipient_mode" value="selected" data-recipient-mode @checked(old('recipient_mode') === 'selected')>
                                    <span>Only selected people</span>
                                </label>
                                @error('recipient_mode') <em class="block text-sm text-rose">{{ $message }}</em> @enderror
                            </fieldset>

                            <div
                                class="admin-campaign-recipients @if (old('recipient_mode', 'all') !== 'selected') hidden @endif"
                                data-recipient-list
                            >
                                @if ($subscribers->isEmpty())
                                    <p class="admin-muted">No subscribers on the list yet.</p>
                                @else
                                    <div class="admin-customers-toolbar mb-2">
                                        <button type="button" class="admin-btn-ghost" data-select-all-subscribers>Select all</button>
                                        <button type="button" class="admin-btn-ghost" data-clear-subscribers>Clear</button>
                                    </div>
                                    <div class="admin-campaign-recipient-scroll">
                                        @foreach ($subscribers as $subscriber)
                                            <label class="admin-check mt-0">
                                                <input
                                                    type="checkbox"
                                                    name="subscriber_ids[]"
                                                    value="{{ $subscriber->id }}"
                                                    data-subscriber-id
                                                    @checked(in_array($subscriber->id, old('subscriber_ids', []), false))
                                                >
                                                <span>
                                                    {{ $subscriber->full_name ?: 'Subscriber' }}
                                                    <span class="admin-muted"> · {{ $subscriber->email }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                                @error('subscriber_ids') <em class="mt-2 block text-sm text-rose">{{ $message }}</em> @enderror
                            </div>

                            <button
                                type="submit"
                                class="admin-btn-primary inline-flex items-center gap-2"
                                data-submit-button
                                @disabled($subscriberCount < 1)
                                data-send-confirm
                            >
                                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                                <span data-submit-label>Send campaign</span>
                                <span class="hidden" data-submit-loading>Sending…</span>
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.newsletter-campaigns.destroy', $campaign) }}" data-submit-form onsubmit="return confirm('Delete this campaign?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-btn-danger inline-flex items-center gap-2" data-submit-button>
                                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                                <span data-submit-label>Delete draft</span>
                                <span class="hidden" data-submit-loading>Deleting…</span>
                            </button>
                        </form>
                    @else
                        <p class="admin-muted">
                            Sent to
                            @if ($campaign->recipient_mode === 'selected')
                                {{ $campaign->sent_count }} selected subscriber(s).
                            @else
                                the full list ({{ $campaign->sent_count }}).
                            @endif
                            This campaign can no longer be edited.
                        </p>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-campaign-send]');
            if (!form) return;

            const list = form.querySelector('[data-recipient-list]');
            const modes = form.querySelectorAll('[data-recipient-mode]');
            const boxes = () => [...form.querySelectorAll('[data-subscriber-id]')];

            const sync = () => {
                const mode = form.querySelector('[data-recipient-mode]:checked')?.value || 'all';
                list?.classList.toggle('hidden', mode !== 'selected');
            };

            modes.forEach((el) => el.addEventListener('change', sync));
            sync();

            form.querySelector('[data-select-all-subscribers]')?.addEventListener('click', () => {
                boxes().forEach((box) => { box.checked = true; });
            });
            form.querySelector('[data-clear-subscribers]')?.addEventListener('click', () => {
                boxes().forEach((box) => { box.checked = false; });
            });

            form.querySelector('[data-send-confirm]')?.addEventListener('click', (event) => {
                const mode = form.querySelector('[data-recipient-mode]:checked')?.value || 'all';
                const count = mode === 'all'
                    ? {{ (int) $subscriberCount }}
                    : boxes().filter((box) => box.checked).length;
                if (!confirm(`Send this campaign to ${count} subscriber(s)?`)) {
                    event.preventDefault();
                }
            });
        })();
    </script>
@endpush
