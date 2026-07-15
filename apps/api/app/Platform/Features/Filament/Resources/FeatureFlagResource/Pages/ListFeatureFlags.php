<?php

namespace App\Platform\Features\Filament\Resources\FeatureFlagResource\Pages;

use App\Platform\Features\Filament\Resources\FeatureFlagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeatureFlags extends ListRecords
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
