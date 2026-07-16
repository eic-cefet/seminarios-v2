<?php

namespace App\Notifications;

class BadgesUnlockedNotification extends InAppNotification
{
    /**
     * @param  array<int, array<string, mixed>>  $badges
     */
    public function __construct(public array $badges) {}

    protected function category(): string
    {
        return 'gamification';
    }

    protected function title(): string
    {
        return count($this->badges) === 1
            ? 'Nova conquista desbloqueada!'
            : 'Novas conquistas desbloqueadas!';
    }

    protected function body(): string
    {
        $count = count($this->badges);
        $names = array_column(array_slice($this->badges, 0, 3), 'name');

        if ($count === 1) {
            return "Você desbloqueou a conquista {$names[0]}.";
        }

        $remaining = $count - count($names);
        $suffix = $remaining > 0 ? " e mais {$remaining}" : '';

        return "Você desbloqueou {$count} conquistas: ".implode(', ', $names).$suffix.'.';
    }

    protected function actionUrl(): ?string
    {
        return '/conquistas';
    }
}
