<?php

namespace App\Domains\Authoring\Actions\Section;

use App\Domains\Authoring\Models\Section;
use App\Shared\Actions\BaseAction;

class UpdateSectionAction extends BaseAction
{
    /** @param array<string, mixed> $data */
    public function execute(Section $section, array $data): Section
    {
        return $this->transaction(function () use ($section, $data): Section {
            $section->fill(array_filter([
                'title' => $data['title'] ?? null,
                'summary' => $data['summary'] ?? null,
            ], fn ($v) => $v !== null));
            $section->save();

            return $section;
        });
    }
}
