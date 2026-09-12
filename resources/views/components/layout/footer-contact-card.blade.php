@props(['title', 'value', 'href', 'target' => null, 'rel' => null])

<article {{ $attributes->except(['href','target','rel'])->class('flex flex-col gap-1.5 py-5') }}>
    <p class="text-[10px] font-bold uppercase tracking-[0.18em]" style="color:var(--color-ink-3);">
        {{ $title }}
    </p>
    <p class="text-sm font-semibold" style="color:var(--color-ink);">{{ $value }}</p>
    <a
        href="{{ $href }}"
        @if ($target) target="{{ $target }}" @endif
        @if ($rel) rel="{{ $rel }}" @endif
        class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold transition hover:underline"
        style="color:var(--color-red);"
    >
        {{ $slot }}
    </a>
</article>
