@extends('layouts.admin')

@section('title', 'Subscribers — ' . config('site.short_name'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">CRM</p>
        <h1 class="heading">Newsletter subscribers</h1>
        <p class="lede mt-3">People who joined from the site footer.</p>
    </div>

    <div class="overflow-x-auto rounded-3xl border border-border bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-border text-xs uppercase tracking-widest text-on-blush/50">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subscribers as $subscriber)
                    <tr class="border-b border-border last:border-0">
                        <td class="px-4 py-3 font-semibold">{{ $subscriber->full_name }}</td>
                        <td class="px-4 py-3">{{ $subscriber->email }}</td>
                        <td class="px-4 py-3">{{ $subscriber->created_at?->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-on-blush/60">No subscribers yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $subscribers->links() }}</div>
@endsection
