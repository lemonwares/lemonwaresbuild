<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminTeamMemberController extends Controller
{
    public function index(): View
    {
        $members = TeamMember::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.team-members.index', compact('members'));
    }

    public function create(): View
    {
        return view('admin.team-members.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('team-members', 'public');
        }

        TeamMember::create($data);

        return redirect()->route('admin.team-members.index')->with('status', 'Team member added.');
    }

    public function edit(TeamMember $teamMember): View
    {
        return view('admin.team-members.edit', compact('teamMember'));
    }

    public function update(Request $request, TeamMember $teamMember): RedirectResponse
    {
        $data = $this->validatePayload($request);

        if ($request->boolean('remove_photo') && $teamMember->photo_path) {
            Storage::disk('public')->delete($teamMember->photo_path);
            $data['photo_path'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($teamMember->photo_path) {
                Storage::disk('public')->delete($teamMember->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('team-members', 'public');
        }

        $teamMember->update($data);

        return redirect()->route('admin.team-members.index')->with('status', 'Team member updated.');
    }

    public function destroy(TeamMember $teamMember): RedirectResponse
    {
        if ($teamMember->photo_path) {
            Storage::disk('public')->delete($teamMember->photo_path);
        }

        $teamMember->delete();

        return redirect()->route('admin.team-members.index')->with('status', 'Team member removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'string', 'max:160'],
            'quote' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'x_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', Rule::in(['0', '1', 0, 1])],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($request->input('sort_order') ?? 0);

        return $data;
    }
}

