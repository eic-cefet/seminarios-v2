<?php

namespace App\Enums;

enum CommunicationCategory: string
{
    case SeminarReminder7d = 'seminar_reminder_7d';
    case SeminarReminder24h = 'seminar_reminder_24h';
    case EvaluationPrompt = 'evaluation_prompt';
    case CertificateReady = 'certificate_ready';
    case SeminarRescheduled = 'seminar_rescheduled';
    case Announcements = 'announcements';
    case NewSeminarAlert = 'new_seminar_alert';

    public function isTransactional(): bool
    {
        return $this !== self::NewSeminarAlert;
    }

    public function column(): string
    {
        return $this->value;
    }

    public function defaultWhenMissing(): bool
    {
        return $this->isTransactional();
    }
}
