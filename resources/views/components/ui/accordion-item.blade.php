@props([
    'title',
    'defaultOpen' => false,
    'galleryKey' => null,
    'logo' => null,
])

<div
    class="accordion-item"
    data-accordion-item
    data-open="{{ $defaultOpen ? 'true' : 'false' }}"
    @if ($galleryKey) data-gallery-key="{{ $galleryKey }}" @endif
>
    <button
        type="button"
        class="accordion-trigger"
        data-accordion-trigger
        aria-expanded="{{ $defaultOpen ? 'true' : 'false' }}"
    >
        <span class="accordion-trigger-label">
            @if ($logo)
                <img src="{{ asset($logo) }}" alt="" width="18" height="18" class="accordion-trigger-logo">
            @endif
            <span>{{ $title }}</span>
        </span>
        <x-ui.icons.chevron-down class="accordion-chevron size-4 shrink-0" data-accordion-chevron />
    </button>
    <div class="accordion-panel" data-accordion-panel>
        <div class="accordion-panel-inner" data-accordion-inner>
            {{ $slot }}
        </div>
    </div>
</div>
