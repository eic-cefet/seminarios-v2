<?php

namespace App\Notifications;

use App\Models\Seminar;

class CertificateReadyNotification extends InAppNotification
{
    public function __construct(
        public Seminar $seminar,
        public string $certificateUrl,
    ) {}

    protected function category(): string
    {
        return 'certificate_ready';
    }

    protected function title(): string
    {
        return 'Seu certificado está pronto';
    }

    protected function body(): string
    {
        return "O certificado da apresentação \"{$this->seminar->name}\" já está disponível.";
    }

    protected function actionUrl(): ?string
    {
        return $this->certificateUrl;
    }
}
