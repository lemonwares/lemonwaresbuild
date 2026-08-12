<div class="relative" data-locale-switcher>
    <label for="locale-switcher" class="sr-only">{{ __('site.common.language') }}</label>
    <select
        id="locale-switcher"
        class="rounded-full border border-border bg-white px-3 py-2 text-xs font-semibold uppercase tracking-widest text-black"
        onchange="window.location.href = this.value"
    >
        @foreach (config('site.locales', []) as $code => $label)
            <option value="{{ route('locale.switch', ['locale' => $code]) }}" @selected(app()->getLocale() === $code)>
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>
