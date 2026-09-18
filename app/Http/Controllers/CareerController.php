<?php

namespace App\Http\Controllers;

use App\Models\CareerOpening;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function index(): View
    {
        $openings = CareerOpening::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('pages.careers', compact('openings'));
    }

    public function show(CareerOpening $careerOpening): View
    {
        abort_unless($careerOpening->is_active, 404);

        return view('pages.career-show', [
            'opening' => $careerOpening,
        ]);
    }
}
