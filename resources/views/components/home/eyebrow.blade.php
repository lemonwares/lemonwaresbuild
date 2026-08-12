@props([
    'label' => '',
])

<span {{ $attributes->class('inline-flex items-center gap-2 rounded-full bg-slate px-3 py-1.5 text-base font-medium text-white') }}>
    <span class="size-2 shrink-0 rounded-full bg-rose" aria-hidden="true"></span>
    {{ $label !== '' ? $label : $slot }}
</span>
