<section class="section-band border-t border-border">
    <div class="container-page py-14 sm:py-16">
        <div class="mb-10 max-w-2xl">
            <p class="section-label mb-3">{{ __('pages.about.services_label') }}</p>
            <h2 class="heading">{{ __('pages.about.services_title') }}</h2>
            <p class="lede mt-4">{{ __('pages.about.services_lede') }}</p>
        </div>

        <div>
            @foreach (config('site.services') as $index => $service)
                @php
                    $key = $service['key'];
                    $copy = __('pages.services.' . $key);
                    $href = null;
                    if (! empty($service['route'])) {
                        $href = route($service['route']);
                        if (! empty($service['fragment'])) {
                            $href .= '#' . $service['fragment'];
                        }
                    }
                @endphp
                <x-ui.service-row
                    :title="$copy['title']"
                    :description="$copy['description']"
                    :highlights="$copy['highlights'] ?? []"
                    :image="$service['image'] ?? null"
                    :icon="$service['icon'] ?? 'zap'"
                    :tone="$service['tone'] ?? 'blush'"
                    :reverse="$index % 2 === 1"
                    :href="$href"
                    :cta="$copy['cta'] ?? null"
                />
            @endforeach
        </div>
    </div>
</section>
