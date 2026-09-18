<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Support\SupportTicketMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function show(): View
    {
        return view('pages.support');
    }

    public function store(Request $request): RedirectResponse
    {
        if (filled($request->input('company'))) {
            return redirect()
                ->route('support')
                ->withFragment('support-ticket')
                ->with('support_feedback', [
                    'type' => 'success',
                    'message' => __('pages.support_page.ticket_sent'),
                ]);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'category' => ['required', 'string', Rule::in(SupportTicket::CATEGORIES)],
            'priority' => ['required', 'string', Rule::in(SupportTicket::PRIORITIES)],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = SupportTicket::query()->create([
            'reference' => SupportTicket::generateReference(),
            'user_id' => $request->user()?->id,
            'full_name' => trim($validated['full_name']),
            'email' => strtolower(trim($validated['email'])),
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'status' => 'open',
        ]);

        try {
            SupportTicketMailer::send($ticket);
        } catch (\Throwable $exception) {
            Log::warning('Support ticket mail failed', [
                'reference' => $ticket->reference,
                'email' => $ticket->email,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('support')
            ->withFragment('support-ticket')
            ->with('support_feedback', [
                'type' => 'success',
                'message' => __('pages.support_page.ticket_sent_with_ref', [
                    'reference' => $ticket->reference,
                ]),
            ]);
    }
}
