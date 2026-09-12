<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification
{
    use Queueable;

    public function __construct(public string $otpCode, public int $expiresInMinutes = 5)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[DNS Manager] Kode OTP Verifikasi Login')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Kami mendeteksi permintaan masuk ke panel DNS Manager Pro.')
            ->line('Gunakan kode keamanan 6-digit berikut untuk menyelesaikan proses login:')
            ->line('# **' . $this->otpCode . '**')
            ->line("Kode OTP ini berlaku selama {$this->expiresInMinutes} menit.")
            ->line('Jika Anda tidak merasa melakukan permintaan login ini, segera amankan akun Anda.');
    }
}
