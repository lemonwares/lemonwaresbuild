<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountActivityController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();

        return view('pages.account-activity', [
            'activities' => $owner->accountActivities()->paginate(30),
        ]);
    }
}
