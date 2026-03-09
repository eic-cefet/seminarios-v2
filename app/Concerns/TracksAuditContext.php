<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Context;

trait TracksAuditContext
{
    protected function setAuditContext(): void
    {
        Context::add('audit.origin', class_basename(static::class));
    }
}
