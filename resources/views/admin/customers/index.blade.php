@extends('layouts.admin')
{{-- {{ dd($customers) }} --}}
@section('title', 'Customers — ' . config('site.short_name'))

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="section-label mb-3">CRM</p>
            <h1 class="heading">Customers</h1>
            <p class="lede mt-3">Manage legacy WHMCS customers and new Lemonwares customers in one place.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('admin.customers.sync-whmcs') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Sync WHMCS now</button>
            </form>
            <form method="GET" action="{{ route('admin.customers.index') }}" class="flex gap-2">
                <input type="hidden" name="source" value="{{ $source }}">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search name, email, company" class="footer-input rounded-xl border border-border px-4 py-2 text-sm">
                <button type="submit" class="btn btn-ghost">Search</button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <p class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </p>
    @endif

    <div class="mb-6 flex flex-wrap gap-2">
        <a
            href="{{ route('admin.customers.index', ['source' => 'native']) }}"
            @class(['btn', 'btn-primary' => $source === 'native', 'btn-ghost' => $source !== 'native'])
        >Native ({{ $nativeCount }})</a>
        <a
            href="{{ route('admin.customers.index', ['source' => 'legacy']) }}"
            @class(['btn', 'btn-primary' => $source === 'legacy', 'btn-ghost' => $source !== 'legacy'])
        >Legacy WHMCS ({{ $legacyCount }})</a>
    </div>

    <div class="overflow-x-auto rounded-3xl border border-border bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-border text-xs uppercase tracking-widest text-on-blush/50">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">{{ $source === 'legacy' ? 'Services' : 'Orders' }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr class="border-b border-border last:border-0">
                        <td class="px-4 py-3 font-semibold">{{ $source === 'legacy' ? ($customer->full_name ?: '—') : $customer->name }}</td>
                        <td class="px-4 py-3">{{ $customer->email }}</td>
                        <td class="px-4 py-3">{{ $customer->company ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $source === 'legacy' ? $customer->services_count : $customer->email_orders_count }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($source === 'legacy')
                                <a href="{{ route('admin.customers.legacy.show', $customer) }}" class="font-semibold text-rose hover:underline">View</a>
                            @else
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold text-rose hover:underline">View</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-on-blush/60">No customers match that search.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $customers->links() }}</div>
@endsection
