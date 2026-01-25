<?php

namespace App\Http\Resources\Concerns;

use Carbon\Carbon;

trait FormatsDates
{
    /**
     * Format a date to ISO 8601 string.
     */
    protected function formatDate(?Carbon $date): ?string
    {
        return $date?->toIso8601String();
    }
}
