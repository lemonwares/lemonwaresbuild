@props([
    'title',
    'defaultOpen' => false,
])

<details class="accordion-item group" @if ($defaultOpen) open @endif>
    <summary>
        {{ $title }}
        <x-ui.icons.chevron-down class="size-4 shrink-0 text-on-blush/40 transition group-open:rotate-180 group-open:text-rose" />
    </summary>
    <div class="accordion-panel">
        {{ $slot }}
    </div>
</details>
