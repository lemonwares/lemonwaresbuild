@extends('layouts.admin')

@section('title', 'Audit log — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Audit log"
        lede="Every change made in the admin panel, plus admin sign-ins. Passwords, tokens and secrets are never stored."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Audit log']]"
        class="mb-5"
    />

    <section class="admin-panel admin-panel-flush">
        <form method="GET" action="{{ route('admin.audit-log.index') }}" class="admin-filter-bar">
            <input type="search" name="q" value="{{ $search }}" class="admin-input" placeholder="Action, URL, email or record ID">
            <select name="admin" class="admin-input">
                <option value="">All staff</option>
                @foreach ($admins as $adminOption)
                    <option value="{{ $adminOption->id }}" @selected($adminId === $adminOption->id)>{{ $adminOption->name }}</option>
                @endforeach
            </select>
            <select name="kind" class="admin-input">
                <option value="">Everything</option>
                <option value="logins" @selected($kind === 'logins')>Sign-ins only</option>
                <option value="failed" @selected($kind === 'failed')>Failed sign-ins &amp; errors</option>
            </select>
            <button class="admin-btn-primary" type="submit">Apply</button>
            @if ($search || $adminId || $kind)
                <a href="{{ route('admin.audit-log.index') }}" class="admin-btn-ghost">Clear</a>
            @endif
        </form>

        <div class="admin-table-wrap is-full">
            <table class="admin-table is-full">
                <thead>
                    <tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Result</th><th>IP</th><th>Details</th></tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <td>{{ $log->admin?->name ?: ($log->admin_email ?: '—') }}</td>
                            <td><strong>{{ str_replace(['.', '-', '_'], ' ', $log->action) }}</strong></td>
                            <td>{{ $log->subject_type ? $log->subject_type.' #'.$log->subject_id : '—' }}</td>
                            <td>
                                @if ($log->action === 'login_failed' || ($log->status_code && $log->status_code >= 400))
                                    <span class="admin-mini-status" style="color:#b91c1c;">{{ $log->status_code ?: 'failed' }}</span>
                                @else
                                    <span class="admin-mini-status">{{ $log->status_code ?: 'ok' }}</span>
                                @endif
                            </td>
                            <td class="font-mono text-xs">{{ $log->ip_address }}</td>
                            <td>
                                @if ($log->payload)
                                    <details>
                                        <summary class="cursor-pointer">View</summary>
                                        <pre class="mt-2 max-w-md overflow-x-auto whitespace-pre-wrap text-xs">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="admin-table-empty">Nothing logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="admin-pagination">{{ $logs->links() }}</div>
        @endif
    </section>
@endsection
