<?php

namespace App\Platform\Pages\Filament\Resources\StaticPageResource\Pages;

use App\Platform\Pages\Actions\UpdateStaticPageAction;
use App\Platform\Pages\Enums\PageStatus;
use App\Platform\Pages\Filament\Resources\StaticPageResource;
use App\Platform\Pages\Models\StaticPage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStaticPage extends EditRecord
{
    protected static string $resource = StaticPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->visible(fn (StaticPage $record): bool => $record->status !== PageStatus::Published)
                ->requiresConfirmation()
                ->action(function (StaticPage $record): void {
                    app(UpdateStaticPageAction::class)->publish($record);
                    Notification::make()->title('Page published')->success()->send();
                    $this->fillForm();
                }),
            DeleteAction::make(),
        ];
    }
}
