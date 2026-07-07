<?php

namespace App\Domains\Authoring\Actions\Section;

use App\Domains\Authoring\Models\Section;
use App\Platform\Shared\Actions\BaseAction;

class DeleteSectionAction extends BaseAction
{
    public function execute(Section $section): void
    {
        $this->transaction(fn () => $section->delete());
    }
}
