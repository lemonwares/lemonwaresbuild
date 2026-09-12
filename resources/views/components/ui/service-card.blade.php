@props([
    'title',
    'description',
    'image' => null,
    'icon' => 'zap',
    'tone' => 'blush',
])

<article {{ $attributes->class('service-card') }}>
    <div class="service-card-media">
        @if ($image)
            <img
                src="{{ asset($image) }}"
                alt="{{ $title }}"
                class="size-full object-cover"
                loading="lazy"
            >
        @else
            <x-ui.service-visual :icon="$icon" :tone="$tone" :label="$title" />
        @endif
    </div>

    <div class="service-card-body">
        <h3 class="mb-2 text-lg font-semibold text-black">{{ $title }}</h3>
        <p class="body-text">{{ $description }}</p>
    </div>
</article>
