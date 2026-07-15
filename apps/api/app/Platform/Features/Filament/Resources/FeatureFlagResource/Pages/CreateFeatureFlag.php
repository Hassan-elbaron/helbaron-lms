<?php

namespace App\Platform\Features\Filament\Resources\FeatureFlagResource\Pages;

use App\Platform\Features\Filament\Resources\FeatureFlagResource;
use App\Platform\Features\Models\FeatureFlag;
use App\Platform\Shared\Audit\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateFeatureFlag extends CreateRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected function afterCreate(): void
    {
        /** @var FeatureFlag $record */
        $record = $this->getRecord();
        app(AuditLogger::class)->log('feature_flag.created', $record, ['key' => $record->key]);
    }
}
