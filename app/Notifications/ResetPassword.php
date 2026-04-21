<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class ResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public string $refId;

    public function __construct(
        public string $token,
    ) {
        $this->refId = 'password-reset:'.(string) Str::uuid();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/redefinir-senha?token='.$this->token.'&email='.urlencode($notifiable->getEmailForPasswordReset()));
        $refId = $this->refId;

        return (new MailMessage)
            ->subject('Redefinir Senha - '.config('app.name'))
            ->withSymfonyMessage(function (Email $message) use ($refId) {
                $message->getHeaders()->addTextHeader('X-Entity-Ref-ID', $refId);
                $message->getHeaders()->addTextHeader('X-Mail-Class', self::class);
            })
            ->greeting('Olá!')
            ->line('Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha para sua conta.')
            ->action('Redefinir Senha', $url)
            ->line('Este link de redefinição de senha expirará em 60 minutos.')
            ->line('Se você não solicitou uma redefinição de senha, nenhuma ação adicional é necessária.')
            ->salutation('Atenciosamente, '.config('mail.team_name'));
    }
}
