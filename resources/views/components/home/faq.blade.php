@props([
    'limit' => null,
])

@php
    $items = __('faq.items');
    if (! is_array($items)) {
        $items = [];
    }
    if ($limit) {
        $items = array_slice($items, 0, (int) $limit);
    }
@endphp

<section {{ $attributes->class('border-t border-border bg-white') }} id="faq">
    <div class="container-page py-16 sm:py-20">
        <div class="mb-10 max-w-2xl">
            <p class="section-label mb-3">{{ __('faq.home_label') }}</p>
            <h2 class="heading mb-4">{{ __('faq.home_title') }}</h2>
            <p class="lede">{{ __('faq.home_lede') }}</p>
        </div>

        <div class="mx-auto max-w-3xl">
            <x-ui.accordion>
                @foreach ($items as $index => $item)
                    <x-ui.accordion-item :title="$item['question']" :default-open="$index === 0">
                        {{ $item['answer'] }}
                    </x-ui.accordion-item>
                @endforeach
            </x-ui.accordion>
        </div>

        <div class="mt-10">
            <x-ui.button href="{{ route('faq') }}" variant="ghost">
                <span>{{ __('faq.view_all') }}</span>
                <x-ui.icons.arrow-up-right class="size-4" />
            </x-ui.button>
        </div>
    </div>
</section>
