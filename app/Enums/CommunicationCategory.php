<?php

namespace App\Enums;

enum CommunicationCategory: string
{
    case SeminarReminder7d = 'seminar_reminder_7d';
    case SeminarReminder24h = 'seminar_reminder_24h';
    case EvaluationPrompt = 'evaluation_prompt';
    case Announcements = 'announcements';
    case TopicFollow = 'topic_follow';

    public function isTransactional(): bool
    {
        return $this !== self::TopicFollow;
    }

    public function column(): string
    {
        return $this === self::TopicFollow ? 'opted_in' : $this->value;
    }

    public function defaultWhenMissing(): bool
    {
        return $this->isTransactional();
    }
}
