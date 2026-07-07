<?php

namespace App\Contexts\Analytics\Filament\Resources;

use App\Contexts\Analytics\Filament\Resources\ExportJobResource\Pages;
use App\Contexts\Analytics\Models\ExportJob;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExportJobResource extends Resource
{
    protected static ?string $model = ExportJob::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Analytics';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('format')->toggleable(),
            TextColumn::make('status')->toggleable(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListExport::route('/')];
    }
}
