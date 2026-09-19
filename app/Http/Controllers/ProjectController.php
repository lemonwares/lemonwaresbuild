<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('case-studies', [], 301);
    }

    public function show(Project $project): RedirectResponse
    {
        return redirect()->route('case-studies', [], 301);
    }
}
