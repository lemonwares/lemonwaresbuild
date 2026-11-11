<?php

namespace App\Filament\Resources\WhmcsInvoices;

use App\Filament\Resources\WhmcsCustomers\WhmcsCustomerResource;
use App\Filament\Resources\WhmcsInvoices\Pages\ListWhmcsInvoices;
use App\Filament\Resources\WhmcsInvoices\Pages\ViewWhmcsInvoice;
use App\Filament\Support\ConsoleStatus;
use App\Filament\Support\InvoiceActions;
use App\Models\WhmcsInvoice;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WhmcsInvoiceResource extends Resource
{
    protected static ?string $model = WhmcsInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $modelLabel = 'invoice';

    protected static ?string $recordTitleAttribute = 'whmcs_invoice_id';

    protected static ?int $navigationSort = 3;

    public const STATUSES = [
        'Unpaid' => 'Unpaid',
        'Payment Pending' => 'Payment pending',
        'Paid' => 'Paid',
        'Cancelled' => 'Cancelled',
        'Refunded' => 'Refunded',
        'Collections' => 'Collections',
        'Draft' => 'Draft',
    ];

    public static function getNavigationBadge(): ?string
    {
        $overdue = WhmcsInvoice::query()->where('status', 'Unpaid')->whereDate('due_date', '<', today())->count();

        return $overdue > 0 ? (string) $overdue : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('whmcs_invoice_id')->label('#')->sortable()->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record->invoice_num ?: $state),
                TextColumn::make('customer.full_name')->label('Client')
                    ->url(fn ($record) => $record->customer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                TextColumn::make('date')->date()->sortable(),
                TextColumn::make('due_date')->label('Due')->date()->sortable()
                    ->color(fn ($record) => $record->status === 'Unpaid' && $record->due_date?->isPast() ? 'danger' : null),
                TextColumn::make('total')->numeric(2)->sortable()->suffix(fn ($record) => ' '.$record->currency_code),
                TextColumn::make('payment_method')->label('Method')->toggleable(),
                TextColumn::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::STATUSES)->multiple(),
                Filter::make('overdue')->label('Overdue')
                    ->query(fn (Builder $query) => $query->where('status', 'Unpaid')->whereDate('due_date', '<', today())),
            ])
            ->recordActions([
                ViewAction::make(),
                InvoiceActions::group(),
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
                            ->state(fn (WhmcsInvoice $record) => InvoiceActions::items($record))
                            ->columns(4)
                            ->schema([
                                TextEntry::make('description')->hiddenLabel()->columnSpan(3),
                                TextEntry::make('amount')->hiddenLabel()->numeric(2)->alignEnd(),
                            ])
                            ->placeholder('No line items returned by WHMCS.'),
                    ]),
                Section::make('Invoice')
                    ->schema([
                        TextEntry::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                        TextEntry::make('customer.full_name')->label('Client')
                            ->url(fn ($record) => $record->customer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                        TextEntry::make('date')->date(),
                        TextEntry::make('due_date')->label('Due')->date(),
                        TextEntry::make('date_paid')->label('Paid')->dateTime()->placeholder('—'),
                        TextEntry::make('credit')->label('Credit applied')->numeric(2),
                        TextEntry::make('tax')->numeric(2),
                        TextEntry::make('total')->numeric(2)->suffix(fn ($record) => ' '.$record->currency_code)->weight('bold'),
                        TextEntry::make('payment_method')->label('Method')->placeholder('—'),
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
            'index' => ListWhmcsInvoices::route('/'),
            'view' => ViewWhmcsInvoice::route('/{record}'),
        ];
    }
}
