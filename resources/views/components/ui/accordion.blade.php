@props([
    'flush' => false,
])

<div
    {{ $attributes->merge([
        'class' => $flush
            ? 'accordion-flush'
            : 'card-tech divide-y divide-border px-6',
        'data-accordion' => true,
    ]) }}
>
    {{ $slot }}
</div>
