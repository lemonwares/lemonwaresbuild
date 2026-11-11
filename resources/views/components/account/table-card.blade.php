@props([
    'title',
    'count' => null,
    'paginator' => null,
    'filtersOpen' => false,
])

<section {{ $attributes->class('acc-card') }} data-acc-card>
    <header class="acc-card-head">
        <div class="acc-card-heading">
            <h1 class="acc-card-title">{{ $title }}</h1>
            @if (! is_null($count))
                <span class="acc-count">{{ trans_choice('account.total_count', $count, ['count' => number_format($count)]) }}</span>
            @endif
        </div>
        <div class="acc-card-tools">
            {{ $tools ?? '' }}
            @isset($filters)
                <button
                    type="button"
                    class="acc-icon-btn"
                    data-acc-filter-toggle
                    aria-expanded="{{ $filtersOpen ? 'true' : 'false' }}"
                    title="{{ __('account.filter') }}"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" />
                    </svg>
                    <span class="sr-only">{{ __('account.filter') }}</span>
                </button>
            @endisset
        </div>
    </header>

    @isset($filters)
        <div class="acc-filters" data-acc-filters @unless ($filtersOpen) hidden @endunless>
            {{ $filters }}
        </div>
    @endisset

    {{ $slot }}

    @if ($paginator && $paginator->total() > 0)
        <x-account.pager :paginator="$paginator" />
    @endif
</section>

@once
    @push('scripts')
        <script>
            document.addEventListener('click', function (event) {
                const button = event.target.closest('[data-acc-filter-toggle]');
                if (!button) return;
                const panel = button.closest('[data-acc-card]')?.querySelector('[data-acc-filters]');
                if (!panel) return;
                panel.hidden = !panel.hidden;
                button.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
                if (!panel.hidden) panel.querySelector('input, select')?.focus();
            });
        </script>
    @endpush
@endonce
