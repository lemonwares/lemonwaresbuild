@extends('layouts.admin')

@section('title', 'Team Members — Admin')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="section-label mb-3">Team Manager</p>
            <h1 class="heading">Team Members</h1>
            <p class="lede mt-3">Add, update, reorder, hide, or remove members shown on the public Team page.</p>
        </div>

        <a href="{{ route('admin.team-members.create') }}" class="inline-flex items-center whitespace-nowrap rounded-xl bg-rose px-5 py-3 text-sm font-semibold text-white shadow-[0_8px_20px_rgba(224,69,69,0.28)] transition hover:-translate-y-0.5 hover:bg-[#c93737]">
            Add Team Member
        </a>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-border bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Total</p>
            <p class="mt-1 text-3xl font-bold text-black">{{ $members->count() }}</p>
        </article>
        <article class="rounded-2xl border border-border bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Visible</p>
            <p class="mt-1 text-3xl font-bold text-black">{{ $members->where('is_active', true)->count() }}</p>
        </article>
        <article class="rounded-2xl border border-border bg-white p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Hidden</p>
            <p class="mt-1 text-3xl font-bold text-black">{{ $members->where('is_active', false)->count() }}</p>
        </article>
    </div>

    <div class="overflow-hidden rounded-3xl border border-border bg-white">
        <table class="min-w-full divide-y divide-border">
            <thead class="bg-blush">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-black">Photo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-black">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-black">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-black">Order</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-black">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-black">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($members as $member)
                    <tr class="transition hover:bg-blush/20">
                        <td class="px-4 py-3">
                            @if ($member->photo_path)
                                <img src="{{ asset('storage/' . $member->photo_path) }}" alt="{{ $member->name }}" class="size-12 rounded-full object-cover" />
                            @else
                                <span class="inline-flex size-12 items-center justify-center rounded-full bg-blush text-sm font-bold text-rose">
                                    {{ \Illuminate\Support\Str::of($member->name)->explode(' ')->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->take(2)->join('') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-black">{{ $member->name }}</td>
                        <td class="px-4 py-3 text-sm text-black">{{ $member->role }}</td>
                        <td class="px-4 py-3 text-sm text-black">{{ $member->sort_order }}</td>
                        <td class="px-4 py-3 text-sm text-black">{{ $member->is_active ? 'Active' : 'Hidden' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.team-members.edit', $member) }}" class="rounded-full border border-border px-4 py-2 text-xs font-semibold text-black transition hover:border-rose hover:text-rose">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.team-members.destroy', $member) }}" onsubmit="return confirm('Remove this team member? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" data-submit-button class="inline-flex items-center gap-2 rounded-full border border-rose/30 px-4 py-2 text-xs font-semibold text-rose transition hover:bg-rose hover:text-white">
                                        <span class="hidden size-3 animate-spin rounded-full border-2 border-rose/40 border-t-rose" data-submit-spinner></span>
                                        <span data-submit-label>Remove</span>
                                        <span class="hidden" data-submit-loading>Removing...</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center body-text">
                            No team members yet. Click <span class="font-semibold">Add Team Member</span> to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

