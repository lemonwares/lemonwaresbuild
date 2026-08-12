@props([
    'title',
    'description',
    'highlights' => [],
    'image' => null,
    'icon' => 'zap',
    'tone' => 'blush',
    'reverse' => false,
])

<div
    {{ $attributes->class('service-row grid items-center gap-10 border-b border-border py-14 last:border-b-0 lg:grid-cols-2 lg:gap-16') }}>
    <div @class(['flex flex-col gap-5', 'lg:order-2' => $reverse])>
        <h3 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ $title }}</h3>
        <p class="body-text max-w-xl">{{ $description }}</p>

        @if (!empty($highlights))
            <ul class="check-list max-w-xl space-y-3">
                @foreach ($highlights as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div @class(['service-row-media', 'lg:order-1' => $reverse])>
        @if ($image)
            <img src="{{ asset($image) }}" alt="{{ $title }}" class="size-full rounded-4xl object-cover"
                loading="lazy">
        @else
            <x-ui.service-visual :icon="$icon" :tone="$tone" :label="$title"
                class="min-h-[18rem] rounded-4xl sm:min-h-[22rem]" />
        @endif
    </div>
</div>
