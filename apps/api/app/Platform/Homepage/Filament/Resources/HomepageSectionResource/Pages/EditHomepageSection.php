<?php

namespace App\Platform\Homepage\Filament\Resources\HomepageSectionResource\Pages;

use App\Platform\Homepage\Filament\Resources\HomepageSectionResource;
use App\Platform\Homepage\Models\HomepageSection;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditHomepageSection extends EditRecord
{
    protected static string $resource = HomepageSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Publish')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Publish this block? The current draft becomes the live copy on the public homepage.')
                ->action(function (): void {
                    /** @var HomepageSection $record */
                    $record = $this->getRecord();
                    $record->publish();
                    Notification::make()->title('Block published')->success()->send();
                }),
            Action::make('preview')
                ->label('Preview draft')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(rtrim((string) config('shared.frontend_url'), '/').'/?preview=1', shouldOpenInNewTab: true),
        ];
    }
}
