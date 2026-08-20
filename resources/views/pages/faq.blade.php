@extends('layouts.app')

@section('title', __('faq.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('faq.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('faq.eyebrow')"
        :title="__('faq.title')"
        :lede="__('faq.lede')"
        cta-href="#page-content"
        :cta-label="__('faq.cta')"
        :art="true"
        art-src="images/heroes/legal.webp"
    />

    <x-layout.page-content>
        <div class="mx-auto max-w-3xl">
            <x-ui.accordion>
                @foreach (__('faq.items') as $index => $item)
                    <x-ui.accordion-item :title="$item['question']" :default-open="$index === 0">
                        <div class="space-y-3">
                            <p>{{ $item['answer'] }}</p>
                            @if (! empty($item['href']) && ! empty($item['cta']))
                                <a href="{{ route($item['href']) }}" class="inline-flex text-sm font-semibold text-rose hover:underline">
                                    {{ $item['cta'] }}
                                </a>
                            @endif
                        </div>
                    </x-ui.accordion-item>
                @endforeach
            </x-ui.accordion>
        </div>

        <div class="mt-14 rounded-3xl bg-blush-soft px-8 py-10 text-center sm:px-12">
            <h2 class="heading mb-3">{{ __('faq.still_title') }}</h2>
            <p class="lede mx-auto mb-6">{{ __('faq.still_lede') }}</p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <x-ui.button href="{{ route('email.plans') }}">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('faq.email_cta') }}</span>
                </x-ui.button>
                <x-ui.button href="{{ route('contact') }}" variant="ghost">
                    <span>{{ __('faq.still_cta') }}</span>
                </x-ui.button>
            </div>
        </div>
    </x-layout.page-content>
@endsection
