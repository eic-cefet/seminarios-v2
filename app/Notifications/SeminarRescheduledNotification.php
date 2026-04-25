<?php

namespace App\Notifications;

use App\Models\Seminar;

class SeminarRescheduledNotification extends InAppNotification
{
    public function __construct(
        public Seminar $seminar,
        public string $previousStartsAt,
    ) {}

    protected function category(): string
    {
        return 'seminar_rescheduled';
    }

    protected function title(): string
    {
        return 'Seminário reagendado';
    }

    protected function body(): string
    {
        return "\"{$this->seminar->name}\" foi reagendado. Confira a nova data.";
    }

    protected function actionUrl(): ?string
    {
        return "/seminario/{$this->seminar->slug}";
    }
}
