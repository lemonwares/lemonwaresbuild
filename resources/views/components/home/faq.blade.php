@props(['limit' => null])

@php
    $items = __('faq.items');
    if (!is_array($items)) $items = [];
    if ($limit) $items = array_slice($items, 0, (int) $limit);
@endphp

{{-- FAQ — white bg, sticky-header left / accordion right --}}
<section id="faq" {{ $attributes->class('border-t bg-white') }}
         style="border-color:var(--color-border);">

    <div class="container-page py-20 sm:py-24">
        <div class="grid gap-12 lg:grid-cols-[1fr_1.5fr] lg:gap-20">

            {{-- ══ LEFT: Sticky header ══ --}}
            <div class="lg:sticky lg:top-[calc(var(--site-header-height,5rem)+2rem)] lg:self-start">

                <p class="section-label mb-4">{{ __('faq.home_label') }}</p>

                <h2 class="mb-5 text-4xl font-bold tracking-tight sm:text-5xl"
                    style="color:var(--color-ink);">
                    {{ __('faq.home_title') }}
                </h2>

                <p class="mb-8 text-base font-light leading-relaxed"
                   style="color:var(--color-ink-3); max-width:28rem;">
                    {{ __('faq.home_lede') }}
                </p>

                <a href="{{ route('faq') }}" class="btn btn-ghost gap-2 px-6 py-3 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                    <span>{{ __('faq.view_all') }}</span>
                </a>
            </div>

            {{-- ══ RIGHT: Accordion items — borderless on white ══ --}}
            <div class="divide-y" style="border-color:var(--color-border);" data-accordion>
                @foreach ($items as $index => $item)
                    <div
                        class="accordion-item"
                        data-accordion-item
                        data-open="{{ $index === 0 ? 'true' : 'false' }}"
                    >
                        <button
                            type="button"
                            class="accordion-trigger w-full py-6 text-left"
                            data-accordion-trigger
                            aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                        >
                            <span class="text-base font-bold leading-snug sm:text-lg"
                                  style="color:var(--color-ink);">
                                {{ $item['question'] }}
                            </span>
                            <span class="accordion-chevron ml-4 inline-flex size-7 shrink-0 items-center
                                         justify-center rounded-full border transition"
                                  style="border-color:var(--color-border); color:var(--color-ink-3);"
                                  data-accordion-chevron
                                  aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2.5"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </span>
                        </button>

                        <div class="accordion-panel" data-accordion-panel>
                            <div class="accordion-panel-inner pb-6 pr-12 text-sm leading-relaxed"
                                 data-accordion-inner
                                 style="color:var(--color-ink-3);">
                                {{ $item['answer'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</section>
