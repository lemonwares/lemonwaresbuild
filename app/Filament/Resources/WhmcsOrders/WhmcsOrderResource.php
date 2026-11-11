<?php

namespace App\Filament\Resources\WhmcsOrders;

use App\Filament\Resources\WhmcsCustomers\WhmcsCustomerResource;
use App\Filament\Resources\WhmcsOrders\Pages\ListWhmcsOrders;
use App\Filament\Resources\WhmcsOrders\Pages\ViewWhmcsOrder;
use App\Filament\Support\ConsoleStatus;
use App\Filament\Support\OrderActions;
use App\Models\WhmcsOrder;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WhmcsOrderResource extends Resource
{
    protected static ?string $model = WhmcsOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $modelLabel = 'order';

    protected static ?string $recordTitleAttribute = 'order_num';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $pending = WhmcsOrder::query()->where('status', 'Pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->columns([
                TextColumn::make('whmcs_order_id')->label('#')->sortable()->searchable(),
                TextColumn::make('order_num')->label('Order number')->searchable()->toggleable(),
                TextColumn::make('customer.full_name')->label('Client')
                    ->url(fn ($record) => $record->customer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                TextColumn::make('placed_at')->label('Placed')->dateTime()->sortable(),
                TextColumn::make('amount')->numeric(2)->sortable(),
                TextColumn::make('payment_status')->label('Payment')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                TextColumn::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
            ])
            ->filters([
                SelectFilter::make('status')->multiple()->options([
                    'Pending' => 'Pending', 'Active' => 'Active', 'Cancelled' => 'Cancelled', 'Fraud' => 'Fraud',
                ]),
                SelectFilter::make('payment_status')->label('Payment')->options(['Paid' => 'Paid', 'Unpaid' => 'Unpaid']),
            ])
            ->recordActions([
                ViewAction::make(),
                OrderActions::group(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Items')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->state(fn (WhmcsOrder $record) => OrderActions::items($record))
                            ->columns(4)
                            ->schema([
                                TextEntry::make('product')->hiddenLabel()->columnSpan(2),
                                TextEntry::make('cycle')->hiddenLabel(),
                                TextEntry::make('amount')->hiddenLabel()->alignEnd(),
                            ])
                            ->placeholder('No line items recorded.'),
                    ]),
                Section::make('Order')
                    ->schema([
                        TextEntry::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                        TextEntry::make('payment_status')->label('Payment')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                        TextEntry::make('customer.full_name')->label('Client')
                            ->url(fn ($record) => $record->customer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                        TextEntry::make('placed_at')->label('Placed')->dateTime(),
                        TextEntry::make('amount')->numeric(2),
                        TextEntry::make('payment_method')->label('Method')->placeholder('—'),
                        TextEntry::make('whmcs_invoice_id')->label('Invoice #')->placeholder('—'),
                    ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhmcsOrders::route('/'),
            'view' => ViewWhmcsOrder::route('/{record}'),
        ];
    }
}
