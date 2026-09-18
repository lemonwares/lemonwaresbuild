@extends('layouts.admin')

@section('title', $customer->name . ' — CRM')
@section('hide_auto_breadcrumbs', true)

@php
    $openEditModal = $errors->any() && ! $errors->has('delete');
@endphp

@section('content')
    <x-admin.page-header
        :title="$customer->name"
        :lede="$customer->company ?: 'No company on file'"
        :back-href="route('admin.customers.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Customers', 'href' => route('admin.customers.index')],
            ['label' => $customer->name],
        ]"
        class="mb-5"
    >
        <x-slot:actions>
            <div class="admin-customers-toolbar" data-admin-edit-modal>
                <button type="button" class="admin-btn-primary" data-admin-edit-open>Edit customer</button>

                <div data-confirm-modal>
                    <button type="button" class="admin-btn-danger" data-confirm-open>Delete</button>
                    <div data-confirm-dialog class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/45 px-4">
                        <div class="w-full max-w-md rounded-2xl border border-border bg-white p-6 shadow-2xl">
                            <h3 class="text-lg font-bold text-black">Delete this customer?</h3>
                            <p class="mt-2 text-sm text-on-blush/70">
                                This removes the Lemonwares account
                                @if ($customer->whmcsCustomer)
                                    and closes WHMCS client #{{ $customer->whmcsCustomer->whmcs_client_id }}
                                @endif
                                . Related email orders and contacts will also be removed.
                            </p>
                            <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" data-confirm-form class="mt-6 flex justify-end gap-2">
                                @csrf
                                @method('DELETE')
                                <button type="button" data-confirm-cancel class="admin-btn-ghost">Cancel</button>
                                <button type="button" data-confirm-submit class="admin-btn-danger inline-flex items-center gap-2">
                                    <span class="admin-btn-spinner hidden" data-confirm-spinner></span>
                                    <span data-confirm-label data-loading-text="Deleting…">Yes, delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    class="admin-edit-modal"
                    data-admin-edit-dialog
                    role="dialog"
                    aria-modal="true"
                    aria-label="Edit customer"
                    @unless ($openEditModal) hidden @endunless
                    @class(['is-open' => $openEditModal])
                >
                    <div class="admin-edit-dialog">
                        <div class="admin-edit-head">
                            <div>
                                <h2 class="admin-dash-panel-title">Edit customer</h2>
                                <p class="admin-dash-panel-lede">Updates save locally and push to WHMCS when linked or matched by email.</p>
                            </div>
                            <button type="button" class="admin-icon-btn" data-admin-edit-close aria-label="Close">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                                    <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="admin-edit-form" data-submit-form>
                            @csrf
                            @method('PUT')

                            <div class="admin-edit-grid">
                                <label class="admin-field">
                                    <span>Full name</span>
                                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="admin-input" required>
                                    @error('name') <em>{{ $message }}</em> @enderror
                                </label>
                                <label class="admin-field">
                                    <span>Email</span>
                                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="admin-input" required>
                                    @error('email') <em>{{ $message }}</em> @enderror
                                </label>
                                <label class="admin-field">
                                    <span>Phone</span>
                                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>Job title</span>
                                    <input type="text" name="job_title" value="{{ old('job_title', $customer->job_title) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>Company</span>
                                    <input type="text" name="company" value="{{ old('company', $customer->company) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>Trading name</span>
                                    <input type="text" name="trading_name" value="{{ old('trading_name', $customer->trading_name) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>Website</span>
                                    <input type="text" name="website" value="{{ old('website', $customer->website) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>Industry</span>
                                    <select name="industry" class="admin-input">
                                        <option value="">—</option>
                                        @foreach ($industries as $key => $label)
                                            <option value="{{ $key }}" @selected(old('industry', $customer->industry) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="admin-field">
                                    <span>Tax / VAT</span>
                                    <input type="text" name="tax_id" value="{{ old('tax_id', $customer->tax_id) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>Registration no.</span>
                                    <input type="text" name="registration_number" value="{{ old('registration_number', $customer->registration_number) }}" class="admin-input">
                                </label>
                                <label class="admin-field admin-field-span">
                                    <span>Address line 1</span>
                                    <input type="text" name="billing_address_line_1" value="{{ old('billing_address_line_1', $customer->billing_address_line_1) }}" class="admin-input">
                                </label>
                                <label class="admin-field admin-field-span">
                                    <span>Address line 2</span>
                                    <input type="text" name="billing_address_line_2" value="{{ old('billing_address_line_2', $customer->billing_address_line_2) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>City</span>
                                    <input type="text" name="billing_city" value="{{ old('billing_city', $customer->billing_city) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>State</span>
                                    <input type="text" name="billing_state" value="{{ old('billing_state', $customer->billing_state) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>Postcode</span>
                                    <input type="text" name="billing_postcode" value="{{ old('billing_postcode', $customer->billing_postcode) }}" class="admin-input">
                                </label>
                                <label class="admin-field">
                                    <span>Country</span>
                                    <select name="billing_country" class="admin-input">
                                        <option value="">—</option>
                                        @foreach ($countries as $code => $label)
                                            <option value="{{ $code }}" @selected(old('billing_country', $customer->billing_country) === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>

                            <label class="admin-check">
                                <input type="hidden" name="sync_whmcs" value="0">
                                <input type="checkbox" name="sync_whmcs" value="1" @checked(old('sync_whmcs', '1') == '1')>
                                <span>Also update WHMCS client</span>
                            </label>

                            <div class="admin-edit-actions">
                                <button type="button" class="admin-btn-ghost" data-admin-edit-close>Cancel</button>
                                <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                                    <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                                    <span data-submit-label>Save changes</span>
                                    <span class="hidden" data-submit-loading>Saving…</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($errors->has('delete'))
        <p class="mb-5 rounded-xl border border-rose/30 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first('delete') }}</p>
    @endif

    <div class="admin-customer-detail">
        <section class="admin-dash-metrics admin-customer-stats" aria-label="Customer snapshot">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Email orders</span>
                <span class="admin-metric-value">{{ $customer->emailOrders->count() }}</span>
                <span class="admin-metric-meta">On this account</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Hosting</span>
                <span class="admin-metric-value">{{ $hostingLeads->count() }}</span>
                <span class="admin-metric-meta">VPS / shared leads</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">WHMCS services</span>
                <span class="admin-metric-value">{{ array_sum($whmcsServiceSummary) }}</span>
                <span class="admin-metric-meta">{{ $customer->whmcsCustomer ? 'Client #'.$customer->whmcsCustomer->whmcs_client_id : 'Not linked' }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Contacts</span>
                <span class="admin-metric-value">{{ $customer->contacts->count() }}</span>
                <span class="admin-metric-meta">Notification people</span>
            </div>
        </section>

        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Profile</h2>
                    <div class="admin-pill-row">
                        <span class="admin-pill is-ok">Native</span>
                        @if ($customer->whmcsCustomer)
                            <span class="admin-pill is-info">WHMCS linked</span>
                        @endif
                    </div>
                </div>
                <dl class="admin-dl">
                    <div><dt>Email</dt><dd><a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a></dd></div>
                    <div><dt>Phone</dt><dd>{{ $customer->phone ?: '—' }}</dd></div>
                    <div><dt>Job title</dt><dd>{{ $customer->job_title ?: '—' }}</dd></div>
                    <div><dt>Company</dt><dd>{{ $customer->company ?: '—' }}</dd></div>
                    <div><dt>Trading name</dt><dd>{{ $customer->trading_name ?: '—' }}</dd></div>
                    <div>
                        <dt>Website</dt>
                        <dd>
                            @if ($customer->website)
                                <a href="{{ $customer->website }}" target="_blank" rel="noopener noreferrer">{{ $customer->website }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div><dt>Industry</dt><dd>{{ $customer->industryLabel() ?: '—' }}</dd></div>
                    <div><dt>Tax / VAT</dt><dd>{{ $customer->tax_id ?: '—' }}</dd></div>
                    <div><dt>Registration no.</dt><dd>{{ $customer->registration_number ?: '—' }}</dd></div>
                    <div class="admin-dl-span"><dt>Billing address</dt><dd>{{ $customer->formattedBillingAddress() ?: '—' }}</dd></div>
                    <div><dt>Joined</dt><dd>{{ $customer->created_at?->format('d M Y') }}</dd></div>
                </dl>
            </section>

            <section class="admin-panel admin-panel-span">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Email orders</h2>
                    <a href="{{ route('admin.email-orders.index') }}" class="admin-dash-link">All orders</a>
                </div>
                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Plan</th>
                                <th>Mailboxes</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($customer->emailOrders as $order)
                                <tr>
                                    <td><strong>{{ $order->domain }}</strong></td>
                                    <td>{{ $order->plan_name }} · {{ $order->billing_cycle }}</td>
                                    <td>{{ $order->mailboxes->pluck('address')->join(', ') ?: '—' }}</td>
                                    <td><span class="admin-mini-status">{{ str_replace('_', ' ', $order->status) }}</span></td>
                                    <td class="admin-table-actions">
                                        <a href="{{ route('admin.email-orders.show', $order) }}">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="admin-table-empty">No email orders for this customer yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="admin-panel admin-panel-flush">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">WHMCS services</h2>
                    <p class="admin-dash-panel-lede">Legacy services linked to this customer email.</p>
                </div>
                <form method="GET" action="{{ route('admin.customers.show', $customer) }}" class="admin-filter-search">
                    <select name="service_status" class="admin-input admin-input-sm">
                        @foreach (['all', 'active', 'pending', 'suspended', 'terminated', 'cancelled'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected($serviceStatus === $statusOption)>{{ ucfirst($statusOption) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="admin-btn-ghost">Filter</button>
                </form>
            </div>

            <div class="admin-pill-row admin-panel-pad">
                @foreach (['active', 'pending', 'suspended', 'cancelled', 'terminated', 'unknown'] as $statusKey)
                    <span class="admin-pill">{{ ucfirst($statusKey) }}: {{ (int) ($whmcsServiceSummary[$statusKey] ?? 0) }}</span>
                @endforeach
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Domain / user</th>
                            <th>Cycle</th>
                            <th>Next due</th>
                            <th>Status</th>
                            <th>ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($whmcsServices as $service)
                            <tr>
                                <td><strong>{{ $service->product_name ?: 'Service #'.$service->whmcs_service_id }}</strong></td>
                                <td>{{ $service->domain ?: ($service->username ?: '—') }}</td>
                                <td>{{ $service->billing_cycle ?: '—' }}</td>
                                <td>{{ $service->next_due_date?->format('d M Y') ?: '—' }}</td>
                                <td><span class="admin-mini-status">{{ $service->status ?: 'unknown' }}</span></td>
                                <td>{{ $service->whmcs_service_id }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="admin-table-empty">No WHMCS services linked yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Notification contacts</h2>
                </div>
                @forelse ($customer->contacts as $contact)
                    <div class="admin-mini-card">
                        <div>
                            <strong>{{ $contact->name }}</strong>
                            <p><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></p>
                            <p class="admin-muted">
                                {{ $contact->notify ? 'Receives account emails' : 'Not on the mailing list' }}
                                @if ($contact->unavailable_backup) · Backup if unavailable @endif
                            </p>
                        </div>
                        <span class="admin-mini-status">{{ $contact->roleLabel() }}</span>
                    </div>
                @empty
                    <p class="admin-empty">No extra contacts on this account yet.</p>
                @endforelse
            </section>

            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">VPS & hosting</h2>
                </div>
                @forelse ($hostingLeads as $lead)
                    <a href="{{ route('admin.hosting-leads.show', $lead) }}" class="admin-mini-card is-link">
                        <div>
                            <strong>{{ $lead->displayName() }}</strong>
                            <p>{{ $lead->plan_name }}{{ $lead->spec_label ? ' · '.$lead->spec_label : '' }}</p>
                            @if ($lead->ipv4)
                                <p class="admin-mono">{{ $lead->ipv4 }}</p>
                            @endif
                        </div>
                        <span class="admin-mini-status">{{ $lead->statusLabel() }}</span>
                    </a>
                @empty
                    <p class="admin-empty">No VPS or hosting records yet.</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
