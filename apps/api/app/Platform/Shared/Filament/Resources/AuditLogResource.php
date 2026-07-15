<?php

namespace App\Platform\Shared\Filament\Resources;

use App\Platform\Shared\Audit\AuditLog;
use App\Platform\Shared\Filament\Resources\AuditLogResource\Pages;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only view over the immutable audit trail (privileged actions: refunds, certificate
 * revoke/reissue, etc.). No create/edit/delete — audit rows are append-only by design.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Audit Log';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable()->label('When'),
                TextColumn::make('action')->badge()->searchable(),
                TextColumn::make('actor_type')->toggleable(),
                TextColumn::make('actor_id')->label('Actor')->toggleable(),
                TextColumn::make('subject_type')->label('Subject')->toggleable(),
                TextColumn::make('subject_id')->label('Subject ID')->toggleable(),
                TextColumn::make('ip')->label('IP')->toggleable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAuditLog::route('/')];
    }
}
