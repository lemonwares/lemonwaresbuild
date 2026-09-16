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
        {{-- Default stretch (no items-start): left column matches FAQ height so sticky can travel --}}
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-5">
                <div class="faq-sticky-rail">
                    <p class="section-label mb-3">{{ __('faq.home_label') }}</p>
                    <h2 class="heading mb-4">{{ __('faq.home_title') }}</h2>
                    <p class="lede mb-8">{{ __('faq.home_lede') }}</p>
                    <x-ui.button href="{{ route('faq') }}" variant="ghost">
                        <span>{{ __('faq.view_all') }}</span>
                        <x-ui.icons.arrow-up-right class="size-4" />
                    </x-ui.button>
                </div>
            </div>

            <div class="lg:col-span-7">
                <x-ui.accordion flush>
                    @foreach ($items as $index => $item)
                        <x-ui.accordion-item :title="$item['question']" :default-open="$index === 0">
                            {{ $item['answer'] }}
                        </x-ui.accordion-item>
                    @endforeach
                </x-ui.accordion>
            </div>
        </div>
    </div>
</section>
