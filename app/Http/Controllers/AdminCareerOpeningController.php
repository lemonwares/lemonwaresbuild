<?php

namespace App\Http\Controllers;

use App\Models\CareerOpening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCareerOpeningController extends Controller
{
    public function index(): View
    {
        $openings = CareerOpening::query()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('admin.career-openings.index', compact('openings'));
    }

    public function create(): View
    {
        return view('admin.career-openings.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        unset($data['slug']);
        $data['slug'] = CareerOpening::uniqueSlug($data['title']);

        CareerOpening::create($data);

        return redirect()->route('admin.career-openings.index')->with('status', 'Career opening added.');
    }

    public function edit(CareerOpening $careerOpening): View
    {
        return view('admin.career-openings.edit', compact('careerOpening'));
    }

    public function update(Request $request, CareerOpening $careerOpening): RedirectResponse
    {
        $data = $this->validatePayload($request, $careerOpening->id);

        $slugInput = trim((string) $request->input('slug', ''));
        $data['slug'] = CareerOpening::uniqueSlug(
            $slugInput !== '' ? $slugInput : $data['title'],
            $careerOpening->id
        );

        $careerOpening->update($data);

        return redirect()->route('admin.career-openings.index')->with('status', 'Career opening updated.');
    }

    public function destroy(CareerOpening $careerOpening): RedirectResponse
    {
        $careerOpening->delete();

        return redirect()->route('admin.career-openings.index')->with('status', 'Career opening removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'alpha_dash', Rule::unique('career_openings', 'slug')->ignore($ignoreId)],
            'type' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'responsibilities' => ['nullable', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'apply_subject' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', Rule::in(['0', '1', 0, 1])],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($request->input('sort_order') ?? 0);

        return $data;
    }
}
