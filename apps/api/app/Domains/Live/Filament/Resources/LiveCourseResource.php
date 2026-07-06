<?php

namespace App\Domains\Live\Filament\Resources;

use App\Domains\Live\Filament\Resources\LiveCourseResource\Pages;
use App\Domains\Live\Models\LiveCourse;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LiveCourseResource extends Resource
{
    protected static ?string $model = LiveCourse::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-video-camera';

    protected static string|\UnitEnum|null $navigationGroup = 'Live';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            Textarea::make('description')->rows(3),
            TextInput::make('timezone')->default('UTC'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable(),
            TextColumn::make('timezone'),
            IconColumn::make('is_active')->boolean(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLiveCourse::route('/'),
            'create' => Pages\CreateLiveCourse::route('/create'),
            'edit' => Pages\EditLiveCourse::route('/{record}/edit'),
        ];
    }
}
