<?php

namespace App\Contexts\Learning\Filament\Resources;

use App\Contexts\Learning\Enums\EnrollmentStatus;
use App\Contexts\Learning\Filament\Resources\EnrollmentResource\Pages;
use App\Contexts\Learning\Models\Enrollment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Admin resource for learning enrollments (read + light state correction). Creation is disabled
 * so enrollments are only minted through the domain enrollment flow, not the panel.
 */
class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Learning';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->options(collect(EnrollmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all())
                ->required(),
            TextInput::make('progress_percentage')->numeric()->minValue(0)->maxValue(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.email')->label('User')->searchable()->sortable(),
                TextColumn::make('course.public_id')->label('Course')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('progress_percentage')->label('Progress %')->numeric()->sortable(),
                TextColumn::make('source')->toggleable(),
                TextColumn::make('enrolled_at')->dateTime()->sortable()->toggleable(),
                TextColumn::make('completed_at')->dateTime()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(EnrollmentStatus::cases())->mapWithKeys(fn ($s) => [$s->value => ucfirst($s->value)])->all(),
                ),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
