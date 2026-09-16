@extends('layouts.app')

@section('title', $metaTitle . ' — ' . config('site.short_name'))
@section('meta_description', $metaDescription)

@section('content')
    <x-layout.page-hero
        :eyebrow="$eyebrow"
        :title="$title"
        :lede="$lede"
        :cta-href="$ctaHref"
        :cta-label="$ctaLabel"
        :art="true"
        :art-src="$artSrc ?? 'images/heroes/about.webp'"
    />

    <x-layout.page-content wide>
        @if (! empty($body))
            <div class="mb-10 max-w-3xl">
                <p class="lede">{{ $body }}</p>
            </div>
        @endif

        @if (! empty($highlights))
            <ul class="check-list mb-12 grid gap-3 md:grid-cols-2">
                @foreach ($highlights as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @endif

        @if (! empty($cards))
            <div @class([
                'mb-12 grid gap-6',
                'lg:grid-cols-2' => count($cards) === 2,
                'lg:grid-cols-3' => count($cards) >= 3,
            ])>
                @foreach ($cards as $card)
                    <article class="card-tech flex flex-col gap-3 p-8">
                        <h3 class="text-xl font-bold text-on-blush">{{ $card['title'] }}</h3>
                        <p class="body-text flex-1">{{ $card['body'] }}</p>
                        @if (! empty($card['href']))
                            <a href="{{ $card['href'] }}" class="mt-2 inline-flex items-center gap-1.5 text-sm font-bold text-rose hover:underline">
                                <span>{{ $card['cta'] ?? __('site.common.contact_us') }}</span>
                                <x-ui.icons.arrow-up-right class="size-3.5" />
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        <div class="border-t border-border pt-10">
            <h2 class="heading mb-3">{{ $helpTitle }}</h2>
            <p class="lede mb-6">{{ $helpLede }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button href="{{ $helpPrimaryHref }}">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ $helpPrimaryLabel }}</span>
                </x-ui.button>
                @if (! empty($helpSecondaryHref))
                    <x-ui.button href="{{ $helpSecondaryHref }}" variant="ghost">
                        <span>{{ $helpSecondaryLabel }}</span>
                    </x-ui.button>
                @endif
            </div>
        </div>
    </x-layout.page-content>
@endsection
