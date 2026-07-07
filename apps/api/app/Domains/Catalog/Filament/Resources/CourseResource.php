<?php

namespace App\Domains\Catalog\Filament\Resources;

use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Filament\Resources\CourseResource\Pages;
use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Enums\Visibility;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('subtitle')->maxLength(255),
            Textarea::make('description')->rows(4),
            Select::make('status')
                ->options(collect(CourseStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all())
                ->default(CourseStatus::Draft->value),
            Select::make('visibility')
                ->options(collect(Visibility::cases())->mapWithKeys(fn ($v) => [$v->value => $v->label()])->all())
                ->default(Visibility::Public->value),
            Select::make('level_id')->relationship('level', 'name')->searchable(),
            Select::make('language_id')->relationship('language', 'name')->searchable(),
            Toggle::make('is_featured'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(40),
                TextColumn::make('status')->badge(),
                IconColumn::make('is_featured')->boolean(),
                TextColumn::make('published_at')->dateTime()->sortable()->toggleable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
