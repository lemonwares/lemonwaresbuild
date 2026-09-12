@props(['bare' => false])

{{--
    bare=false (default): bordered card wrapper — used on hosting/email sections
    bare=true: no card, just the accordion behaviour — used in-page
--}}
<div
    {{ $attributes->merge([
        'class' => $bare
            ? 'divide-y'
            : 'divide-y rounded-2xl border bg-white',
        'data-accordion' => true,
    ]) }}
    style="{{ $bare ? 'border-color:var(--color-border)' : 'border-color:var(--color-border)' }}"
>
    {{ $slot }}
</div>
