{{-- CONTACT CTA — full ink/black band, centered, two contact cards --}}
<section id="contact" {{ $attributes->class('relative overflow-hidden border-t') }}
         style="border-color:var(--color-border); background:var(--color-ink);">

    {{-- Soft red glow top-center --}}
    <div class="pointer-events-none absolute left-1/2 top-0 h-64 w-[40rem] -translate-x-1/2
                -translate-y-1/2 rounded-full blur-[100px]"
         style="background:rgba(220,38,38,0.12);" aria-hidden="true"></div>

    <div class="container-page relative z-10 py-20 sm:py-24">

        {{-- Headline --}}
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <p class="section-label mb-4" style="color:rgba(255,255,255,0.35);">
                {{ __('site.home.contact_label') }}
            </p>
            <h2 class="mb-4 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                {{ __('site.home.contact_title') }}
            </h2>
            <p class="text-base font-light leading-relaxed" style="color:rgba(255,255,255,0.50);">
                {{ __('site.home.contact_lede') }}
            </p>
        </div>

        {{-- Contact method cards --}}
        <div class="mx-auto mb-10 grid max-w-xl gap-4 sm:grid-cols-2">

            {{-- Email --}}
            <a href="mailto:{{ config('site.email') }}"
               class="group flex items-center gap-4 rounded-2xl border p-5 transition-all duration-200
                      hover:border-red hover:bg-white/5"
               style="border-color:rgba(255,255,255,0.10);">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl transition group-hover:bg-red"
                      style="background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.7);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="20" height="16" x="2" y="4" rx="2"/>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.15em]"
                       style="color:rgba(255,255,255,0.35);">Email</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-white">
                        {{ config('site.email') }}
                    </p>
                </div>
            </a>

            {{-- WhatsApp / Phone --}}
            <a href="{{ config('site.whatsapp') }}" target="_blank" rel="noopener noreferrer"
               class="group flex items-center gap-4 rounded-2xl border p-5 transition-all duration-200
                      hover:border-red hover:bg-white/5"
               style="border-color:rgba(255,255,255,0.10);">
                <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl transition group-hover:bg-red"
                      style="background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.7);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8 19.79 19.79 0 012 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.15em]"
                       style="color:rgba(255,255,255,0.35);">WhatsApp</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-white">
                        {{ config('site.phone') }}
                    </p>
                </div>
            </a>

        </div>

        {{-- Secondary CTA --}}
        <div class="text-center">
            <a href="{{ route('contact') }}"
               class="inline-flex items-center gap-2 rounded-full border px-7 py-3 text-sm font-bold
                      transition-all duration-200 hover:-translate-y-0.5"
               style="border-color:rgba(255,255,255,0.15); color:rgba(255,255,255,0.65);"
               onmouseover="this.style.borderColor='rgba(220,38,38,0.6)';this.style.color='#fff';"
               onmouseout="this.style.borderColor='rgba(255,255,255,0.15)';this.style.color='rgba(255,255,255,0.65)';">
                {{ __('site.pages.contact_cta') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2.5"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

    </div>
</section>
