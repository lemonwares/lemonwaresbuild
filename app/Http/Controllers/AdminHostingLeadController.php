<?php

namespace App\Http\Controllers;

use App\Models\AdminOrderEvent;
use App\Models\HostingLead;
use App\Models\User;
use App\Support\AdminOrderActions;
use App\Support\AdminPermissions;
use App\Support\HetznerClient;
use App\Support\WhmcsLeadSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminHostingLeadController extends Controller
{
    public const STATUSES = [
        'pending',
        'awaiting_payment',
        'paid',
        'paid_pending_setup',
        'provisioned',
        'payment_failed',
        'cancelled',
        'rejected',
        'refunded',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $assigned = (string) $request->query('assigned', '');

        $leads = HostingLead::query()
            ->with('assignedAdmin')
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('hostname', 'like', '%'.$search.'%')
                        ->orWhere('payment_reference', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($status, self::STATUSES, true), fn (Builder $q) => $q->where('status', $status))
            ->when($assigned === 'me', fn (Builder $q) => $q->where('assigned_admin_id', AdminPermissions::currentUser()?->id))
            ->when($assigned === 'none', fn (Builder $q) => $q->whereNull('assigned_admin_id'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totalLeads = HostingLead::count();
        $pendingLeads = HostingLead::query()->where(function ($q) {
            $q->whereNull('status')->orWhereIn('status', ['pending', 'new', 'open', 'awaiting_payment']);
        })->count();
        $paidLeads = HostingLead::query()->where(function ($q) {
            $q->where('payment_status', 'paid')->orWhereIn('status', ['paid', 'provisioned']);
        })->count();
        $newWeek = HostingLead::query()->where('created_at', '>=', now()->subDays(7))->count();

        return view('admin.hosting-leads.index', [
            'leads' => $leads,
            'totalLeads' => $totalLeads,
            'pendingLeads' => $pendingLeads,
            'paidLeads' => $paidLeads,
            'newWeek' => $newWeek,
            'search' => $search,
            'status' => $status,
            'assigned' => $assigned,
            'statuses' => self::STATUSES,
        ]);
    }

    public function show(HostingLead $hostingLead): View
    {
        $server = null;
        $cpu = null;
        $serverTypes = [];
        $unlinkedServers = [];

        if (HetznerClient::isConfigured()) {
            if ($hostingLead->hetzner_server_id) {
                $server = HetznerClient::server((int) $hostingLead->hetzner_server_id);
                $cpu = $server ? HetznerClient::cpuLastHour((int) $hostingLead->hetzner_server_id) : null;
                $serverTypes = $server ? HetznerClient::serverTypes() : [];
            } elseif ($hostingLead->isVps()) {
                $linked = HostingLead::query()->whereNotNull('hetzner_server_id')->pluck('hetzner_server_id')->all();
                $unlinkedServers = array_values(array_filter(HetznerClient::servers(), fn ($s) => ! in_array($s['id'], $linked, true)));
            }
        }

        return view('admin.hosting-leads.show', [
            'lead' => $hostingLead->load('assignedAdmin', 'user'),
            'events' => AdminOrderEvent::query()
                ->where('orderable_type', $hostingLead->getMorphClass())
                ->where('orderable_id', $hostingLead->id)
                ->with('admin')
                ->latest('id')
                ->get(),
            'admins' => User::query()->where('role', 'admin')->orderBy('name')->get(['id', 'name']),
            'hetznerConfigured' => HetznerClient::isConfigured(),
            'server' => $server,
            'cpu' => $cpu,
            'serverTypes' => $serverTypes,
            'unlinkedServers' => $unlinkedServers,
            'serverActions' => HetznerClient::ACTIONS,
        ]);
    }

    public function edit(HostingLead $hostingLead): View
    {
        return view('admin.hosting-leads.edit', [
            'lead' => $hostingLead,
            'statuses' => self::STATUSES,
            'countries' => config('site.country_options', []),
            'cycles' => array_keys((array) \App\Support\HostingPricing::billingCycles()),
        ]);
    }

    public function update(Request $request, HostingLead $hostingLead): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:160'],
            'billing_address_line_1' => ['nullable', 'string', 'max:190'],
            'billing_address_line_2' => ['nullable', 'string', 'max:190'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_state' => ['nullable', 'string', 'max:120'],
            'billing_postcode' => ['nullable', 'string', 'max:40'],
            'billing_country' => ['nullable', 'string', 'max:8'],
            'plan_name' => ['required', 'string', 'max:120'],
            'spec_label' => ['nullable', 'string', 'max:120'],
            'spec_summary' => ['nullable', 'string', 'max:500'],
            'billing_cycle' => ['nullable', 'string', 'max:40'],
            'amount_usd' => ['nullable', 'numeric', 'min:0'],
            'amount_ngn' => ['nullable', 'numeric', 'min:0'],
            'hostname' => ['nullable', 'string', 'max:190'],
            'ipv4' => ['nullable', 'ip'],
            'panel_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'payment_status' => ['nullable', 'string', 'max:40'],
        ]);

        $before = $hostingLead->only(array_keys($data));
        $hostingLead->fill($data);

        if ($data['status'] === 'cancelled' && ! $hostingLead->cancelled_at) {
            $hostingLead->cancelled_at = now();
        } elseif ($data['status'] !== 'cancelled') {
            $hostingLead->cancelled_at = null;
        }

        $changed = array_keys($hostingLead->getDirty());
        $hostingLead->save();

        if ($changed !== []) {
            AdminOrderActions::log($hostingLead, AdminPermissions::currentUser(), 'edited', 'Edited: '.implode(', ', $changed).'.', [
                'before' => array_intersect_key($before, array_flip($changed)),
            ]);
        }

        return redirect()->route('admin.hosting-leads.show', $hostingLead)->with('status', 'Hosting request updated.');
    }

    public function destroy(HostingLead $hostingLead): RedirectResponse
    {
        $label = $hostingLead->full_name.' · '.$hostingLead->plan_name;
        AdminOrderEvent::query()->where('orderable_type', $hostingLead->getMorphClass())->where('orderable_id', $hostingLead->id)->delete();
        $hostingLead->delete();

        return redirect()->route('admin.hosting-leads.index')->with('status', 'Deleted hosting request: '.$label.'.');
    }

    public function updateNotes(Request $request, HostingLead $hostingLead): RedirectResponse
    {
        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'assigned_admin_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'admin')],
        ]);

        $previousAssignee = $hostingLead->assigned_admin_id;
        $hostingLead->update([
            'admin_notes' => filled($data['admin_notes'] ?? null) ? trim($data['admin_notes']) : null,
            'assigned_admin_id' => $data['assigned_admin_id'] ?? null,
        ]);

        if ((int) $previousAssignee !== (int) $hostingLead->assigned_admin_id) {
            AdminOrderActions::log($hostingLead, AdminPermissions::currentUser(), 'assigned', 'Assigned to '.($hostingLead->fresh('assignedAdmin')->assignedAdmin?->name ?: 'nobody').'.');
        }

        return redirect()->route('admin.hosting-leads.show', $hostingLead)->with('status', 'Notes and assignment saved.');
    }

    public function retryWhmcsSync(HostingLead $hostingLead): RedirectResponse
    {
        WhmcsLeadSync::retry($hostingLead);
        AdminOrderActions::log($hostingLead, AdminPermissions::currentUser(), 'whmcs_retry', 'WHMCS sync retried: '.($hostingLead->fresh()->whmcs_sync_status ?: 'no status').'.');

        return redirect()
            ->route('admin.hosting-leads.show', $hostingLead)
            ->with('status', 'WHMCS sync retried.');
    }

    public function linkServer(Request $request, HostingLead $hostingLead): RedirectResponse
    {
        $data = $request->validate([
            'hetzner_server_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $serverId = $data['hetzner_server_id'] ?? null;
        $updates = ['hetzner_server_id' => $serverId];

        if ($serverId) {
            $server = HetznerClient::server((int) $serverId);
            if (! $server) {
                return back()->withErrors(['vps' => 'Hetzner server #'.$serverId.' was not found with the saved token.']);
            }
            $updates['ipv4'] = $server['ipv4'] ?: $hostingLead->ipv4;
            $updates['hostname'] = $hostingLead->hostname ?: $server['name'];
        }

        $hostingLead->update($updates);
        AdminOrderActions::log($hostingLead, AdminPermissions::currentUser(), 'vps_linked', $serverId ? 'Linked to Hetzner server #'.$serverId.'.' : 'Unlinked from Hetzner server.');

        return redirect()->route('admin.hosting-leads.show', $hostingLead)->with('status', $serverId ? 'Server linked.' : 'Server unlinked.');
    }

    public function serverAction(Request $request, HostingLead $hostingLead): RedirectResponse
    {
        abort_unless($hostingLead->hetzner_server_id, 404);

        $data = $request->validate([
            'action' => ['required', Rule::in(array_keys(HetznerClient::ACTIONS))],
            'server_type' => ['required_if:action,change_type', 'nullable', 'string', 'max:40'],
            'upgrade_disk' => ['nullable', 'boolean'],
            'image' => ['required_if:action,rebuild', 'nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:120'],
            'confirm_name' => ['nullable', 'string', 'max:190'],
        ]);

        if (in_array($data['action'], ['rebuild', 'poweroff', 'reset'], true)) {
            $server = HetznerClient::server((int) $hostingLead->hetzner_server_id);
            if (! $server || trim((string) ($data['confirm_name'] ?? '')) !== $server['name']) {
                return back()->withErrors(['vps' => 'Type the server name exactly to confirm this action.']);
            }
        }

        $result = HetznerClient::action((int) $hostingLead->hetzner_server_id, $data['action'], [
            'server_type' => $data['server_type'] ?? null,
            'upgrade_disk' => $request->boolean('upgrade_disk'),
            'image' => $data['image'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        AdminOrderActions::log($hostingLead, AdminPermissions::currentUser(), 'vps_'.$data['action'], HetznerClient::ACTIONS[$data['action']].': '.$result['message'], array_filter([
            'server_type' => $data['server_type'] ?? null,
            'image' => $data['image'] ?? null,
        ]));

        if (! $result['ok']) {
            return back()->withErrors(['vps' => $result['message']]);
        }

        $redirect = redirect()->route('admin.hosting-leads.show', $hostingLead)->with('status', $result['message']);

        return isset($result['root_password'])
            ? $redirect->with('rescue_root_password', $result['root_password'])
            : $redirect;
    }
}
