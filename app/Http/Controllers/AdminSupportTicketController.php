<?php

namespace App\Http\Controllers;

use App\Mail\SupportTicketReply;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\AccountActivityLogger;
use App\Support\ZeptoMailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $priority = (string) $request->query('priority', '');
        $assigned = (string) $request->query('assigned', '');
        $search = trim((string) $request->query('q', ''));

        $tickets = SupportTicket::query()
            ->with('assignedAdmin')
            ->when(in_array($status, SupportTicket::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->when($status === 'active', fn ($q) => $q->whereIn('status', ['open', 'in_progress']))
            ->when(in_array($priority, SupportTicket::PRIORITIES, true), fn ($q) => $q->where('priority', $priority))
            ->when($assigned === 'me', fn ($q) => $q->where('assigned_admin_id', (int) $request->session()->get('admin_user_id')))
            ->when($assigned === 'none', fn ($q) => $q->whereNull('assigned_admin_id'))
            ->when($search !== '', fn ($q) => $q->where(fn ($inner) => $inner
                ->where('reference', 'like', '%'.$search.'%')
                ->orWhere('subject', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('full_name', 'like', '%'.$search.'%')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $totalTickets = SupportTicket::count();
        $openTickets = SupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count();
        $resolvedTickets = SupportTicket::query()->whereIn('status', ['resolved', 'closed'])->count();
        $highPriority = SupportTicket::query()->where('priority', 'high')->whereIn('status', ['open', 'in_progress'])->count();
        $newWeek = SupportTicket::query()->where('created_at', '>=', now()->subDays(7))->count();

        return view('admin.support-tickets.index', compact(
            'tickets',
            'totalTickets',
            'openTickets',
            'resolvedTickets',
            'highPriority',
            'newWeek',
            'status',
            'priority',
            'assigned',
            'search',
        ));
    }

    public function show(SupportTicket $supportTicket): View
    {
        $supportTicket->ensureThreadSeeded();
        $supportTicket->load(['messages' => fn ($q) => $q->oldest()]);

        return view('admin.support-tickets.show', [
            'ticket' => $supportTicket,
            'admins' => User::query()->where('role', 'admin')->orderBy('name')->get(['id', 'name']),
            'savedReplies' => \App\Models\SavedReply::query()->orderBy('title')->get(),
        ]);
    }

    public function update(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(SupportTicket::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', 'string', Rule::in(SupportTicket::PRIORITIES)],
            'assigned_admin_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'admin')],
        ]);

        $supportTicket->status = $validated['status'];
        if (! empty($validated['priority'])) {
            $supportTicket->priority = $validated['priority'];
        }
        if ($request->has('assigned_admin_id')) {
            $supportTicket->assigned_admin_id = $validated['assigned_admin_id'] ?? null;
        }
        $supportTicket->admin_notes = filled($validated['admin_notes'] ?? null)
            ? trim($validated['admin_notes'])
            : null;

        if (in_array($validated['status'], ['resolved', 'closed'], true) && ! $supportTicket->resolved_at) {
            $supportTicket->resolved_at = now();
        }

        if (! in_array($validated['status'], ['resolved', 'closed'], true)) {
            $supportTicket->resolved_at = null;
        }

        $supportTicket->save();

        return redirect()
            ->route('admin.support-tickets.show', $supportTicket)
            ->with('status', 'Ticket updated.');
    }

    public function reply(Request $request, SupportTicket $supportTicket): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'is_internal' => ['nullable', 'boolean'],
            'notify_customer' => ['nullable', 'boolean'],
        ]);

        $supportTicket->ensureThreadSeeded();

        $adminId = (int) $request->session()->get('admin_user_id');
        $admin = $adminId > 0 ? User::query()->find($adminId) : null;
        $isInternal = (bool) ($validated['is_internal'] ?? false);

        $message = $supportTicket->messages()->create([
            'user_id' => $admin?->id,
            'author_type' => 'admin',
            'author_name' => $admin?->name ?: 'Support',
            'author_email' => $admin?->email,
            'body' => trim($validated['body']),
            'is_internal' => $isInternal,
        ]);

        if ($supportTicket->status === 'open') {
            $supportTicket->status = 'in_progress';
            $supportTicket->save();
        }

        if (! $isInternal && $request->boolean('notify_customer', true)) {
            $this->notifyCustomer($supportTicket, $message->body);

            $customer = $supportTicket->user_id
                ? User::query()->find($supportTicket->user_id)
                : User::query()->where('email', strtolower((string) $supportTicket->email))->first();

            if ($customer) {
                AccountActivityLogger::log(
                    $customer,
                    'support_reply',
                    'Support replied to your ticket',
                    'A reply was sent for ticket '.$supportTicket->reference.'.',
                    'admin',
                    $supportTicket,
                    ['reference' => $supportTicket->reference],
                );
            }
        }

        return redirect()
            ->route('admin.support-tickets.show', $supportTicket)
            ->with('status', $isInternal ? 'Internal note added.' : 'Reply sent.');
    }

    private function notifyCustomer(SupportTicket $ticket, string $body): void
    {
        try {
            ZeptoMailSettings::applyRuntimeConfig();
            $mailer = ZeptoMailSettings::isConfigured()
                ? 'zeptomail'
                : (string) config('mail.default', 'log');

            Mail::mailer($mailer)->to($ticket->email)->send(new SupportTicketReply($ticket, $body));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
