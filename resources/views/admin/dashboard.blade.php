@extends('layouts.admin')

@section('title', 'Admin overview — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Overview"
        lede="Everything moving across Lemonwares — customers, orders, leads, and support — at a glance."
        :back-href="url('/')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Overview']]"
        class="mb-5"
    />

    <div class="admin-dash">
        <section class="admin-dash-metrics" aria-label="Key metrics">
            <a href="{{ route('admin.customers.index') }}" class="admin-metric">
                <span class="admin-metric-label">Customers</span>
                <span class="admin-metric-value">{{ $customersCount }}</span>
                <span class="admin-metric-meta">+{{ $newCustomersWeek }} this week</span>
            </a>
            <a href="{{ route('admin.email-orders.index') }}" class="admin-metric">
                <span class="admin-metric-label">Email orders</span>
                <span class="admin-metric-value">{{ $emailOrdersCount }}</span>
                <span class="admin-metric-meta">{{ $paidEmailOrdersCount }} paid · {{ $pendingEmailSetupCount }} setup</span>
            </a>
            <a href="{{ route('admin.hosting-leads.index') }}" class="admin-metric">
                <span class="admin-metric-label">Hosting leads</span>
                <span class="admin-metric-value">{{ $hostingLeadsCount }}</span>
                <span class="admin-metric-meta">+{{ $newLeadsWeek }} this week</span>
            </a>
            <a href="{{ route('admin.support-tickets.index') }}" class="admin-metric">
                <span class="admin-metric-label">Open tickets</span>
                <span class="admin-metric-value">{{ $openTicketsCount }}</span>
                <span class="admin-metric-meta">Needs attention</span>
            </a>
            <a href="{{ route('admin.subscribers.index') }}" class="admin-metric">
                <span class="admin-metric-label">Subscribers</span>
                <span class="admin-metric-value">{{ $subscribersCount }}</span>
                <span class="admin-metric-meta">Newsletter list</span>
            </a>
            <a href="{{ route('admin.team-members.index') }}" class="admin-metric">
                <span class="admin-metric-label">Team / careers</span>
                <span class="admin-metric-value">{{ $teamMembersCount }}</span>
                <span class="admin-metric-meta">{{ $careerOpeningsCount }} live openings</span>
            </a>
        </section>

        <section class="admin-dash-chart" aria-label="Activity chart">
            <div class="admin-dash-panel-head">
                <div>
                    <h2 class="admin-dash-panel-title">14-day pulse</h2>
                    <p class="admin-dash-panel-lede">Orders, hosting leads, and new customers.</p>
                </div>
                <div class="admin-chart-legend" aria-hidden="true">
                    <span><i class="admin-chart-swatch is-orders"></i> Orders</span>
                    <span><i class="admin-chart-swatch is-leads"></i> Leads</span>
                    <span><i class="admin-chart-swatch is-customers"></i> Customers</span>
                </div>
            </div>

            @php
                $w = 560;
                $h = 160;
                $padX = 8;
                $padY = 12;
                $plotW = $w - ($padX * 2);
                $plotH = $h - ($padY * 2);
                $n = max(count($orderSeries) - 1, 1);

                $toPoints = function (array $series) use ($n, $plotW, $plotH, $padX, $padY, $chartMax): string {
                    return collect($series)->map(function ($value, $i) use ($n, $plotW, $plotH, $padX, $padY, $chartMax) {
                        $x = $padX + ($i / $n) * $plotW;
                        $y = $padY + $plotH - (($value / $chartMax) * $plotH);

                        return round($x, 1) . ',' . round($y, 1);
                    })->implode(' ');
                };

                $orderPoints = $toPoints($orderSeries);
                $leadPoints = $toPoints($leadSeries);
                $customerPoints = $toPoints($customerSeries);
            @endphp

            <div class="admin-chart-frame">
                <svg class="admin-chart-svg" viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="Activity over the last 14 days">
                    @for ($i = 0; $i < 4; $i++)
                        @php $gy = $padY + ($plotH / 3) * $i; @endphp
                        <line class="admin-chart-grid" x1="{{ $padX }}" y1="{{ $gy }}" x2="{{ $w - $padX }}" y2="{{ $gy }}" />
                    @endfor
                    <polyline class="admin-chart-line is-orders" fill="none" points="{{ $orderPoints }}" />
                    <polyline class="admin-chart-line is-leads" fill="none" points="{{ $leadPoints }}" />
                    <polyline class="admin-chart-line is-customers" fill="none" points="{{ $customerPoints }}" />
                </svg>
                <div class="admin-chart-x">
                    <span>{{ $chartDays->first()->format('M j') }}</span>
                    <span>{{ $chartDays->get(7)?->format('M j') }}</span>
                    <span>{{ $chartDays->last()->format('M j') }}</span>
                </div>
            </div>

            <div class="admin-chart-stats">
                <div>
                    <span class="admin-chart-stat-label">Orders / 7d</span>
                    <strong>{{ $newOrdersWeek }}</strong>
                </div>
                <div>
                    <span class="admin-chart-stat-label">Leads / 7d</span>
                    <strong>{{ $newLeadsWeek }}</strong>
                </div>
                <div>
                    <span class="admin-chart-stat-label">Customers / 7d</span>
                    <strong>{{ $newCustomersWeek }}</strong>
                </div>
                <div>
                    <span class="admin-chart-stat-label">Priced specs</span>
                    <strong>{{ $pricedSpecsCount }}</strong>
                </div>
            </div>
        </section>

        <section class="admin-dash-activity" aria-label="Live activity">
            <div class="admin-dash-panel-head">
                <div>
                    <h2 class="admin-dash-panel-title">Live feed</h2>
                    <p class="admin-dash-panel-lede">Latest movement across the platform.</p>
                </div>
            </div>
            <ul class="admin-activity-list">
                @forelse ($activity as $item)
                    <li>
                        <a href="{{ $item['href'] }}" class="admin-activity-item">
                            <span class="admin-activity-dot" aria-hidden="true"></span>
                            <span class="admin-activity-copy">
                                <span class="admin-activity-label">{{ $item['label'] }}</span>
                                <span class="admin-activity-meta">{{ $item['meta'] }}</span>
                            </span>
                            <time datetime="{{ $item['at']?->toIso8601String() }}">{{ $item['at']?->diffForHumans(short: true) }}</time>
                        </a>
                    </li>
                @empty
                    <li class="admin-activity-empty">Nothing yet — activity will show up here.</li>
                @endforelse
            </ul>
        </section>

        <section class="admin-dash-lists" aria-label="Recent records">
            <div class="admin-dash-list-card">
                <div class="admin-dash-panel-head compact">
                    <h2 class="admin-dash-panel-title">Customers</h2>
                    <a href="{{ route('admin.customers.index') }}" class="admin-dash-link">All</a>
                </div>
                @forelse ($recentCustomers as $customer)
                    <a href="{{ route('admin.customers.show', $customer) }}" class="admin-mini-row">
                        <span>
                            <strong>{{ $customer->name }}</strong>
                            <span>{{ $customer->email }}</span>
                        </span>
                        <time>{{ $customer->created_at?->diffForHumans(short: true) }}</time>
                    </a>
                @empty
                    <p class="admin-empty">No customers yet.</p>
                @endforelse
            </div>

            <div class="admin-dash-list-card">
                <div class="admin-dash-panel-head compact">
                    <h2 class="admin-dash-panel-title">Email orders</h2>
                    <a href="{{ route('admin.email-orders.index') }}" class="admin-dash-link">All</a>
                </div>
                @forelse ($recentEmailOrders as $order)
                    <a href="{{ route('admin.email-orders.show', $order) }}" class="admin-mini-row">
                        <span>
                            <strong>{{ $order->domain }}</strong>
                            <span>{{ $order->plan_name }}</span>
                        </span>
                        <span class="admin-mini-status">{{ str_replace('_', ' ', $order->status) }}</span>
                    </a>
                @empty
                    <p class="admin-empty">No orders yet.</p>
                @endforelse
            </div>

            <div class="admin-dash-list-card">
                <div class="admin-dash-panel-head compact">
                    <h2 class="admin-dash-panel-title">Support</h2>
                    <a href="{{ route('admin.support-tickets.index') }}" class="admin-dash-link">All</a>
                </div>
                @forelse ($recentTickets as $ticket)
                    <a href="{{ route('admin.support-tickets.show', $ticket) }}" class="admin-mini-row">
                        <span>
                            <strong>{{ \Illuminate\Support\Str::limit($ticket->subject ?: $ticket->reference, 28) }}</strong>
                            <span>{{ $ticket->full_name ?: $ticket->email }}</span>
                        </span>
                        <span class="admin-mini-status">{{ str_replace('_', ' ', $ticket->status) }}</span>
                    </a>
                @empty
                    <p class="admin-empty">No tickets yet.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
