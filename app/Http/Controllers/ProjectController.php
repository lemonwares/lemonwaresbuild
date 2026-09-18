<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('pages.projects', compact('projects'));
    }

    public function show(Project $project): View
    {
        abort_unless($project->is_published, 404);

        return view('pages.project-show', compact('project'));
    }
}
