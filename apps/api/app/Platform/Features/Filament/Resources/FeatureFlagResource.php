<?php

namespace App\Platform\Features\Filament\Resources;

use App\Platform\Features\Filament\Resources\FeatureFlagResource\Pages;
use App\Platform\Features\Models\FeatureFlag;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Identity\Enums\Role;
use App\Platform\Shared\Audit\AuditLogger;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * The Feature Flags admin — manage presentation/rollout flags. Flags default to ENABLED; turning
 * one OFF (or scoping it by environment / role / percentage / schedule) is an explicit choice.
 * `key` is immutable after creation (call sites depend on it). Every update writes an audit entry
 * (feature_flag.updated). Admin/super-admin gated (defence in depth over the panel gate).
 */
class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Feature Flags';

    protected static ?string $recordRouteKeyName = 'public_id';

    protected static ?string $recordTitleAttribute = 'name';

    /** Environment options — null (unset) means "all environments". */
    private const ENVIRONMENTS = [
        'production' => 'Production',
        'staging' => 'Staging',
        'local' => 'Local',
    ];

    /** Gate the whole resource to admins (the panel already requires it; defence in depth). */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof Actor && $user->hasRole(['admin', 'super_admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identity')->columns(2)->schema([
                TextInput::make('key')->required()->maxLength(64)
                    ->rule('regex:/^[a-z0-9_]+$/')
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit')
                    ->helperText('Stable machine key (lowercase, digits, underscores), e.g. "events". Immutable after creation.'),
                TextInput::make('name')->required()->maxLength(120),
                Textarea::make('description')->rows(2)->columnSpanFull(),
                TextInput::make('owner')->maxLength(120)
                    ->helperText('Optional owning team/person.'),
            ]),

            Section::make('Targeting')->columns(2)
                ->description('An ENABLED flag is further constrained by these. Leave them empty for "everyone, everywhere".')
                ->schema([
                    Toggle::make('is_enabled')->label('Enabled')->default(true)->inline(false)
                        ->helperText('Master switch. Flags default to ENABLED so nothing hides by accident.'),
                    Select::make('environment')->options(self::ENVIRONMENTS)
                        ->placeholder('All environments')
                        ->helperText('Restrict to one environment. Empty = all.'),
                    Select::make('roles')->label('Target roles')->multiple()
                        ->options(self::roleOptions())
                        ->helperText('Only these roles see the feature. Empty = all roles (guests included).'),
                    TextInput::make('rollout_percentage')->label('Rollout %')->numeric()
                        ->minValue(0)->maxValue(100)->placeholder('100')
                        ->helperText('Deterministic percentage rollout. Empty or 100 = everyone; 0 = nobody.'),
                    DateTimePicker::make('starts_at')->label('Starts at')->seconds(false)
                        ->helperText('Optional — flag is inactive before this time.'),
                    DateTimePicker::make('ends_at')->label('Ends at')->seconds(false)
                        ->helperText('Optional — flag is inactive after this time.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            ->columns([
                TextColumn::make('key')->badge()->searchable()->sortable(),
                TextColumn::make('name')->searchable()->toggleable(),
                ToggleColumn::make('is_enabled')->label('Enabled')
                    ->afterStateUpdated(function (FeatureFlag $record): void {
                        app(AuditLogger::class)->log('feature_flag.updated', $record, [
                            'key' => $record->key,
                            'is_enabled' => $record->is_enabled,
                        ]);
                    }),
                TextColumn::make('environment')->badge()->placeholder('All')->toggleable(),
                TextColumn::make('rollout_percentage')->label('Rollout %')->placeholder('100')->toggleable(),
                IconColumn::make('roles')->label('Targeted')->boolean()
                    ->state(fn (FeatureFlag $record): bool => ! empty($record->roles))->toggleable(),
                TextColumn::make('owner')->placeholder('—')->toggleable(),
                TextColumn::make('updated_at')->dateTime()->since()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')->label('Enabled'),
                SelectFilter::make('environment')->options(self::ENVIRONMENTS),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeatureFlags::route('/'),
            'create' => Pages\CreateFeatureFlag::route('/create'),
            'edit' => Pages\EditFeatureFlag::route('/{record}/edit'),
        ];
    }

    /** @return array<string, string> role value => label */
    private static function roleOptions(): array
    {
        $options = [];

        foreach (Role::cases() as $role) {
            $options[$role->value] = ucwords(str_replace('_', ' ', $role->value));
        }

        return $options;
    }
}
