<form
    method="GET"
    action="{{ route('domain') }}"
    {{ $attributes->class('header-domain-search') }}
    role="search"
    data-header-domain-search
>
    <input type="hidden" name="tab" value="register">
    <label class="sr-only" for="header-domain-q">
        {{ __('site.nav.domain_search_aria') }}
    </label>
    <div class="header-domain-search-field">
        <x-ui.icons.search class="header-domain-search-icon size-4" />
        <input
            id="header-domain-q"
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ __('site.nav.domain_search_placeholder') }}"
            class="header-domain-search-input"
            autocomplete="off"
            spellcheck="false"
            enterkeyhint="search"
            data-header-domain-input
        >
        <button
            type="button"
            class="header-domain-search-clear {{ filled(request('q')) ? '' : 'hidden' }}"
            aria-label="{{ __('site.nav.domain_search_clear') }}"
            title="{{ __('site.nav.domain_search_clear') }}"
            data-header-domain-clear
        >
            <x-ui.icons.x class="size-4" />
        </button>
        <button
            type="submit"
            class="header-domain-search-submit"
            aria-label="{{ __('site.nav.domain_search_submit') }}"
            data-header-domain-submit
            data-loading-label="{{ __('site.nav.domain_search_loading') }}"
        >
            <span class="header-domain-search-spinner" aria-hidden="true"></span>
            <span class="header-domain-search-label">{{ __('site.nav.domain_search_submit') }}</span>
        </button>
    </div>
</form>
