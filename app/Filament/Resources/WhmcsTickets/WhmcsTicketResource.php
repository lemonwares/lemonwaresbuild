<?php

namespace App\Filament\Resources\WhmcsTickets;

use App\Filament\Resources\WhmcsCustomers\WhmcsCustomerResource;
use App\Filament\Resources\WhmcsTickets\Pages\ListWhmcsTickets;
use App\Filament\Resources\WhmcsTickets\Pages\ViewWhmcsTicket;
use App\Filament\Support\ConsoleOptions;
use App\Filament\Support\ConsoleStatus;
use App\Filament\Support\TicketActions;
use App\Models\WhmcsTicket;
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

class WhmcsTicketResource extends Resource
{
    protected static ?string $model = WhmcsTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?string $modelLabel = 'ticket';

    protected static ?string $recordTitleAttribute = 'subject';

    protected static ?int $navigationSort = 6;

    public const AWAITING = ['Open', 'Customer-Reply'];

    public static function getNavigationBadge(): ?string
    {
        $awaiting = WhmcsTicket::query()->whereIn('status', self::AWAITING)->count();

        return $awaiting > 0 ? (string) $awaiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return self::ticketTable($table);
    }

    public static function ticketTable(Table $table, bool $withClient = true): Table
    {
        return $table
            ->defaultSort('last_reply_at', 'desc')
            ->columns(array_values(array_filter([
                TextColumn::make('tid')->label('Ticket')->searchable(),
                TextColumn::make('subject')->searchable()->limit(60)->wrap(),
                $withClient
                    ? TextColumn::make('customer.full_name')->label('Client')->placeholder('Guest')
                        ->url(fn ($record) => $record->customer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->customer]) : null)
                    : null,
                TextColumn::make('department_id')->label('Department')
                    ->formatStateUsing(fn ($state, $record) => $record->department ?: (ConsoleOptions::departments()[$state] ?? $state))
                    ->toggleable(),
                TextColumn::make('priority')->badge()->color(fn (?string $state) => $state === 'High' ? 'danger' : 'gray'),
                TextColumn::make('last_reply_at')->label('Last reply')->since()->sortable(),
                TextColumn::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
            ])))
            ->filters([
                SelectFilter::make('status')->multiple()->options(TicketActions::STATUSES)
                    ->default($withClient ? self::AWAITING : null),
                SelectFilter::make('priority')->options(TicketActions::PRIORITIES),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (WhmcsTicket $record) => self::getUrl('view', ['record' => $record])),
                TicketActions::group(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Conversation')
                    ->columnSpan(2)
                    ->schema([
                        RepeatableEntry::make('thread')
                            ->hiddenLabel()
                            ->state(fn (WhmcsTicket $record) => TicketActions::replies($record))
                            ->schema([
                                TextEntry::make('author')->hiddenLabel()->weight('bold'),
                                TextEntry::make('date')->hiddenLabel()->color('gray')->size('sm'),
                                TextEntry::make('message')->hiddenLabel()
                                    ->formatStateUsing(fn (?string $state) => nl2br(e((string) $state)))
                                    ->html(),
                            ])
                            ->placeholder('No messages returned by WHMCS.'),
                    ]),
                Section::make('Ticket')
                    ->schema([
                        TextEntry::make('tid')->label('Ticket'),
                        TextEntry::make('status')->badge()->color(fn (?string $state) => ConsoleStatus::color($state)),
                        TextEntry::make('priority'),
                        TextEntry::make('department_id')->label('Department')
                            ->formatStateUsing(fn ($state, $record) => $record->department ?: (ConsoleOptions::departments()[$state] ?? $state))
                            ->placeholder('—'),
                        TextEntry::make('customer.full_name')->label('Client')->placeholder('Guest')
                            ->url(fn ($record) => $record->customer ? WhmcsCustomerResource::getUrl('view', ['record' => $record->customer]) : null),
                        TextEntry::make('opened_at')->label('Opened')->dateTime()->placeholder('—'),
                        TextEntry::make('last_reply_at')->label('Last reply')->since()->placeholder('—'),
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
            'index' => ListWhmcsTickets::route('/'),
            'view' => ViewWhmcsTicket::route('/{record}'),
        ];
    }
}
