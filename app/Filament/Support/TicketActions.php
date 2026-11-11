<?php

namespace App\Filament\Support;

use App\Models\WhmcsTicket;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class TicketActions
{
    public const STATUSES = [
        'Open' => 'Open',
        'Answered' => 'Answered',
        'Customer-Reply' => 'Customer reply',
        'On Hold' => 'On hold',
        'In Progress' => 'In progress',
        'Closed' => 'Closed',
    ];

    public const PRIORITIES = ['Low' => 'Low', 'Medium' => 'Medium', 'High' => 'High'];

    /** @var array<int, list<array{author:string,date:string,message:string}>> */
    private static array $threads = [];

    public static function group(): ActionGroup
    {
        return ActionGroup::make(self::all());
    }

    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        $reload = fn (WhmcsTicket $record) => ConsoleAction::reload($record);
        $open = fn (WhmcsTicket $record): bool => $record->status !== 'Closed';

        return [
            ConsoleAction::make('reply')
                ->label('Reply')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('primary')
                ->schema([
                    Textarea::make('message')->required()->rows(8),
                    Select::make('status')->label('Then set status to')->options(self::STATUSES)->default('Answered'),
                ])
                ->action(function (WhmcsTicket $record, array $data): void {
                    $admin = ConsoleAction::admin();
                    $sent = ConsoleAction::run('AddTicketReply', [
                        'ticketid' => $record->whmcs_ticket_id,
                        'message' => $data['message'],
                        'adminusername' => $admin?->name ?: 'LemonWares',
                        'status' => $data['status'],
                    ], 'Reply sent');

                    if ($sent) {
                        unset(self::$threads[(int) $record->whmcs_ticket_id]);
                    }
                })
                ->after($reload),

            ConsoleAction::make('addNote')
                ->label('Add private note')
                ->icon(Heroicon::OutlinedPencil)
                ->schema([Textarea::make('message')->label('Note (staff only)')->required()->rows(5)])
                ->action(fn (WhmcsTicket $record, array $data) => ConsoleAction::run(
                    'AddTicketNote',
                    ['ticketid' => $record->whmcs_ticket_id, 'message' => $data['message']],
                    'Note added',
                )),

            ConsoleAction::make('updateTicket')
                ->label('Status, priority, department')
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->fillForm(fn (WhmcsTicket $record) => [
                    'status' => $record->status,
                    'priority' => $record->priority,
                    'deptid' => $record->department_id,
                ])
                ->schema([
                    Select::make('status')->options(self::STATUSES)->required(),
                    Select::make('priority')->options(self::PRIORITIES)->required(),
                    Select::make('deptid')->label('Department')->options(fn () => ConsoleOptions::departments()),
                ])
                ->action(fn (WhmcsTicket $record, array $data) => ConsoleAction::run(
                    'UpdateTicket',
                    ['ticketid' => $record->whmcs_ticket_id] + array_filter($data),
                    'Ticket updated',
                ))
                ->after($reload),

            ConsoleAction::make('close')
                ->label('Close')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (WhmcsTicket $record) => ConsoleAction::can() && $open($record))
                ->action(fn (WhmcsTicket $record) => ConsoleAction::run(
                    'UpdateTicket',
                    ['ticketid' => $record->whmcs_ticket_id, 'status' => 'Closed'],
                    'Ticket closed',
                ))
                ->after($reload),
        ];
    }

    public static function open(?int $clientId = null): Action
    {
        return ConsoleAction::make('openTicket')
            ->label('New ticket')
            ->icon(Heroicon::OutlinedPlus)
            ->fillForm(['clientid' => $clientId, 'priority' => 'Medium'])
            ->schema([
                Select::make('clientid')->label('Client')->required($clientId === null)
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => ConsoleOptions::searchClients($search))
                    ->getOptionLabelUsing(fn ($value) => ConsoleOptions::clientLabel($value))
                    ->hidden($clientId !== null),
                Select::make('deptid')->label('Department')->options(fn () => ConsoleOptions::departments())->required(),
                Select::make('priority')->options(self::PRIORITIES)->required(),
                TextInput::make('subject')->required()->maxLength(200),
                Textarea::make('message')->required()->rows(8),
            ])
            ->action(fn (array $data) => ConsoleAction::run('OpenTicket', [
                'clientid' => (int) ($clientId ?? $data['clientid']),
                'deptid' => (int) $data['deptid'],
                'priority' => $data['priority'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'admin' => true,
            ], 'Ticket opened'));
    }

    /**
     * Conversation is read live; only ticket headers are mirrored.
     *
     * @return list<array{author:string,date:string,message:string}>
     */
    public static function replies(WhmcsTicket $ticket): array
    {
        $id = (int) $ticket->whmcs_ticket_id;

        if (! array_key_exists($id, self::$threads)) {
            $result = ConsoleAction::fetch('GetTicket', ['ticketid' => $id]);
            self::$threads[$id] = array_map(fn (array $reply) => [
                'author' => ! empty($reply['admin'])
                    ? $reply['admin'].' (staff)'
                    : (string) ($reply['name'] ?? $reply['requestor_name'] ?? 'Client'),
                'date' => (string) ($reply['date'] ?? ''),
                'message' => (string) ($reply['message'] ?? ''),
            ], $result?->rows('replies.reply') ?? []);
        }

        return self::$threads[$id];
    }
}
