<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class StudentVerifyEmail extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'student.verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            ['student' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
        );

        return (new MailMessage)
            ->subject('Verifikasi email akun Fibo')
            ->greeting('Halo, '.$notifiable->name)
            ->line('Verifikasi alamat email untuk mengaktifkan fitur Top-up dan pembelian akses.')
            ->action('Verifikasi email', $url)
            ->line('Tautan berlaku selama satu jam.');
    }
}
