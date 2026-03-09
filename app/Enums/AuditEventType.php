<?php

namespace App\Enums;

enum AuditEventType: string
{
    case Manual = 'manual';
    case System = 'system';
}
