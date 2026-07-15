<?php

namespace App\Platform\Notifications\Filament\Resources;

use App\Platform\Notifications\Enums\Channel;
use App\Platform\Notifications\Filament\Resources\NotificationTemplateResource\Pages;
use App\Platform\Notifications\Models\NotificationTemplate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Admin management for notification/email templates (subject + body per channel + locale).
 * Presentation only — persistence is standard Eloquent on the NotificationTemplate model.
 */
class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Notifications';

    protected static ?string $navigationLabel = 'Templates';

    protected static ?string $recordRouteKeyName = 'public_id';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')->required()->maxLength(120)
                ->helperText('Stable identifier used by the dispatcher, e.g. "welcome" or "order.paid".'),
            Select::make('channel')
                ->options(collect(Channel::cases())->mapWithKeys(fn (Channel $c) => [$c->value => $c->name])->all())
                ->required(),
            Select::make('locale')->options(['en' => 'English', 'ar' => 'العربية'])->default('en')->required(),
            TextInput::make('subject')->maxLength(255)
                ->helperText('Used by email/push channels; ignored by in-app.'),
            Textarea::make('body')->required()->rows(8)
                ->helperText('Supports the placeholders documented for this template key.'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('key')->searchable()->sortable(),
            TextColumn::make('channel')->badge()->toggleable(),
            TextColumn::make('locale')->badge()->toggleable(),
            TextColumn::make('subject')->limit(40)->toggleable(),
            IconColumn::make('is_active')->boolean(),
            TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemplate::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }
}
