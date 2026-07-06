<?php

namespace App\Domains\Analytics\Filament\Resources;

use App\Domains\Analytics\Filament\Resources\ReportDefinitionResource\Pages;
use App\Domains\Analytics\Models\ReportDefinition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportDefinitionResource extends Resource
{
    protected static ?string $model = ReportDefinition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

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
            TextColumn::make('type')->toggleable(),
            TextColumn::make('visibility')->toggleable(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListReport::route('/')];
    }
}
