<?php

namespace App\Platform\Features\Filament\Resources\FeatureFlagResource\Pages;

use App\Platform\Features\Filament\Resources\FeatureFlagResource;
use App\Platform\Features\Models\FeatureFlag;
use App\Platform\Shared\Audit\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeatureFlag extends EditRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** Every flag update is audited (feature_flag.updated). */
    protected function afterSave(): void
    {
        /** @var FeatureFlag $record */
        $record = $this->getRecord();
        app(AuditLogger::class)->log('feature_flag.updated', $record, ['key' => $record->key]);
    }
}
