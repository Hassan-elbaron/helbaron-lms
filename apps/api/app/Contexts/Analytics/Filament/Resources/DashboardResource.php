<?php

namespace App\Contexts\Analytics\Filament\Resources;

use App\Contexts\Analytics\Filament\Resources\DashboardResource\Pages;
use App\Contexts\Analytics\Models\DashboardDefinition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DashboardResource extends Resource
{
    protected static ?string $model = DashboardDefinition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->toggleable(),
            TextColumn::make('key')->toggleable(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListDashboard::route('/')];
    }
}
