<?php

namespace App\Filament\Support;

use App\Models\WhmcsInvoice;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class InvoiceActions
{
    private const OPEN = ['Unpaid', 'Payment Pending'];

    public static function group(): ActionGroup
    {
        return ActionGroup::make(self::all());
    }

    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        $reload = fn (WhmcsInvoice $record) => $record->refresh();
        $open = fn (WhmcsInvoice $record): bool => in_array($record->status, self::OPEN, true);

        return [
            ConsoleAction::make('addPayment')
                ->label('Add payment')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->visible(fn (WhmcsInvoice $record) => ConsoleAction::can() && $open($record))
                ->fillForm(fn (WhmcsInvoice $record) => [
                    'amount' => $record->total,
                    'gateway' => $record->payment_method,
                    'date' => today()->toDateString(),
                ])
                ->schema([
                    TextInput::make('amount')->numeric()->minValue(0.01)->required(),
                    Select::make('gateway')->label('Payment method')->options(fn () => ConsoleOptions::gateways())->required(),
                    TextInput::make('transid')->label('Transaction reference')->required(),
                    DatePicker::make('date')->required(),
                    Toggle::make('noemail')->label('Do not email the receipt'),
                ])
                ->action(fn (WhmcsInvoice $record, array $data) => ConsoleAction::run('AddInvoicePayment', [
                    'invoiceid' => $record->whmcs_invoice_id,
                    'amount' => $data['amount'],
                    'gateway' => $data['gateway'],
                    'transid' => $data['transid'],
                    'date' => $data['date'],
                    'noemail' => (bool) $data['noemail'],
                ], 'Payment recorded'))
                ->after($reload),

            ConsoleAction::make('applyCredit')
                ->label('Apply client credit')
                ->icon(Heroicon::OutlinedWallet)
                ->visible(fn (WhmcsInvoice $record) => ConsoleAction::can() && $open($record))
                ->schema([TextInput::make('amount')->numeric()->minValue(0.01)->required()])
                ->action(fn (WhmcsInvoice $record, array $data) => ConsoleAction::run(
                    'ApplyCredit',
                    ['invoiceid' => $record->whmcs_invoice_id, 'amount' => $data['amount']],
                    'Credit applied',
                ))
                ->after($reload),

            ConsoleAction::make('changeDueDate')
                ->label('Change due date')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->visible(fn (WhmcsInvoice $record) => ConsoleAction::can() && $open($record))
                ->fillForm(fn (WhmcsInvoice $record) => ['duedate' => $record->due_date?->toDateString()])
                ->schema([DatePicker::make('duedate')->label('Due date')->required()])
                ->action(fn (WhmcsInvoice $record, array $data) => ConsoleAction::run(
                    'UpdateInvoice',
                    ['invoiceid' => $record->whmcs_invoice_id, 'duedate' => $data['duedate']],
                    'Due date updated',
                ))
                ->after($reload),

            ConsoleAction::make('sendReminder')
                ->label('Send payment reminder')
                ->icon(Heroicon::OutlinedEnvelope)
                ->requiresConfirmation()
                ->visible(fn (WhmcsInvoice $record) => ConsoleAction::can() && $open($record))
                ->action(fn (WhmcsInvoice $record) => ConsoleAction::run(
                    'SendEmail',
                    ['messagename' => 'Invoice Payment Reminder', 'id' => $record->whmcs_invoice_id],
                    'Reminder sent',
                )),

            ConsoleAction::make('cancelInvoice', 'full')
                ->label('Cancel invoice')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (WhmcsInvoice $record) => ConsoleAction::can('full') && $open($record))
                ->action(fn (WhmcsInvoice $record) => ConsoleAction::run(
                    'UpdateInvoice',
                    ['invoiceid' => $record->whmcs_invoice_id, 'status' => 'Cancelled'],
                    'Invoice cancelled',
                    'full',
                ))
                ->after($reload),
        ];
    }

    public static function create(?int $clientId = null): Action
    {
        return ConsoleAction::make('createInvoice')
            ->label('New invoice')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->fillForm(['userid' => $clientId, 'duedate' => today()->addDays(7)->toDateString(), 'items' => [['description' => '', 'amount' => null]]])
            ->schema([
                Select::make('userid')->label('Client')->required($clientId === null)
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => ConsoleOptions::searchClients($search))
                    ->getOptionLabelUsing(fn ($value) => ConsoleOptions::clientLabel($value))
                    ->hidden($clientId !== null),
                DatePicker::make('duedate')->label('Due date')->required(),
                Select::make('paymentmethod')->label('Payment method')->options(fn () => ConsoleOptions::gateways()),
                Repeater::make('items')->required()->minItems(1)->columns(3)->schema([
                    TextInput::make('description')->required()->columnSpan(2),
                    TextInput::make('amount')->numeric()->required(),
                ]),
                Toggle::make('sendinvoice')->label('Email the invoice to the client')->default(true),
            ])
            ->action(function (array $data) use ($clientId): void {
                $params = [
                    'userid' => (int) ($clientId ?? $data['userid']),
                    'status' => 'Unpaid',
                    'duedate' => $data['duedate'],
                    'sendinvoice' => (bool) $data['sendinvoice'],
                ];
                if (! empty($data['paymentmethod'])) {
                    $params['paymentmethod'] = $data['paymentmethod'];
                }
                foreach (array_values($data['items']) as $i => $item) {
                    $params['itemdescription'.($i + 1)] = $item['description'];
                    $params['itemamount'.($i + 1)] = $item['amount'];
                }

                ConsoleAction::run('CreateInvoice', $params, 'Invoice created');
            });
    }

    /**
     * Line items are not mirrored; they are read live when an invoice is opened.
     *
     * @return list<array{description:string,amount:string}>
     */
    public static function items(WhmcsInvoice $invoice): array
    {
        $id = (int) $invoice->whmcs_invoice_id;

        if (! array_key_exists($id, self::$items)) {
            $result = ConsoleAction::fetch('GetInvoice', ['invoiceid' => $id]);
            self::$items[$id] = array_map(fn (array $item) => [
                'description' => (string) ($item['description'] ?? ''),
                'amount' => (string) ($item['amount'] ?? '0.00'),
            ], $result?->rows('items.item') ?? []);
        }

        return self::$items[$id];
    }

    /** @var array<int, list<array{description:string,amount:string}>> */
    private static array $items = [];
}
