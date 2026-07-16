<?php

namespace App\Gamification;

use App\Enums\BadgeKey;

final readonly class BadgeDefinition
{
    public function __construct(
        public BadgeKey $key,
        public string $name,
        public string $description,
        public string $category,
        public string $tier,
        public string $metric,
        public int $threshold,
        public string $icon,
    ) {}
}
