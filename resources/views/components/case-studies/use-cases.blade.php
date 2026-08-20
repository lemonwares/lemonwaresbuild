<section class="section-band border-t border-border">
    <div class="container-page py-14 sm:py-16">
        <div class="mb-10 max-w-2xl">
            <p class="section-label mb-3">{{ __('pages.case_studies.section_label') }}</p>
            <h2 class="heading">{{ __('pages.case_studies.section_title') }}</h2>
            <p class="lede mt-4">{{ __('pages.case_studies.section_lede') }}</p>
        </div>

        <div>
            @foreach ([
                ['icon' => 'code', 'tone' => 'slate', 'image' => 'images/case-studies/web.jpg'],
                ['icon' => 'zap', 'tone' => 'blush', 'image' => 'images/case-studies/hosting.jpg', 'href' => url('/#hosting-plans'), 'cta' => __('site.footer.hosting_plans')],
                ['icon' => 'mail', 'tone' => 'rose', 'image' => 'images/case-studies/email.jpg', 'href' => route('email.plans'), 'cta' => __('site.nav.email')],
                ['icon' => 'smartphone', 'tone' => 'slate', 'image' => 'images/case-studies/mobile.jpg'],
                ['icon' => 'headset', 'tone' => 'blush', 'image' => 'images/case-studies/vps.jpg', 'href' => route('hosting.specifications', ['plan' => 'vps']), 'cta' => __('site.home.vps_cta')],
            ] as $index => $meta)
                @php
                    $study = __('pages.case_studies.items')[$index];
                @endphp
                <x-ui.service-row
                    :title="$study['service']"
                    :description="$study['summary']"
                    :highlights="[
                        __('pages.case_studies.client_label') . ': ' . $study['client'],
                        __('pages.case_studies.outcome_label') . ': ' . $study['result'],
                    ]"
                    :image="$meta['image']"
                    :icon="$meta['icon']"
                    :tone="$meta['tone']"
                    :reverse="$index % 2 === 1"
                    :href="$meta['href'] ?? null"
                    :cta="$meta['cta'] ?? null"
                />
            @endforeach
        </div>
    </div>
</section>
