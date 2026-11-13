<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentPasswordReset extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/student/password/reset?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]));

        return (new MailMessage)
            ->subject('Atur ulang kata sandi Fibo')
            ->greeting('Halo, '.$notifiable->name)
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun Fibo kamu.')
            ->action('Atur ulang kata sandi', $url)
            ->line('Jika kamu tidak meminta reset, abaikan email ini. Tautan berlaku selama satu jam.');
    }
}
