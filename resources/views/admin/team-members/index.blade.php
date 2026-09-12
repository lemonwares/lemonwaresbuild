@extends('layouts.admin')

@section('title', 'Team Members — Admin')

@section('content')
    <div class="mb-8 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-2xl">
            <p class="section-label mb-3">Team Manager</p>
            <h1 class="heading">Team Members</h1>
            <p class="lede mt-3">Add, update, reorder, hide, or remove members shown on the public Team page.</p>
        </div>

        <a href="{{ route('admin.team-members.create') }}" class="btn btn-primary">
            Add Team Member
        </a>
    </div>

    <div class="mb-6 flex flex-wrap gap-2">
        <span class="rounded-full border border-border bg-white px-3 py-1.5 text-sm text-on-blush/70">
            Total <span class="font-bold text-black">{{ $members->count() }}</span>
        </span>
        <span class="rounded-full border border-border bg-white px-3 py-1.5 text-sm text-on-blush/70">
            Visible <span class="font-bold text-black">{{ $members->where('is_active', true)->count() }}</span>
        </span>
        <span class="rounded-full border border-border bg-white px-3 py-1.5 text-sm text-on-blush/70">
            Hidden <span class="font-bold text-black">{{ $members->where('is_active', false)->count() }}</span>
        </span>
    </div>

    <div class="overflow-hidden rounded-3xl border border-border bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-blush-soft">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-on-blush/60">Photo</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-on-blush/60">Name</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-on-blush/60">Role</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-on-blush/60">Order</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-on-blush/60">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-on-blush/60">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse ($members as $member)
                        <tr class="transition hover:bg-blush-soft/60">
                            <td class="px-5 py-4">
                                @if ($member->photo_path)
                                    <img src="{{ asset('storage/' . $member->photo_path) }}" alt="{{ $member->name }}" class="size-11 rounded-full object-cover" />
                                @else
                                    <span class="inline-flex size-11 items-center justify-center rounded-full bg-blush text-sm font-bold text-rose">
                                        {{ \Illuminate\Support\Str::of($member->name)->explode(' ')->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->take(2)->join('') }}
                                    </span>
                                @endif
                            </td>
                            <td class="max-w-[14rem] truncate px-5 py-4 text-sm font-semibold text-black" title="{{ $member->name }}">{{ $member->name }}</td>
                            <td class="max-w-[16rem] truncate px-5 py-4 text-sm text-on-blush/80" title="{{ $member->role }}">{{ $member->role }}</td>
                            <td class="px-5 py-4 text-sm text-on-blush/80">{{ $member->sort_order }}</td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-800' => $member->is_active,
                                    'bg-blush-soft text-on-blush/70' => ! $member->is_active,
                                ])>{{ $member->is_active ? 'Active' : 'Hidden' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.team-members.edit', $member) }}" class="rounded-full border border-border px-4 py-2 text-xs font-semibold text-black transition hover:border-rose hover:text-rose">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.team-members.destroy', $member) }}" onsubmit="return confirm('Remove this team member? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" data-submit-button class="inline-flex items-center gap-2 rounded-full border border-rose/30 px-4 py-2 text-xs font-semibold text-rose transition hover:bg-rose hover:text-white">
                                            <span class="hidden size-3 animate-spin rounded-full border-2 border-current/40 border-t-current" data-submit-spinner></span>
                                            <span data-submit-label>Remove</span>
                                            <span class="hidden" data-submit-loading>Removing...</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center body-text">
                                No team members yet. Click <span class="font-semibold">Add Team Member</span> to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
