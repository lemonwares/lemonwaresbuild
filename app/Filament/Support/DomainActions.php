<?php

namespace App\Filament\Support;

use App\Models\WhmcsDomain;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class DomainActions
{
    public const STATUSES = [
        'Pending' => 'Pending',
        'Pending Registration' => 'Pending registration',
        'Pending Transfer' => 'Pending transfer',
        'Active' => 'Active',
        'Grace' => 'Grace',
        'Redemption' => 'Redemption',
        'Expired' => 'Expired',
        'Transferred Away' => 'Transferred away',
        'Cancelled' => 'Cancelled',
        'Fraud' => 'Fraud',
    ];

    public static function group(): ActionGroup
    {
        return ActionGroup::make(self::all());
    }

    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        $reload = fn (WhmcsDomain $record) => $record->refresh();
        $id = fn (WhmcsDomain $record): array => ['domainid' => $record->whmcs_domain_id];
        $live = fn (WhmcsDomain $record): bool => in_array($record->status, ['Active', 'Grace', 'Redemption', 'Expired'], true);

        return [
            ConsoleAction::make('register')
                ->label('Send to registrar')
                ->icon(Heroicon::OutlinedRocketLaunch)
                ->requiresConfirmation()
                ->modalDescription(fn (WhmcsDomain $record) => $record->status === 'Pending Transfer'
                    ? 'Submits the transfer request to the registrar.'
                    : 'Registers the domain at the registrar. The registrar will charge your account.')
                ->visible(fn (WhmcsDomain $record) => ConsoleAction::can() && in_array($record->status, ['Pending', 'Pending Registration', 'Pending Transfer'], true))
                ->action(fn (WhmcsDomain $record) => $record->status === 'Pending Transfer'
                    ? ConsoleAction::run('DomainTransfer', $id($record), 'Transfer submitted')
                    : ConsoleAction::run('DomainRegister', $id($record), 'Domain registered'))
                ->after($reload),

            ConsoleAction::make('renew')
                ->label('Renew')
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn (WhmcsDomain $record) => ConsoleAction::can() && $live($record))
                ->schema([
                    Select::make('regperiod')->label('Years')->options(array_combine(range(1, 10), range(1, 10)))->default(1)->required(),
                ])
                ->modalDescription('Renews the domain at the registrar. The registrar will charge your account.')
                ->action(fn (WhmcsDomain $record, array $data) => ConsoleAction::run('DomainRenew', $id($record) + ['regperiod' => (int) $data['regperiod']], 'Domain renewed'))
                ->after($reload),

            ConsoleAction::make('nameservers')
                ->label('Nameservers')
                ->icon(Heroicon::OutlinedServer)
                ->visible(fn (WhmcsDomain $record) => ConsoleAction::can() && $live($record))
                ->fillForm(function (WhmcsDomain $record): array {
                    $current = ConsoleAction::fetch('DomainGetNameservers', ['domainid' => $record->whmcs_domain_id]);

                    return collect(range(1, 5))->mapWithKeys(fn ($n) => ["ns{$n}" => $current?->get("ns{$n}")])->all();
                })
                ->schema(array_map(
                    fn ($n) => TextInput::make("ns{$n}")->label("Nameserver {$n}")->required($n <= 2),
                    range(1, 5),
                ))
                ->action(fn (WhmcsDomain $record, array $data) => ConsoleAction::run(
                    'DomainUpdateNameservers',
                    $id($record) + array_map(fn ($v) => (string) $v, array_filter($data)),
                    'Nameservers updated',
                )),

            ConsoleAction::make('locking')
                ->label('Registrar lock')
                ->icon(Heroicon::OutlinedLockClosed)
                ->visible(fn (WhmcsDomain $record) => ConsoleAction::can() && $live($record))
                ->fillForm(function (WhmcsDomain $record): array {
                    $current = ConsoleAction::fetch('DomainGetLockingStatus', ['domainid' => $record->whmcs_domain_id]);

                    return ['lockstatus' => strtolower((string) $current?->get('lockstatus')) === 'locked'];
                })
                ->schema([Toggle::make('lockstatus')->label('Locked (blocks transfers away)')])
                ->action(fn (WhmcsDomain $record, array $data) => ConsoleAction::run(
                    'DomainUpdateLocking',
                    $id($record) + ['lockstatus' => (bool) $data['lockstatus']],
                    'Lock updated',
                )),

            ConsoleAction::make('idProtection')
                ->label(fn (WhmcsDomain $record) => $record->id_protection ? 'Turn off ID protection' : 'Turn on ID protection')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->requiresConfirmation()
                ->visible(fn (WhmcsDomain $record) => ConsoleAction::can() && $live($record))
                ->action(fn (WhmcsDomain $record) => ConsoleAction::run(
                    'DomainToggleIdProtect',
                    $id($record) + ['idprotect' => ! $record->id_protection],
                    'ID protection updated',
                ))
                ->after($reload),

            ConsoleAction::make('editDomain')
                ->label('Edit billing')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->fillForm(fn (WhmcsDomain $record) => [
                    'status' => $record->status,
                    'expirydate' => $record->expiry_date?->toDateString(),
                    'nextduedate' => $record->next_due_date?->toDateString(),
                    'recurringamount' => $record->recurring_amount,
                    'autorenew' => $record->auto_renew,
                ])
                ->schema([
                    Select::make('status')->options(self::STATUSES)->required(),
                    DatePicker::make('expirydate')->label('Expiry date'),
                    DatePicker::make('nextduedate')->label('Next due date'),
                    TextInput::make('recurringamount')->label('Renewal price')->numeric()->minValue(0),
                    Toggle::make('autorenew')->label('Auto-renew'),
                ])
                ->modalDescription('Changes WHMCS records only; nothing is sent to the registrar.')
                ->action(fn (WhmcsDomain $record, array $data) => ConsoleAction::run('UpdateClientDomain', $id($record) + [
                    'status' => $data['status'],
                    'expirydate' => (string) $data['expirydate'],
                    'nextduedate' => (string) $data['nextduedate'],
                    'recurringamount' => (string) $data['recurringamount'],
                    'donotrenew' => ! $data['autorenew'],
                ], 'Domain updated'))
                ->after($reload),

            ConsoleAction::make('eppCode', 'full')
                ->label('Get EPP code')
                ->icon(Heroicon::OutlinedKey)
                ->requiresConfirmation()
                ->modalDescription('Requests the transfer authorisation code. Only share it with the verified domain owner.')
                ->visible(fn (WhmcsDomain $record) => ConsoleAction::can('full') && $live($record))
                ->action(function (WhmcsDomain $record): void {
                    $result = ConsoleAction::call('DomainRequestEPP', ['domainid' => $record->whmcs_domain_id], 'EPP code requested', 'full');
                    if ($result->ok) {
                        $code = (string) $result->get('eppcode');
                        Notification::make()->info()->persistent()
                            ->title($code !== '' ? 'EPP code: '.$code : 'The registrar emailed the EPP code to the domain owner.')
                            ->send();
                    }
                }),
        ];
    }
}
