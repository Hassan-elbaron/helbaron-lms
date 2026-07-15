<?php

namespace App\Platform\Branding\Filament\Resources\BrandSettingResource\Pages;

use App\Platform\Branding\Filament\Resources\BrandSettingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditBrandSetting extends EditRecord
{
    protected static string $resource = BrandSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('View live site')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(rtrim((string) config('shared.frontend_url'), '/'), shouldOpenInNewTab: true),
        ];
    }
}
