<?php

namespace App\Http\Controllers;

use App\Support\ContactFormMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(Request $request): View
    {
        $prefillSubject = trim((string) $request->query('subject', ''));
        if (mb_strlen($prefillSubject) > 200) {
            $prefillSubject = mb_substr($prefillSubject, 0, 200);
        }

        return view('pages.contact', [
            'prefillSubject' => $prefillSubject !== '' ? $prefillSubject : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (filled($request->input('company'))) {
            return redirect()
                ->route('contact')
                ->withFragment('contact-form')
                ->with('contact_feedback', [
                    'type' => 'success',
                    'message' => __('pages.contact.sent'),
                ]);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            ContactFormMailer::send([
                'full_name' => trim($validated['full_name']),
                'email' => strtolower(trim($validated['email'])),
                'subject' => trim($validated['subject']),
                'message' => trim($validated['message']),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Contact form mail failed', [
                'email' => $validated['email'],
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('contact')
                ->withInput()
                ->withFragment('contact-form')
                ->with('contact_feedback', [
                    'type' => 'error',
                    'message' => __('pages.contact.send_failed', [
                        'email' => config('site.email'),
                        'phone' => config('site.phone'),
                    ]),
                ]);
        }

        return redirect()
            ->route('contact')
            ->withFragment('contact-form')
            ->with('contact_feedback', [
                'type' => 'success',
                'message' => __('pages.contact.sent'),
            ]);
    }
}
