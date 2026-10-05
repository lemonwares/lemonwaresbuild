{{-- Hetzner VPS controls. Expects: $lead, $hetznerConfigured, $server, $cpu, $serverTypes, $unlinkedServers, $serverActions. --}}
@if ($lead->isVps() || $lead->hetzner_server_id)
    <section class="admin-panel">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">VPS server (Hetzner)</h2>
                <p class="admin-dash-panel-lede">
                    @if (! $hetznerConfigured)
                        Hetzner is not connected. <a href="{{ route('admin.hetzner-settings.index') }}" class="font-semibold">Add the API token</a> to control servers from here.
                    @elseif ($server)
                        {{ $server['name'] }} · {{ $server['type'] }} ({{ $server['cores'] }} vCPU, {{ $server['memory'] }} GB RAM, {{ $server['disk'] }} GB) · {{ $server['location'] }}
                    @elseif ($lead->hetzner_server_id)
                        Linked to server #{{ $lead->hetzner_server_id }}, but Hetzner did not return it. It may have been deleted.
                    @else
                        Not linked to a Hetzner server yet.
                    @endif
                </p>
            </div>
            @if ($server)
                <span @class(['admin-pill', 'is-ok' => $server['status'] === 'running', 'is-info' => $server['status'] !== 'running'])>{{ $server['status'] }}</span>
            @endif
        </div>

        @if (session('rescue_root_password'))
            <p class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Rescue root password (shown once): <code class="font-mono">{{ session('rescue_root_password') }}</code>. Reboot the server to boot into rescue.
            </p>
        @endif

        @if ($server)
            <dl class="admin-dl">
                <div><dt>IPv4</dt><dd class="font-mono">{{ $server['ipv4'] ?: '—' }}</dd></div>
                <div><dt>CPU (last hour)</dt><dd>{{ $cpu !== null ? $cpu.'%' : '—' }}</dd></div>
                <div><dt>Image</dt><dd>{{ $server['image'] ?: '—' }}</dd></div>
                <div><dt>Rescue mode</dt><dd>{{ $server['rescue'] ? 'On' : 'Off' }}</dd></div>
            </dl>

            <div class="mt-5 flex flex-wrap gap-2 border-t border-border pt-5">
                @foreach (['poweron', 'shutdown', 'reboot', $server['rescue'] ? 'disable_rescue' : 'enable_rescue'] as $simpleAction)
                    <form method="POST" action="{{ route('admin.hosting-leads.server-action', $lead) }}" data-submit-form>
                        @csrf
                        <input type="hidden" name="action" value="{{ $simpleAction }}">
                        <button type="submit" class="admin-btn-ghost" onclick="return confirm('{{ $serverActions[$simpleAction] }} for {{ $server['name'] }}?');">{{ $serverActions[$simpleAction] }}</button>
                    </form>
                @endforeach
                <form method="POST" action="{{ route('admin.hosting-leads.server-action', $lead) }}" class="flex gap-2" data-submit-form>
                    @csrf
                    <input type="hidden" name="action" value="create_image">
                    <input type="text" name="description" class="admin-input admin-input-sm" placeholder="Snapshot name (optional)" maxlength="120">
                    <button type="submit" class="admin-btn-ghost">Take snapshot</button>
                </form>
            </div>

            <div class="admin-customer-grid mt-5 border-t border-border pt-5">
                <form method="POST" action="{{ route('admin.hosting-leads.server-action', $lead) }}" class="space-y-3" data-submit-form>
                    @csrf
                    <input type="hidden" name="action" value="change_type">
                    <p class="admin-dash-panel-title">Resize</p>
                    <p class="admin-dash-panel-lede">The server must be powered off first. Hetzner bills the new size from now.</p>
                    <select name="server_type" class="admin-input w-full" required>
                        @foreach ($serverTypes as $type)
                            <option value="{{ $type['name'] }}" @selected($type['name'] === $server['type'])>{{ $type['description'] }}</option>
                        @endforeach
                    </select>
                    <label class="admin-check">
                        <input type="checkbox" name="upgrade_disk" value="1">
                        <span>Also grow the disk (cannot be shrunk back later)</span>
                    </label>
                    <button type="submit" class="admin-btn-ghost" onclick="return confirm('Resize this server?');">Resize</button>
                </form>

                <form method="POST" action="{{ route('admin.hosting-leads.server-action', $lead) }}" class="space-y-3" data-submit-form>
                    @csrf
                    <p class="admin-dash-panel-title text-rose">Danger zone</p>
                    <p class="admin-dash-panel-lede">Hard power off, hard reset and rebuild need the server name typed to confirm. Rebuild erases all data on the disk.</p>
                    <select name="action" class="admin-input w-full">
                        <option value="poweroff">{{ $serverActions['poweroff'] }}</option>
                        <option value="reset">{{ $serverActions['reset'] }}</option>
                        <option value="rebuild">{{ $serverActions['rebuild'] }}</option>
                    </select>
                    <input type="text" name="image" class="admin-input w-full" placeholder="Image for rebuild, e.g. ubuntu-24.04" maxlength="80">
                    <input type="text" name="confirm_name" class="admin-input w-full" placeholder="Type {{ $server['name'] }} to confirm" maxlength="190" required>
                    <button type="submit" class="admin-btn-danger">Run</button>
                </form>
            </div>
        @endif

        @if ($hetznerConfigured)
            <form method="POST" action="{{ route('admin.hosting-leads.server', $lead) }}" class="mt-5 flex flex-wrap items-end gap-2 border-t border-border pt-5" data-submit-form>
                @csrf
                @method('PUT')
                <label class="admin-field">
                    <span>{{ $lead->hetzner_server_id ? 'Change linked server ID' : 'Link to a Hetzner server' }}</span>
                    @if ($unlinkedServers !== [])
                        <select name="hetzner_server_id" class="admin-input">
                            @foreach ($unlinkedServers as $candidate)
                                <option value="{{ $candidate['id'] }}">#{{ $candidate['id'] }} · {{ $candidate['name'] }} · {{ $candidate['ipv4'] }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="number" name="hetzner_server_id" value="{{ $lead->hetzner_server_id }}" min="1" class="admin-input" placeholder="Server ID">
                    @endif
                </label>
                <button type="submit" class="admin-btn-ghost">Save link</button>
            </form>
        @endif
    </section>
@endif
