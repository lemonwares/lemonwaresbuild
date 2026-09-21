@php
    $tabs = [
        ['key' => 'clients', 'label' => 'Clients', 'route' => 'admin.whmcs-console.clients'],
        ['key' => 'services', 'label' => 'Services', 'route' => 'admin.whmcs-console.services'],
        ['key' => 'invoices', 'label' => 'Invoices', 'route' => 'admin.whmcs-console.invoices'],
        ['key' => 'orders', 'label' => 'Orders', 'route' => 'admin.whmcs-console.orders'],
        ['key' => 'tickets', 'label' => 'Tickets', 'route' => 'admin.whmcs-console.tickets'],
        ['key' => 'domains', 'label' => 'Domains', 'route' => 'admin.whmcs-console.domains'],
    ];
@endphp

<nav class="admin-whmcs-console-tabs mb-5" aria-label="WHMCS console sections">
    @foreach ($tabs as $tab)
        <a
            href="{{ route($tab['route']) }}"
            @class(['admin-whmcs-console-tab', 'is-active' => ($section ?? '') === $tab['key']])
        >
            {{ $tab['label'] }}
        </a>
    @endforeach
    <a href="{{ route('admin.whmcs-settings.index') }}" class="admin-whmcs-console-tab is-settings">
        Settings
    </a>
</nav>
