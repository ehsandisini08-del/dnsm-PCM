<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationOtpNotification extends Notification
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
            ->subject('[DNS Manager] Kode OTP Aktivasi Akun Anda')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Terima kasih telah mendaftar di DNS Manager Pro.')
            ->line('Berikut adalah kode OTP untuk memverifikasi dan mengaktifkan alamat email Anda:')
            ->line('# **' . $this->otpCode . '**')
            ->line("Kode OTP ini berlaku selama {$this->expiresInMinutes} menit.")
            ->line('Setelah email Anda terverifikasi, akun Anda akan ditinjau dan disetujui oleh Super Admin sebelum Anda dapat mengakses dashboard.')
            ->line('Jangan bagikan kode OTP ini kepada siapa pun.');
    }
}
