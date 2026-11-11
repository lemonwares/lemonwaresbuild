<?php

namespace App\Http\Controllers;

use App\Filament\Support\ConsoleOptions;
use App\Models\WhmcsTicket;
use App\Support\Whmcs\Whmcs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountSupportController extends Controller
{
    private const PRIORITIES = ['Low', 'Medium', 'High'];

    public function index(Request $request): View
    {
        $clientId = $this->clientId($request);

        $tickets = WhmcsTicket::query()
            ->where('whmcs_client_id', $clientId ?: -1)
            ->orderByRaw("status = 'Closed'")
            ->orderByDesc('last_reply_at')
            ->paginate(15);

        return view('pages.account-support', [
            'tickets' => $tickets,
            'hasBillingAccount' => $clientId !== null,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->clientId($request)) {
            return redirect()->route('account.support.index');
        }

        return view('pages.account-support-new', [
            'departments' => ConsoleOptions::departments(),
            'priorities' => self::PRIORITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $clientId = $this->clientId($request);
        abort_unless($clientId, 404);

        $data = $request->validate([
            'deptid' => ['required', 'integer', Rule::in(array_keys(ConsoleOptions::departments()))],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:20000'],
        ]);

        $result = Whmcs::call('OpenTicket', [
            'clientid' => $clientId,
            'deptid' => (int) $data['deptid'],
            'priority' => $data['priority'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        if (! $result->ok || ! $result->get('id')) {
            return back()->withInput()->withErrors(['message' => __('account.support_unavailable')]);
        }

        $ticket = WhmcsTicket::query()->updateOrCreate(['whmcs_ticket_id' => (int) $result->get('id')], [
            'tid' => (string) $result->get('tid'),
            'whmcs_client_id' => $clientId,
            'user_id' => $request->user()->accountOwner()->id,
            'department_id' => (int) $data['deptid'],
            'subject' => $data['subject'],
            'status' => 'Open',
            'priority' => $data['priority'],
            'opened_at' => now(),
            'last_reply_at' => now(),
            'last_synced_at' => now(),
        ]);

        return redirect()->route('account.support.show', $ticket)->with('status', __('account.support_opened'));
    }

    public function show(Request $request, WhmcsTicket $ticket): View
    {
        $this->authorizeTicket($request, $ticket);

        $result = Whmcs::call('GetTicket', ['ticketid' => $ticket->whmcs_ticket_id]);
        if ($result->ok) {
            $ticket->update(array_filter([
                'status' => $result->get('status'),
                'last_reply_at' => $result->get('lastreply'),
                'last_synced_at' => now(),
            ]));
        }

        $replies = array_map(fn (array $reply) => [
            'staff' => ! empty($reply['admin']),
            'author' => ! empty($reply['admin']) ? $reply['admin'] : ($reply['name'] ?? $reply['requestor_name'] ?? ''),
            'date' => (string) ($reply['date'] ?? ''),
            'message' => (string) ($reply['message'] ?? ''),
        ], $result->rows('replies.reply'));

        return view('pages.account-support-ticket', [
            'ticket' => $ticket,
            'replies' => $replies,
            'loaded' => $result->ok,
            'department' => ConsoleOptions::departments()[$ticket->department_id] ?? null,
        ]);
    }

    public function reply(Request $request, WhmcsTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request, $ticket);

        $data = $request->validate(['message' => ['required', 'string', 'max:20000']]);

        $result = Whmcs::call('AddTicketReply', [
            'ticketid' => $ticket->whmcs_ticket_id,
            'clientid' => $ticket->whmcs_client_id,
            'message' => $data['message'],
        ]);

        if (! $result->ok) {
            return back()->withInput()->withErrors(['message' => __('account.support_unavailable')]);
        }

        $ticket->update(['status' => 'Customer-Reply', 'last_reply_at' => now()]);

        return redirect()->route('account.support.show', $ticket)->with('status', __('account.support_replied'));
    }

    public function close(Request $request, WhmcsTicket $ticket): RedirectResponse
    {
        $this->authorizeTicket($request, $ticket);

        if (! Whmcs::call('UpdateTicket', ['ticketid' => $ticket->whmcs_ticket_id, 'status' => 'Closed'])->ok) {
            return back()->withErrors(['message' => __('account.support_unavailable')]);
        }

        $ticket->update(['status' => 'Closed']);

        return redirect()->route('account.support.show', $ticket)->with('status', __('account.support_closed'));
    }

    private function clientId(Request $request): ?int
    {
        $clientId = (int) $request->user()->accountOwner()->whmcsCustomer?->whmcs_client_id;

        return $clientId > 0 ? $clientId : null;
    }

    private function authorizeTicket(Request $request, WhmcsTicket $ticket): void
    {
        $clientId = $this->clientId($request);

        abort_unless($clientId && (int) $ticket->whmcs_client_id === $clientId, 404);
    }
}
