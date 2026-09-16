@props([
    'title',
    'description',
    'highlights' => [],
    'image' => null,
    'icon' => 'zap',
    'tone' => 'blush',
    'reverse' => false,
    'href' => null,
    'cta' => null,
])

<div
    {{ $attributes->class('service-row grid items-stretch gap-10 border-b border-border py-14 last:border-b-0 lg:grid-cols-2 lg:gap-16') }}>
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

        @if ($href && $cta)
            <div>
                <a href="{{ $href }}" class="inline-flex items-center gap-2 text-sm font-semibold text-rose hover:underline">
                    <span>{{ $cta }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </a>
            </div>
        @endif
    </div>

    <div @class(['service-row-media relative', 'lg:order-1' => $reverse])>
        @if ($image)
            <img src="{{ asset($image) }}" alt="{{ $title }}" class="absolute inset-0 size-full object-cover object-center"
                loading="lazy">
        @else
            <x-ui.service-visual :icon="$icon" :tone="$tone" :label="$title"
                class="absolute inset-0 min-h-[18rem] rounded-4xl sm:min-h-[22rem]" />
        @endif
    </div>
</div>
