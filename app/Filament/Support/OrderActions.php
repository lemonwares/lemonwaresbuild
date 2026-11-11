<?php

namespace App\Filament\Support;

use App\Models\WhmcsOrder;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

class OrderActions
{
    public static function group(): ActionGroup
    {
        return ActionGroup::make(self::all());
    }

    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        $reload = fn (WhmcsOrder $record) => ConsoleAction::reload($record);
        $id = fn (WhmcsOrder $record): array => ['orderid' => $record->whmcs_order_id];
        $is = fn (WhmcsOrder $record, string ...$statuses): bool => in_array($record->status, $statuses, true);

        return [
            ConsoleAction::make('accept')
                ->label('Accept')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (WhmcsOrder $record) => ConsoleAction::can() && $is($record, 'Pending'))
                ->schema([
                    Toggle::make('autosetup')->label('Provision services and register domains now')->default(true),
                    Toggle::make('sendemail')->label('Send welcome emails')->default(true),
                ])
                ->modalDescription(fn (WhmcsOrder $record) => $record->payment_status !== 'Paid' ? 'This order is not paid yet.' : null)
                ->action(fn (WhmcsOrder $record, array $data) => ConsoleAction::run('AcceptOrder', $id($record) + [
                    'autosetup' => (bool) $data['autosetup'],
                    'sendemail' => (bool) $data['sendemail'],
                ], 'Order accepted'))
                ->after($reload),

            ConsoleAction::make('setPending')
                ->label('Set back to pending')
                ->icon(Heroicon::OutlinedClock)
                ->requiresConfirmation()
                ->visible(fn (WhmcsOrder $record) => ConsoleAction::can() && $is($record, 'Active', 'Cancelled', 'Fraud'))
                ->action(fn (WhmcsOrder $record) => ConsoleAction::run('PendingOrder', $id($record), 'Order set to pending'))
                ->after($reload),

            ConsoleAction::make('cancel')
                ->label('Cancel')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (WhmcsOrder $record) => ConsoleAction::can() && $is($record, 'Pending', 'Active'))
                ->schema([
                    Toggle::make('cancelsub')->label('Also cancel any recurring subscription')->default(true),
                    Toggle::make('noemail')->label('Do not email the client'),
                ])
                ->action(fn (WhmcsOrder $record, array $data) => ConsoleAction::run('CancelOrder', $id($record) + [
                    'cancelsub' => (bool) $data['cancelsub'],
                    'noemail' => (bool) $data['noemail'],
                ], 'Order cancelled'))
                ->after($reload),

            ConsoleAction::make('fraud', 'full')
                ->label('Mark as fraud')
                ->icon(Heroicon::OutlinedShieldExclamation)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Marks the order as fraud and cancels its services and invoices.')
                ->visible(fn (WhmcsOrder $record) => ConsoleAction::can('full') && $is($record, 'Pending', 'Active'))
                ->action(fn (WhmcsOrder $record) => ConsoleAction::run('FraudOrder', $id($record), 'Order marked as fraud', 'full'))
                ->after($reload),

            ConsoleAction::make('delete', 'full')
                ->label('Delete')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Permanently deletes the order from WHMCS.')
                ->visible(fn (WhmcsOrder $record) => ConsoleAction::can('full') && $is($record, 'Cancelled', 'Fraud'))
                ->action(function (WhmcsOrder $record): void {
                    if (ConsoleAction::run('DeleteOrder', ['orderid' => $record->whmcs_order_id], 'Order deleted', 'full')) {
                        $record->delete();
                    }
                }),
        ];
    }

    /**
     * Line items come from the mirrored GetOrders payload.
     *
     * @return list<array{product:string,cycle:string,amount:string,status:string}>
     */
    public static function items(WhmcsOrder $order): array
    {
        $items = data_get($order->raw_payload, 'lineitems.lineitem', []);
        $items = is_array($items) && ! array_is_list($items) ? [$items] : (array) $items;

        return array_map(fn ($item) => [
            'product' => trim(($item['product'] ?? $item['type'] ?? '').(! empty($item['domain']) ? ' — '.$item['domain'] : '')),
            'cycle' => (string) ($item['billingcycle'] ?? ''),
            'amount' => (string) ($item['amount'] ?? ''),
            'status' => (string) ($item['status'] ?? ''),
        ], array_filter($items, 'is_array'));
    }
}
