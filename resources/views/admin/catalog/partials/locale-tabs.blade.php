{{-- Language tabs: French/German fields left empty show the English text on the site. --}}
<div class="flex gap-2 border-b border-black/10" role="tablist" data-locale-tabs>
    @foreach ($locales as $code => $name)
        <button type="button" class="px-3 py-2 text-sm font-semibold {{ $loop->first ? 'border-b-2 border-rose text-rose' : 'text-black/60' }}" data-locale-tab="{{ $code }}">{{ $name }}</button>
    @endforeach
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-locale-tabs]').forEach((tabs) => {
                const scope = tabs.closest('form');
                const show = (code) => {
                    scope.querySelectorAll('[data-locale-pane]').forEach((pane) => pane.hidden = pane.dataset.localePane !== code);
                    tabs.querySelectorAll('[data-locale-tab]').forEach((tab) => {
                        const active = tab.dataset.localeTab === code;
                        tab.classList.toggle('border-b-2', active);
                        tab.classList.toggle('border-rose', active);
                        tab.classList.toggle('text-rose', active);
                        tab.classList.toggle('text-black/60', !active);
                    });
                };
                tabs.addEventListener('click', (event) => {
                    const tab = event.target.closest('[data-locale-tab]');
                    if (tab) show(tab.dataset.localeTab);
                });
                show('en');
            });
        </script>
    @endpush
@endonce
