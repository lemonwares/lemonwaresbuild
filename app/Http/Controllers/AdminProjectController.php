<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('admin.projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('admin.projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $data['slug'] = Project::uniqueSlug($data['title']);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('project-covers', 'public');
        }

        Project::query()->create($data);

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Project created.');
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validatePayload($request, $project->id);

        $slugInput = trim((string) $request->input('slug', ''));
        $data['slug'] = Project::uniqueSlug(
            $slugInput !== '' ? $slugInput : $data['title'],
            $project->id
        );

        if ($request->boolean('remove_cover') && $project->cover_path) {
            Storage::disk('public')->delete($project->cover_path);
            $data['cover_path'] = null;
        }

        if ($request->hasFile('cover')) {
            if ($project->cover_path) {
                Storage::disk('public')->delete($project->cover_path);
            }
            $data['cover_path'] = $request->file('cover')->store('project-covers', 'public');
        }

        $project->update($data);

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        if ($project->cover_path) {
            Storage::disk('public')->delete($project->cover_path);
        }

        $project->delete();

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Project removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'alpha_dash', Rule::unique('projects', 'slug')->ignore($ignoreId)],
            'client_name' => ['nullable', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:50000'],
            'project_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', Rule::in(['0', '1', 0, 1])],
            'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        unset($data['cover']);

        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] = (int) ($request->input('sort_order') ?? 0);
        $data['project_url'] = filled($data['project_url'] ?? null) ? $data['project_url'] : null;
        $data['client_name'] = filled($data['client_name'] ?? null) ? trim($data['client_name']) : null;
        $data['summary'] = filled($data['summary'] ?? null) ? trim($data['summary']) : null;
        $data['description'] = filled($data['description'] ?? null) ? $data['description'] : null;

        return $data;
    }
}
