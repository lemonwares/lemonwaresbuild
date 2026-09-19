<?php

namespace App\Http\Controllers;

use App\Models\CaseStudy;
use Illuminate\View\View;

class CaseStudyController extends Controller
{
    public function index(): View
    {
        $caseStudies = CaseStudy::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('pages.case-studies', compact('caseStudies'));
    }

    public function show(CaseStudy $caseStudy): View
    {
        abort_unless($caseStudy->is_published, 404);

        return view('pages.case-study-show', compact('caseStudy'));
    }
}
