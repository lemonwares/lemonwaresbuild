@props([
    'title',
    'lede',
    'eyebrow'  => __('legal.eyebrow'),
    'sections' => [],
    'badge'    => null,  /* e.g. "Terms & Conditions" */
])

{{-- Hero --}}
<section class="page-hero-section relative overflow-hidden">
    {{-- Dot grid --}}
    <div class="pointer-events-none absolute inset-0" aria-hidden="true"
         style="background-image:radial-gradient(circle,var(--color-border) 1px,transparent 1px);
                background-size:28px 28px; opacity:0.4;"></div>
    <div class="container-page relative z-10 py-14 sm:py-20">
        <div class="max-w-2xl">
            {{-- Document type badge --}}
            <div class="mb-5 inline-flex items-center gap-2.5 rounded-full border px-4 py-1.5"
                 style="border-color:var(--color-border); background:white;">
                <span class="size-1.5 rounded-full" style="background:var(--color-red);" aria-hidden="true"></span>
                <span class="text-[11px] font-bold uppercase tracking-[0.2em]"
                      style="color:var(--color-ink-3);">{{ $eyebrow }}</span>
            </div>
            <h1 class="mb-4 text-4xl font-bold tracking-tight sm:text-5xl"
                style="color:var(--color-ink);">{{ $title }}</h1>
            <p class="text-base font-light leading-relaxed" style="color:var(--color-ink-3); max-width:38rem;">
                {{ $lede }}
            </p>
            {{-- Meta strip --}}
            <div class="mt-6 flex flex-wrap items-center gap-4">
                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5
                             text-[11px] font-semibold"
                      style="border-color:var(--color-border); color:var(--color-ink-3); background:white;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/>
                        <line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
                    </svg>
                    {{ __('legal.last_updated', ['date' => now()->translatedFormat('F j, Y')]) }}
                </span>
                @if ($badge)
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5
                                 text-[11px] font-bold"
                          style="border-color:var(--color-red-light); background:var(--color-red-light); color:var(--color-red);">
                        {{ $badge }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</section>

{{-- Two-column layout --}}
<section class="border-t bg-white" style="border-color:var(--color-border);">
    <div class="container-page py-14 sm:py-16">
        <div class="grid gap-10 lg:grid-cols-[16rem_1fr] lg:gap-16">

            {{-- ── Sticky TOC ── --}}
            <aside class="hidden lg:block">
                <div class="sticky top-[calc(var(--site-header-height,5rem)+1.5rem)]">
                    <p class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.2em]"
                       style="color:var(--color-ink-3);">Contents</p>
                    <nav aria-label="Document sections">
                        @foreach ($sections as $i => $section)
                            @php $sectionId = 'section-' . ($i + 1); @endphp
                            <a href="#{{ $sectionId }}"
                               class="legal-toc-link"
                               data-toc-link="{{ $sectionId }}">
                                <span class="legal-toc-dot"></span>
                                <span>{{ $section['heading'] }}</span>
                            </a>
                        @endforeach
                    </nav>

                    {{-- Contact shortcut --}}
                    <div class="mt-8 rounded-2xl border p-4"
                         style="border-color:var(--color-border); background:var(--color-surface-2);">
                        <p class="text-xs font-bold" style="color:var(--color-ink);">Questions?</p>
                        <p class="mt-1 text-xs font-light" style="color:var(--color-ink-3);">
                            We're happy to clarify anything in this document.
                        </p>
                        <a href="{{ route('contact') }}"
                           class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold transition hover:underline"
                           style="color:var(--color-red);">
                            Contact us →
                        </a>
                    </div>
                </div>
            </aside>

            {{-- ── Content sections ── --}}
            <div class="min-w-0 space-y-6">
                @foreach ($sections as $i => $section)
                    @php $sectionId = 'section-' . ($i + 1); @endphp
                    <div id="{{ $sectionId }}"
                         class="legal-section-block rounded-2xl border p-6 sm:p-8"
                         style="border-color:var(--color-border); background:var(--color-surface);">

                        {{-- Heading row --}}
                        <div class="mb-4 flex items-start gap-4">
                            <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-xl
                                         text-sm font-bold"
                                  style="background:var(--color-red-light); color:var(--color-red);">
                                {{ $i + 1 }}
                            </span>
                            <h2 class="pt-0.5 text-lg font-bold leading-snug sm:text-xl"
                                style="color:var(--color-ink);">
                                {{-- Strip leading "N. " numbering since we show the badge --}}
                                {{ preg_replace('/^\d+\.\s*/', '', $section['heading']) }}
                            </h2>
                        </div>

                        {{-- Body text or list --}}
                        @if (!empty($section['items']))
                            <ul class="ml-12 space-y-2.5">
                                @foreach ($section['items'] as $item)
                                    <li class="flex items-start gap-2.5 text-sm font-light leading-relaxed"
                                        style="color:var(--color-ink-2);">
                                        <span class="mt-1.5 size-1.5 shrink-0 rounded-full"
                                              style="background:var(--color-red);"></span>
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="ml-12 text-sm font-light leading-relaxed sm:text-base"
                               style="color:var(--color-ink-2);">
                                {!! str_replace(
                                    ':email',
                                    '<a href="mailto:' . e(config('site.email')) . '"
                                        class="font-semibold transition hover:underline"
                                        style="color:var(--color-red);">' . e(config('site.email')) . '</a>',
                                    e($section['body'] ?? '')
                                ) !!}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>

        </div>
    </div>
</section>

{{-- Bottom CTA --}}
<section class="border-t" style="border-color:var(--color-border); background:var(--color-surface-2);">
    <div class="container-page py-12">
        <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
            <div>
                <p class="text-base font-bold" style="color:var(--color-ink);">Still have questions?</p>
                <p class="mt-1 text-sm font-light" style="color:var(--color-ink-3);">
                    Our team is happy to clarify any part of this document.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('contact') }}" class="btn btn-primary gap-2 px-6 py-3 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                    Contact Us
                </a>
                <a href="mailto:{{ config('site.email') }}" class="btn btn-ghost px-6 py-3 text-sm">
                    {{ config('site.email') }}
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Scroll-spy script --}}
<script>
(function () {
    var links   = document.querySelectorAll('[data-toc-link]');
    var blocks  = document.querySelectorAll('.legal-section-block');
    if (!links.length || !blocks.length) return;

    var setActive = function (id) {
        links.forEach(function (l) {
            var active = l.dataset.tocLink === id;
            l.classList.toggle('is-active', active);
        });
    };

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) setActive(entry.target.id);
        });
    }, {
        rootMargin: '-10% 0px -75% 0px',
        threshold: 0,
    });

    blocks.forEach(function (b) { observer.observe(b); });

    // Highlight first on load
    if (blocks[0]) setActive(blocks[0].id);
}());
</script>
