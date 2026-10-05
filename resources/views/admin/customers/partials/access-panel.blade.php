{{-- Admin account controls for a customer. Expects: $customer, $activities. --}}
@php
    $bag = $errors->getBag('customerAction');
    $canImpersonate = \App\Support\AdminPermissions::currentCan('impersonate');
@endphp

@if ($bag->any())
    <p class="rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $bag->first() }}</p>
@endif

@if ($customer->suspended_at)
    <p class="rounded-xl border border-rose/30 bg-rose/5 px-4 py-3 text-sm text-rose">
        <strong>Suspended</strong> since {{ $customer->suspended_at->format('d M Y H:i') }}{{ $customer->suspended_reason ? ' — '.$customer->suspended_reason : '' }}.
        The customer and their team cannot log in.
    </p>
@endif

<div class="admin-customer-grid">
    <section class="admin-panel">
        <div class="admin-panel-toolbar compact">
            <h2 class="admin-dash-panel-title">Account access</h2>
            <div class="admin-pill-row">
                @if ($customer->email_verified_at)
                    <span class="admin-pill is-ok">Email verified</span>
                @else
                    <span class="admin-pill is-info">Email not verified</span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($canImpersonate && ! $customer->suspended_at)
                <form method="POST" action="{{ route('admin.customers.impersonate', $customer) }}">
                    @csrf
                    <button type="submit" class="admin-btn-primary" onclick="return confirm('Open this customer\'s account as them? The customer will see this in their activity log.');">Log in as customer</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.customers.password-reset', $customer) }}" data-submit-form>
                @csrf
                <button type="submit" class="admin-btn-ghost">Email password reset link</button>
            </form>
            <form method="POST" action="{{ route('admin.customers.verify-email', $customer) }}">
                @csrf
                <button type="submit" class="admin-btn-ghost">{{ $customer->email_verified_at ? 'Mark email unverified' : 'Mark email verified' }}</button>
            </form>
            @if ($customer->suspended_at)
                <form method="POST" action="{{ route('admin.customers.unsuspend', $customer) }}">
                    @csrf
                    <button type="submit" class="admin-btn-primary">Restore access</button>
                </form>
            @endif
        </div>

        <details class="mt-5 border-t border-border pt-4">
            <summary class="cursor-pointer text-sm font-semibold">Set a new password</summary>
            <form method="POST" action="{{ route('admin.customers.password', $customer) }}" class="mt-3 space-y-3">
                @csrf
                @method('PUT')
                <input type="password" name="password" class="admin-input w-full" placeholder="New password (10+ characters)" autocomplete="new-password" required>
                <input type="password" name="password_confirmation" class="admin-input w-full" placeholder="Repeat password" autocomplete="new-password" required>
                <button type="submit" class="admin-btn-ghost">Save password</button>
            </form>
        </details>

        @unless ($customer->suspended_at)
            <details class="mt-4 border-t border-border pt-4">
                <summary class="cursor-pointer text-sm font-semibold text-rose">Suspend account</summary>
                <form method="POST" action="{{ route('admin.customers.suspend', $customer) }}" class="mt-3 space-y-3">
                    @csrf
                    <input type="text" name="reason" class="admin-input w-full" placeholder="Reason (internal, required)" maxlength="500" required>
                    <button type="submit" class="admin-btn-danger" onclick="return confirm('Suspend this account? The customer and their team will be signed out.');">Suspend</button>
                </form>
            </details>
        @endunless
    </section>

    <section class="admin-panel">
        <div class="admin-panel-toolbar compact">
            <h2 class="admin-dash-panel-title">Internal notes &amp; tags</h2>
        </div>
        <form method="POST" action="{{ route('admin.customers.notes', $customer) }}" class="space-y-3">
            @csrf
            @method('PUT')
            <label class="admin-field">
                <span>Tags (comma separated, e.g. vip, reseller, slow payer)</span>
                <input type="text" name="admin_tags" value="{{ old('admin_tags', implode(', ', (array) ($customer->admin_tags ?? []))) }}" class="admin-input" maxlength="500">
            </label>
            <label class="admin-field">
                <span>Notes (only admins see these)</span>
                <textarea name="admin_notes" rows="5" class="admin-input">{{ old('admin_notes', $customer->admin_notes) }}</textarea>
            </label>
            <button type="submit" class="admin-btn-ghost">Save notes</button>
        </form>
    </section>
</div>

<div class="admin-customer-grid">
    <section class="admin-panel">
        <div class="admin-panel-toolbar compact">
            <h2 class="admin-dash-panel-title">Customer's team</h2>
        </div>
        @if ($customer->accountStaffMembers->isEmpty() && $customer->accountInvites->isEmpty())
            <p class="admin-table-empty">No team members or invites.</p>
        @else
            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead><tr><th>Person</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($customer->accountStaffMembers as $member)
                            <tr>
                                <td><strong>{{ $member->name }}</strong><br>{{ $member->email }}</td>
                                <td><span class="admin-mini-status">member</span></td>
                                <td class="admin-table-actions">
                                    <form method="POST" action="{{ route('admin.customers.staff.destroy', [$customer, $member]) }}" onsubmit="return confirm('Remove {{ $member->name }} from this account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        @foreach ($customer->accountInvites->filter(fn ($invite) => $invite->isPending()) as $invite)
                            <tr>
                                <td>{{ $invite->email }}</td>
                                <td><span class="admin-mini-status">{{ $invite->isExpired() ? 'invite expired' : 'invited' }}</span></td>
                                <td class="admin-table-actions">
                                    <form method="POST" action="{{ route('admin.customers.invites.destroy', [$customer, $invite]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose">Cancel invite</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="admin-panel">
        <div class="admin-panel-toolbar compact">
            <h2 class="admin-dash-panel-title">Recent account activity</h2>
        </div>
        @if ($activities->isEmpty())
            <p class="admin-table-empty">No activity recorded yet.</p>
        @else
            <ul class="space-y-3 text-sm">
                @foreach ($activities as $activity)
                    <li>
                        <p><strong>{{ $activity->title }}</strong>{{ $activity->body ? ' — '.$activity->body : '' }}</p>
                        <p class="text-on-blush/60">{{ $activity->created_at?->format('d M Y H:i') }} · {{ $activity->actor_type }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
