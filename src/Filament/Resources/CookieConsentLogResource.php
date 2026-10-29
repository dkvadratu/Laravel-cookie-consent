<?php

namespace Dkvadratu\CookieConsent\Filament\Resources;

use BackedEnum;
use Dkvadratu\CookieConsent\CookieConsentLog;
use Dkvadratu\CookieConsent\Filament\Resources\Pages\ListCookieConsentLogs;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class CookieConsentLogResource extends Resource
{
    protected static ?string $model = CookieConsentLog::class;

    protected static ?string $navigationLabel = 'Slapukų sutikimai';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function canViewAny(): bool
    {
        return Gate::allows('view-cookie-consent-logs');
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Data')->dateTime()->sortable(),
            TextColumn::make('action')->label('Veiksmas')->sortable(),
            TextColumn::make('consented_categories')->label('Kategorijos')->badge(),
            TextColumn::make('cookie_id')->label('Identifikatorius')->searchable()->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListCookieConsentLogs::route('/')];
    }
}
