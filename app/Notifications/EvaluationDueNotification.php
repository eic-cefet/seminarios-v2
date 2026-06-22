<?php

namespace App\Notifications;

use App\Models\Seminar;
use App\Support\PresentationTypeGrammar;

class EvaluationDueNotification extends InAppNotification
{
    public function __construct(public Seminar $seminar) {}

    protected function category(): string
    {
        return 'evaluation_due';
    }

    protected function title(): string
    {
        return 'Avaliação pendente';
    }

    protected function body(): string
    {
        return 'Avalie '.PresentationTypeGrammar::for($this->seminar->seminarType?->name)->definite().' "'.$this->seminar->name.'".';
    }

    protected function actionUrl(): ?string
    {
        return '/avaliar';
    }
}
