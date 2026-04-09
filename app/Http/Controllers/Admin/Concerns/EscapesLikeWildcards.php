<?php

namespace App\Http\Controllers\Admin\Concerns;

trait EscapesLikeWildcards
{
    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
