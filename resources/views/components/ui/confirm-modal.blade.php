@props([
    'action',
    'title',
    'body' => '',
    'confirmLabel',
    'cancelLabel',
    'openLabel',
    'openClass' => 'btn btn-ghost',
])

@php
    $titleId = 'confirm-title-' . uniqid();
@endphp

<div data-confirm-modal class="inline-flex shrink-0">
    <button type="button" data-confirm-open class="{{ $openClass }}">
        {{ $openLabel }}
    </button>

    <form method="POST" action="{{ $action }}" data-confirm-form class="hidden">
        @csrf
        {{ $slot }}
    </form>

    <div
        data-confirm-dialog
        class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/40 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $titleId }}"
    >
        <div class="w-full max-w-md rounded-3xl border border-border bg-white p-6 shadow-2xl">
            <h3 id="{{ $titleId }}" class="text-xl font-bold text-black">{{ $title }}</h3>
            @if ($body !== '')
                <p class="mt-2 body-text">{{ $body }}</p>
            @endif
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" data-confirm-cancel class="rounded-xl border border-border px-4 py-2 text-sm font-semibold text-black transition hover:border-rose hover:text-rose">
                    {{ $cancelLabel }}
                </button>
                <button type="button" data-confirm-submit class="inline-flex items-center gap-2 rounded-xl bg-rose px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#c93737]">
                    <span class="hidden size-4 animate-spin rounded-full border-2 border-white/30 border-t-white" data-confirm-spinner></span>
                    <span data-confirm-label>{{ $confirmLabel }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
