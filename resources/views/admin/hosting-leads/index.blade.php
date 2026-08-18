@extends('layouts.admin')

@section('title', 'Hosting Leads — ' . config('site.short_name'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">CRM</p>
        <h1 class="heading">Hosting Leads</h1>
        <p class="lede mt-3">Checkout requests from the hosting flow, including VPS orders waiting on payment.</p>
    </div>

    <div class="overflow-x-auto rounded-3xl border border-border bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-border text-xs uppercase tracking-widest text-on-blush/50">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($leads as $lead)
                    <tr class="border-b border-border last:border-0">
                        <td class="px-4 py-3 font-semibold">{{ $lead->full_name }}</td>
                        <td class="px-4 py-3">{{ $lead->email }}</td>
                        <td class="px-4 py-3">{{ $lead->plan_name }}{{ $lead->spec_label ? ' · ' . $lead->spec_label : '' }}</td>
                        <td class="px-4 py-3">{{ str_replace('_', ' ', $lead->status ?: 'pending') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.hosting-leads.show', $lead) }}" class="font-semibold text-rose hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-on-blush/60">No hosting leads yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $leads->links() }}</div>
@endsection
