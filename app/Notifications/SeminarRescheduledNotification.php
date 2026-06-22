<?php

namespace App\Notifications;

use App\Models\Seminar;
use App\Support\PresentationTypeGrammar;
use Illuminate\Support\Str;

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
        $grammar = PresentationTypeGrammar::for($this->seminar->seminarType?->name);

        return Str::ucfirst($grammar->noun()).' '.$grammar->agree('reagendado', 'reagendada');
    }

    protected function body(): string
    {
        $grammar = PresentationTypeGrammar::for($this->seminar->seminarType?->name);

        return Str::ucfirst($grammar->definite())." \"{$this->seminar->name}\" foi ".$grammar->agree('reagendado', 'reagendada').'. Confira a nova data.';
    }

    protected function actionUrl(): ?string
    {
        return "/seminario/{$this->seminar->slug}";
    }
}
