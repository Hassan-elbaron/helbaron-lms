<?php

namespace App\Domains\Authoring\Actions\Section;

use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Events\SectionPublished;
use App\Domains\Authoring\Models\Section;
use App\Platform\Shared\Actions\BaseAction;

class SetSectionPublishStateAction extends BaseAction
{
    public function execute(Section $section, PublishState $state): Section
    {
        $wasPublished = $section->isPublished();

        $section = $this->transaction(function () use ($section, $state): Section {
            $section->forceFill(['publish_state' => $state->value])->save();

            return $section;
        });

        if (! $wasPublished && $state->isPublished()) {
            SectionPublished::dispatch($section);
        }

        return $section;
    }
}
