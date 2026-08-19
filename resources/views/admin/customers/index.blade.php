@extends('layouts.admin')
{{-- {{ dd($customers) }} --}}
@section('title', 'Customers — ' . config('site.short_name'))

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="section-label mb-3">CRM</p>
            <h1 class="heading">Customers</h1>
            <p class="lede mt-3">People with a Lemonwares account. Open a profile for email orders and contact details.</p>
        </div>
        <form method="GET" action="{{ route('admin.customers.index') }}" class="flex gap-2">
            <input type="search" name="q" value="{{ $search }}" placeholder="Search name, email, company" class="footer-input rounded-xl border border-border px-4 py-2 text-sm">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>

    <div class="overflow-x-auto rounded-3xl border border-border bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-border text-xs uppercase tracking-widest text-on-blush/50">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Company</th>
                    <th class="px-4 py-3">Orders</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr class="border-b border-border last:border-0">
                        <td class="px-4 py-3 font-semibold">{{ $customer->name }}</td>
                        <td class="px-4 py-3">{{ $customer->email }}</td>
                        <td class="px-4 py-3">{{ $customer->company ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $customer->email_orders_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="font-semibold text-rose hover:underline">View</a>
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
