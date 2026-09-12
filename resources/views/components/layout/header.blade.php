<div data-site-header>

    <header class="site-header-fixed site-header-bar" data-site-header-bar>
        <div class="container-page flex items-center gap-3 py-3 sm:py-3.5">

            {{-- Logo --}}
            <x-layout.logo />

            {{-- Desktop nav centred --}}
            <x-layout.nav />

            {{-- Right cluster --}}
            <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">

                {{-- Locale switcher — hidden on smallest screens --}}
                <div class="hidden sm:block">
                    <x-layout.locale-switcher />
                </div>

                {{-- Client login / account CTA — desktop only --}}
                <div class="hidden lg:block">
                    <x-layout.account-session />
                </div>

                {{-- Hamburger — mobile / tablet --}}
                <button
                    type="button"
                    class="relative inline-flex size-10 items-center justify-center rounded-full
                           transition hover:bg-black/5 lg:hidden"
                    data-mobile-nav-toggle
                    data-open-label="{{ __('site.common.open_menu') }}"
                    data-close-label="{{ __('site.common.close_menu') }}"
                    aria-controls="mobile-nav"
                    aria-expanded="false"
                    aria-label="{{ __('site.common.open_menu') }}"
                >
                    {{-- Three animated lines --}}
                    <span class="flex flex-col items-end justify-center gap-[5px]" data-hamburger aria-hidden="true">
                        <span class="h-[2px] w-5 rounded-full bg-current transition-all duration-300" data-ham-1></span>
                        <span class="h-[2px] w-3.5 rounded-full bg-current transition-all duration-300" data-ham-2></span>
                        <span class="h-[2px] w-5 rounded-full bg-current transition-all duration-300" data-ham-3></span>
                    </span>
                </button>
            </div>
        </div>
    </header>

    {{-- Spacer so fixed header doesn't overlap content --}}
    <div class="site-header-spacer" data-site-header-spacer aria-hidden="true"></div>

    {{-- Mobile nav overlay --}}
    <div
        id="mobile-nav"
        class="mobile-nav-overlay lg:hidden"
        data-mobile-nav
        aria-hidden="true"
    >
        {{-- Red top stripe inside overlay so it aligns with header stripe --}}
        <div class="absolute inset-x-0 top-0 h-[2px]" style="background:var(--color-red);" aria-hidden="true"></div>

        <div class="mobile-nav-overlay-inner">
            <div class="container-page">
                <x-layout.mobile-nav />
            </div>
        </div>
    </div>

</div>

{{-- Scroll-elevation + hamburger animation --}}
<script>
(function () {
    var bar    = document.querySelector('[data-site-header-bar]');
    var toggle = document.querySelector('[data-mobile-nav-toggle]');
    var h1     = toggle && toggle.querySelector('[data-ham-1]');
    var h2     = toggle && toggle.querySelector('[data-ham-2]');
    var h3     = toggle && toggle.querySelector('[data-ham-3]');

    // Elevate header on scroll
    if (bar) {
        var onScroll = function () {
            bar.classList.toggle('is-elevated', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // Animate hamburger → X
    if (toggle) {
        new MutationObserver(function () {
            var open = toggle.getAttribute('aria-expanded') === 'true';
            if (!h1 || !h2 || !h3) return;
            h1.style.transform = open ? 'translateY(7px) rotate(45deg)'  : '';
            h2.style.opacity   = open ? '0' : '1';
            h2.style.transform = open ? 'scaleX(0)' : '';
            h3.style.transform = open ? 'translateY(-7px) rotate(-45deg)' : '';
            h3.style.width     = open ? '1.25rem' : '';
        }).observe(toggle, { attributes: true, attributeFilter: ['aria-expanded'] });
    }
}());
</script>
