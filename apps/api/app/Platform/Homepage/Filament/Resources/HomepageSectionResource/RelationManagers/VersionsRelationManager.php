<?php

namespace App\Platform\Homepage\Filament\Resources\HomepageSectionResource\RelationManagers;

use App\Platform\Homepage\Actions\UpdateHomepageSectionAction;
use App\Platform\Homepage\Models\HomepageSection;
use App\Platform\Homepage\Models\HomepageSectionVersion;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Version-history panel for a HomepageSection. Read-only list of append-only snapshots with a
 * per-row Rollback action that restores that version's content (itself recorded as a fresh version,
 * so history is never rewritten). Versions are created automatically on every block update. Mirrors
 * App\Platform\Pages\...\VersionsRelationManager.
 */
class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Version history';

    protected static ?string $recordTitleAttribute = 'version';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->defaultSort('version', 'desc')
            ->columns([
                TextColumn::make('version')->label('Version')->sortable(),
                TextColumn::make('snapshot.status')->label('Status')->badge()->placeholder('—'),
                TextColumn::make('snapshot.position')->label('Position')->placeholder('—')->toggleable(),
                TextColumn::make('author.name')->label('By')->placeholder('system')->toggleable(),
                TextColumn::make('created_at')->dateTime()->since()->label('Recorded'),
            ])
            ->recordActions([
                Action::make('rollback')
                    ->label('Rollback')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Restore this version? The block content is replaced with this snapshot and a new version is recorded.')
                    ->action(function (HomepageSectionVersion $record): void {
                        /** @var HomepageSection $section */
                        $section = $this->getOwnerRecord();
                        app(UpdateHomepageSectionAction::class)->rollback($section, $record->version);
                        Notification::make()->title('Rolled back to version '.$record->version)->success()->send();
                    }),
            ])
            ->headerActions([])
            ->toolbarActions([]);
    }
}
