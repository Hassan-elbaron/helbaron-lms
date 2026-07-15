<?php

namespace App\Platform\Branding\Filament\Resources\BrandSettingResource\Pages;

use App\Platform\Branding\Filament\Resources\BrandSettingResource;
use App\Platform\Branding\Models\BrandSetting;
use Filament\Resources\Pages\ListRecords;

class ListBrandSetting extends ListRecords
{
    protected static string $resource = BrandSettingResource::class;

    /** Ensure the singleton row exists so the list always has the one record to edit. */
    public function mount(): void
    {
        BrandSetting::current();

        parent::mount();
    }
}
