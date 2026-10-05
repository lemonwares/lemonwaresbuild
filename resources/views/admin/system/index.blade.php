@extends('layouts.admin')

@section('title', 'System — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="System"
        lede="Connected services, background jobs, scheduled tasks, recent errors and backups."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'System']]"
        class="mb-5"
    >
        <x-slot:actions>
            <div class="admin-customers-toolbar">
                <form method="POST" action="{{ route('admin.system.clear-cache') }}">
                    @csrf
                    <button type="submit" class="admin-btn-ghost">Clear cache</button>
                </form>
                @if ($canBackup && \App\Support\AdminPermissions::currentUser()?->isSuperAdmin())
                    <form method="POST" action="{{ route('admin.system.backup') }}">
                        @csrf
                        <button type="submit" class="admin-btn-primary">Download database backup</button>
                    </form>
                @endif
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    @if (session('task_result'))
        @php($result = session('task_result'))
        <div @class(['mb-5 rounded-xl border px-4 py-3 text-sm', 'border-emerald-200 bg-emerald-50' => $result['ok'], 'border-rose/20 bg-rose/5' => ! $result['ok']])>
            <p class="font-semibold">{{ $result['task'] }} {{ $result['ok'] ? 'finished' : 'failed' }}</p>
            <pre class="mt-2 whitespace-pre-wrap text-xs">{{ $result['output'] }}</pre>
        </div>
    @endif

    <div class="admin-page-stack">
        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Connected services</h2></div>
                <ul class="space-y-2 text-sm">
                    @foreach ($services as $service)
                        <li class="flex items-center justify-between gap-3">
                            <a href="{{ $service['href'] }}">{{ $service['name'] }}</a>
                            <span @class(['admin-pill', 'is-ok' => $service['ok'], 'is-info' => ! $service['ok']])>{{ $service['ok'] ? 'Set up' : 'Not set up' }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="admin-muted mt-3">"Set up" means the credentials are saved. Use each settings page's connection test to check they work.</p>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Server</h2></div>
                <dl class="admin-dl">
                    @foreach ($environment as $label => $value)
                        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                    @endforeach
                    <div><dt>Jobs waiting</dt><dd>{{ $pendingJobs }}</dd></div>
                    <div><dt>Failed jobs</dt><dd>{{ $failedJobs->count() }}</dd></div>
                </dl>
            </section>
        </div>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Scheduled tasks</h2></div>
            <div class="space-y-3">
                @foreach ($tasks as $command => $description)
                    <form method="POST" action="{{ route('admin.system.run-task') }}" class="flex flex-wrap items-center justify-between gap-3" data-submit-form>
                        @csrf
                        <input type="hidden" name="task" value="{{ $command }}">
                        <span class="text-sm"><code>{{ $command }}</code> — {{ $description }}</span>
                        <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button onclick="return confirm('Run {{ $command }} now?');">
                            <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                            <span data-submit-label>Run now</span>
                            <span class="hidden" data-submit-loading>Running…</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>

        <section class="admin-panel admin-panel-flush">
            <div class="admin-panel-toolbar"><h2 class="admin-dash-panel-title">Failed background jobs</h2></div>
            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead><tr><th>When</th><th>Job</th><th>Error</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($failedJobs as $job)
                            <tr>
                                <td>{{ $job->failed_at }}</td>
                                <td>{{ class_basename($job->name) }}</td>
                                <td class="text-xs">{{ \Illuminate\Support\Str::limit($job->error, 200) }}</td>
                                <td class="admin-table-actions">
                                    <div class="admin-customers-toolbar">
                                        <form method="POST" action="{{ route('admin.system.retry-job') }}">
                                            @csrf
                                            <input type="hidden" name="uuid" value="{{ $job->uuid }}">
                                            <button type="submit">Retry</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.system.forget-job') }}">
                                            @csrf
                                            <input type="hidden" name="uuid" value="{{ $job->uuid }}">
                                            <button type="submit" class="text-rose">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="admin-table-empty">No failed jobs.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Recent log entries</h2></div>
            @if ($logLines === [])
                <p class="admin-table-empty">The log is empty.</p>
            @else
                <pre class="max-h-[32rem] overflow-auto whitespace-pre-wrap text-xs">@foreach (array_reverse($logLines) as $line){{ $line }}
@endforeach</pre>
            @endif
        </section>
    </div>
@endsection
