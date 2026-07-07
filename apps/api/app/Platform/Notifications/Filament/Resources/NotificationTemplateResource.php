<?php

namespace App\Platform\Notifications\Filament\Resources;

use App\Platform\Notifications\Filament\Resources\NotificationTemplateResource\Pages;
use App\Platform\Notifications\Models\NotificationTemplate;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('key')->toggleable(),
            TextColumn::make('channel')->toggleable(),
            TextColumn::make('locale')->toggleable(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListTemplate::route('/')];
    }
}
