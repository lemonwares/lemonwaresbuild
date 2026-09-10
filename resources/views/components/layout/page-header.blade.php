@props(['title', 'lede' => null])

<section {{ $attributes->class('page-hero-section') }}>
    <div class="container-page py-12 sm:py-16">
        <p class="section-label mb-3">{{ config('site.short_name') }}</p>
        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl" style="color:var(--color-ink);">
            {{ $title }}
        </h1>
        @if ($lede)
            <p class="lede mt-4">{{ $lede }}</p>
        @endif
    </div>
</section>
