<?php

namespace App\Domains\Crm\Filament\Resources;

use App\Domains\Crm\Filament\Resources\ConsultingRequestResource\Pages;
use App\Domains\Crm\Models\ConsultingRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsultingRequestResource extends Resource
{
    protected static ?string $model = ConsultingRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';

    protected static string|\UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('subject')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('sla_due_at')->dateTime()->toggleable(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListConsultingRequest::route('/')];
    }
}
