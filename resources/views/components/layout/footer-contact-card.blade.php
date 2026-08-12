@props(['title', 'value', 'href', 'target' => null, 'rel' => null])

<article {{ $attributes->except(['href', 'target', 'rel'])->class('footer-contact-card flex flex-col gap-2 py-4') }}>
    <h3 class="footer-heading">{{ $title }}</h3>
    <p class="body-text">{{ $value }}</p>
    <a href="{{ $href }}" @if ($target) target="{{ $target }}" @endif
        @if ($rel) rel="{{ $rel }}" @endif
        class="link inline-flex w-fit items-center gap-2 text-base font-semibold">
        {{ $slot }}
    </a>
</article>
