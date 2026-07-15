<?php

namespace App\Platform\Navigation\Filament\Resources;

use App\Platform\Navigation\Enums\NavAuthVisibility;
use App\Platform\Navigation\Enums\NavUrlType;
use App\Platform\Navigation\Filament\Resources\NavItemResource\Pages;
use App\Platform\Navigation\Models\NavItem;
use App\Platform\Navigation\Models\NavMenu;
use App\Platform\Navigation\Support\NavUrl;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

/**
 * The Navigation Builder — edits the admin-managed nav items, grouped by their menu location and
 * drag-reorderable within each location. Supports bilingual labels/badges/descriptions, safe
 * internal/external URLs (validated with the SAME rule as the API), parent nesting, icons, enable/
 * disable, open-in-new-tab, rel, image, and per-item visibility (roles / auth-state / locales /
 * feature flag). Admin/super-admin gated by the panel.
 */
class NavItemResource extends Resource
{
    protected static ?string $model = NavItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static string|\UnitEnum|null $navigationGroup = 'Navigation';

    protected static ?string $navigationLabel = 'Nav Items';

    protected static ?string $recordTitleAttribute = 'url';

    protected static ?string $recordRouteKeyName = 'public_id';

    /** @return array<string, string> Role names offered for per-item visibility gating. */
    private static function roleOptions(): array
    {
        return [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'instructor' => 'Instructor',
            'student' => 'Student',
            'org_manager' => 'Organization Manager',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('menu_id')
                ->label('Menu location')
                ->relationship('menu', 'location')
                ->getOptionLabelFromRecordUsing(fn (NavMenu $record) => $record->location->label())
                ->required()
                ->searchable()
                ->preload()
                ->live(),

            Select::make('parent_id')
                ->label('Parent item')
                ->helperText('Optional — nest this item under another item in the same menu.')
                ->options(function (Get $get, ?NavItem $record): array {
                    $menuId = $get('menu_id');
                    if (blank($menuId)) {
                        return [];
                    }

                    return NavItem::query()
                        ->where('menu_id', $menuId)
                        ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                        ->whereNull('parent_id')
                        ->orderBy('position')
                        ->get()
                        ->mapWithKeys(fn (NavItem $i) => [$i->id => ($i->label['en'] ?? '(untitled)')])
                        ->all();
                })
                ->searchable(),

            TextInput::make('label.en')->label('Label (EN)')->required()->maxLength(120),
            TextInput::make('label.ar')->label('Label (AR)')->maxLength(120),

            Select::make('url_type')
                ->label('Link type')
                ->options(NavUrlType::options())
                ->default(NavUrlType::Internal->value)
                ->required()
                ->live(),

            TextInput::make('url')
                ->label('URL')
                ->required()
                ->maxLength(2048)
                ->helperText('Internal: a path like /courses or #anchor. External: a full https:// URL.')
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        if (! is_string($value) || ! NavUrl::isSafe((string) ($get('url_type') ?? 'internal'), $value)) {
                            $fail('The URL is not a safe or valid link for its type (javascript:, data:, etc. are rejected).');
                        }
                    },
                ]),

            TextInput::make('icon')->label('Icon key')->maxLength(64)
                ->helperText('Lucide icon name, e.g. LayoutDashboard (sidebars only).'),
            TextInput::make('position')->label('Position')->numeric()->default(0)
                ->helperText('Lower numbers render first. You can also drag rows to reorder.'),

            Toggle::make('is_enabled')->label('Enabled')->default(true)->inline(false),
            Toggle::make('open_new_tab')->label('Open in new tab')->inline(false),
            TextInput::make('rel')->label('rel attribute')->maxLength(120)
                ->helperText('noopener noreferrer is added automatically for new-tab / external links.'),

            TextInput::make('badge.en')->label('Badge (EN)')->maxLength(40),
            TextInput::make('badge.ar')->label('Badge (AR)')->maxLength(40),
            Textarea::make('description.en')->label('Description (EN)')->rows(2),
            Textarea::make('description.ar')->label('Description (AR)')->rows(2),
            TextInput::make('image')->label('Image URL')->url()->maxLength(2048),

            CheckboxList::make('visibility_roles')
                ->label('Visible to roles')
                ->options(self::roleOptions())
                ->columns(2)
                ->helperText('Leave all unchecked to show to every role.'),
            Select::make('visibility_auth')
                ->label('Auth visibility')
                ->options(NavAuthVisibility::options())
                ->default(NavAuthVisibility::Any->value)
                ->required(),
            CheckboxList::make('visibility_locales')
                ->label('Visible in locales')
                ->options(['en' => 'English', 'ar' => 'Arabic'])
                ->columns(2)
                ->helperText('Leave all unchecked to show in every locale.'),
            TextInput::make('feature_flag')->label('Feature flag key')->maxLength(120)
                ->helperText('Optional — item only shows when this flag is on (frontend-resolved).'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('position')
            ->defaultGroup('menu.location')
            ->groups([
                Group::make('menu.location')
                    ->label('Menu')
                    ->getTitleFromRecordUsing(fn (NavItem $record) => $record->menu->location->label()),
            ])
            ->defaultSort('position')
            ->columns([
                TextColumn::make('label.en')->label('Label')->searchable()->wrap(),
                TextColumn::make('menu.location')
                    ->label('Menu')->badge()
                    ->formatStateUsing(fn ($state) => is_object($state) ? $state->label() : $state)
                    ->toggleable(),
                TextColumn::make('url')->label('URL')->limit(40)->toggleable(),
                TextColumn::make('url_type')->badge()->toggleable(),
                TextColumn::make('parent.label.en')->label('Parent')->placeholder('—')->toggleable(),
                IconColumn::make('is_enabled')->boolean()->label('Enabled'),
                TextColumn::make('visibility_auth')->badge()->label('Auth')->toggleable(),
                TextColumn::make('position')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('menu_id')
                    ->label('Menu')
                    ->relationship('menu', 'location')
                    ->getOptionLabelFromRecordUsing(fn (NavMenu $record) => $record->location->label()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNavItems::route('/'),
            'create' => Pages\CreateNavItem::route('/create'),
            'edit' => Pages\EditNavItem::route('/{record}/edit'),
        ];
    }
}
