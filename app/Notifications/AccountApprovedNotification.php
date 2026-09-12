<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $assignedRole)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleName = match ($this->assignedRole) {
            'super_admin' => 'Super Administrator',
            'dns_admin' => 'DNS Administrator',
            'operator' => 'Operator NOC',
            'customer' => 'Customer',
            default => ucfirst($this->assignedRole),
        };

        return (new MailMessage)
            ->subject('[DNS Manager] Akun Anda Telah Disetujui')
            ->greeting('Selamat ' . $notifiable->name . '!')
            ->line('Pendaftaran akun Anda di DNS Manager Pro telah disetujui oleh Administrator.')
            ->line("Peran (Role) yang diberikan: **{$roleName}**.")
            ->action('Login ke Dashboard', url('/admin/login'))
            ->line('Anda sekarang dapat masuk ke dashboard DNS Manager menggunakan akun Anda.');
    }
}
