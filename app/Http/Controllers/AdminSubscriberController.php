<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\View\View;

class AdminSubscriberController extends Controller
{
    public function index(): View
    {
        $subscribers = NewsletterSubscriber::query()->latest()->paginate(30);

        return view('admin.subscribers.index', compact('subscribers'));
    }
}
