<?php

namespace App\Platform\Navigation\Filament\Resources;

use App\Platform\Navigation\Filament\Resources\NavMenuResource\Pages;
use App\Platform\Navigation\Models\NavMenu;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

/**
 * Read-mostly view of the navigation MENU LOCATIONS. Locations are seeded (one per MenuLocation) and
 * not created ad hoc here — admins edit the items in NavItemResource. This resource lets an admin
 * activate/deactivate a whole location (an inactive menu is not served by the public API, so the
 * frontend falls back to its hardcoded config). Admin/super-admin gated by the panel.
 */
class NavMenuResource extends Resource
{
    protected static ?string $model = NavMenu::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = 'Navigation';

    protected static ?string $navigationLabel = 'Menu Locations';

    protected static ?string $recordRouteKeyName = 'public_id';

    /** Menu locations are the fixed MenuLocation set — seeded, never created ad hoc. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('location')
            ->columns([
                TextColumn::make('location')
                    ->label('Location')->badge()
                    ->formatStateUsing(fn ($state) => is_object($state) ? $state->label() : $state)
                    ->searchable(),
                TextColumn::make('items_count')->counts('items')->label('Items'),
                ToggleColumn::make('is_active')->label('Active'),
                TextColumn::make('updated_at')->dateTime()->since()->toggleable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNavMenus::route('/'),
        ];
    }
}
