<?php

namespace App\Http\Controllers;

use App\Models\CaseStudy;
use App\Support\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCaseStudyController extends Controller
{
    public function index(): View
    {
        $caseStudies = CaseStudy::query()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('admin.case-studies.index', compact('caseStudies'));
    }

    public function create(): View
    {
        return view('admin.case-studies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $data['slug'] = CaseStudy::uniqueSlug($data['title']);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = MediaStorage::store($request->file('cover'), 'case-study-covers');
        }

        CaseStudy::query()->create($data);

        return redirect()
            ->route('admin.case-studies.index')
            ->with('status', 'Case study created.');
    }

    public function edit(CaseStudy $caseStudy): View
    {
        return view('admin.case-studies.edit', compact('caseStudy'));
    }

    public function update(Request $request, CaseStudy $caseStudy): RedirectResponse
    {
        $data = $this->validatePayload($request, $caseStudy->id);

        $slugInput = trim((string) $request->input('slug', ''));
        $data['slug'] = CaseStudy::uniqueSlug(
            $slugInput !== '' ? $slugInput : $data['title'],
            $caseStudy->id
        );

        if ($request->boolean('remove_cover') && $caseStudy->cover_path) {
            MediaStorage::delete($caseStudy->cover_path);
            $data['cover_path'] = null;
        }

        if ($request->hasFile('cover')) {
            if ($caseStudy->cover_path) {
                MediaStorage::delete($caseStudy->cover_path);
            }
            $data['cover_path'] = MediaStorage::store($request->file('cover'), 'case-study-covers');
        }

        $caseStudy->update($data);

        return redirect()
            ->route('admin.case-studies.index')
            ->with('status', 'Case study updated.');
    }

    public function destroy(CaseStudy $caseStudy): RedirectResponse
    {
        if ($caseStudy->cover_path) {
            MediaStorage::delete($caseStudy->cover_path);
        }

        $caseStudy->delete();

        return redirect()
            ->route('admin.case-studies.index')
            ->with('status', 'Case study removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'alpha_dash', Rule::unique('case_studies', 'slug')->ignore($ignoreId)],
            'client_name' => ['nullable', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:500'],
            'outcome' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:50000'],
            'cta_url' => ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/).+/i'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', Rule::in(['0', '1', 0, 1])],
            'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        unset($data['cover']);

        $data['is_published'] = $request->boolean('is_published');
        $data['sort_order'] = (int) ($request->input('sort_order') ?? 0);
        $data['cta_url'] = filled($data['cta_url'] ?? null) ? $data['cta_url'] : null;
        $data['cta_label'] = filled($data['cta_label'] ?? null) ? trim($data['cta_label']) : null;
        $data['client_name'] = filled($data['client_name'] ?? null) ? trim($data['client_name']) : null;
        $data['summary'] = filled($data['summary'] ?? null) ? trim($data['summary']) : null;
        $data['outcome'] = filled($data['outcome'] ?? null) ? trim($data['outcome']) : null;
        $data['description'] = filled($data['description'] ?? null) ? $data['description'] : null;

        return $data;
    }
}
