<?php

namespace App\Domains\Notifications\Data;

final readonly class RenderedMessage
{
    public function __construct(
        public string $subject,
        public string $body,
        public string $locale,
    ) {}
}
