{{-- Access level per admin area, grouped like the menu. Expects: $permissionOptions, $selectedLevels. --}}
@php
    $sections = [
        'Overview' => ['dashboard', 'reports'],
        'Sales' => ['orders', 'email_orders', 'hosting_leads', 'coupons'],
        'Customers & support' => ['customers', 'impersonate', 'support', 'subscribers', 'campaigns'],
        'Catalog' => ['hosting_prices', 'email_catalog'],
        'Website' => ['content', 'blog', 'projects', 'case_studies', 'team', 'careers'],
        'Integrations' => ['whmcs', 'flutterwave', 'zeptomail', 'email_providers', 'cloudflare', 'hetzner', 'cloudinary'],
        'Admin' => ['staff', 'audit_log', 'site_settings', 'system'],
    ];
    $listed = array_merge(...array_values($sections));
    $other = array_values(array_diff(array_keys($permissionOptions), $listed));
    if ($other !== []) {
        $sections['Other'] = $other;
    }
    $levels = \App\Support\AdminPermissions::LEVEL_LABELS;
@endphp

<div class="space-y-4" data-permission-levels>
    @foreach ($sections as $section => $keys)
        @php($keys = array_values(array_filter($keys, fn ($k) => isset($permissionOptions[$k]))))
        @continue($keys === [])
        <fieldset class="rounded-xl border border-border p-4">
            <legend class="flex w-full flex-wrap items-center justify-between gap-2 px-1">
                <span class="admin-dash-panel-title">{{ $section }}</span>
                <select class="admin-input admin-input-sm" aria-label="Set every {{ $section }} area" onchange="if (this.value) { this.closest('fieldset').querySelectorAll('select[data-level]').forEach(s => s.value = this.value); this.value = ''; }">
                    <option value="">Set whole section…</option>
                    <option value="none">No access</option>
                    @foreach ($levels as $levelKey => $levelLabel)
                        <option value="{{ $levelKey }}">{{ $levelLabel }}</option>
                    @endforeach
                </select>
            </legend>
            <div class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($keys as $key)
                    <label class="admin-field mt-0">
                        <span>{{ $permissionOptions[$key] }}</span>
                        <select name="permission_levels[{{ $key }}]" class="admin-input admin-input-sm" data-level>
                            <option value="none">No access</option>
                            @foreach ($levels as $levelKey => $levelLabel)
                                <option value="{{ $levelKey }}" @selected(($selectedLevels[$key] ?? 'none') === $levelKey)>{{ $levelLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach
</div>
