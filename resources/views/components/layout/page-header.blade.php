@props([
    'title',
    'lede' => null,
])

<section {{ $attributes->class('border-b border-border bg-blush-soft') }}>
    <div class="container-page py-14 sm:py-16">
        <p class="section-label mb-3">{{ config('site.short_name') }}</p>
        <h1 class="heading mb-4">{{ $title }}</h1>
        @if ($lede)
            <p class="lede">{{ $lede }}</p>
        @endif
    </div>
</section>
