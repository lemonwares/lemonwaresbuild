@props([
    'wide' => false,
    'full' => false,
])

<section id="page-content" {{ $attributes->class('container-page py-14 sm:py-16') }}>
    <div @class([
        'mx-auto w-full',
        'max-w-3xl' => ! $wide && ! $full,
        'max-w-5xl' => $wide && ! $full,
    ])>
        {{ $slot }}
    </div>
</section>
