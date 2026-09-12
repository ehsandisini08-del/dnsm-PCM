<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\OtpService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class VerifyOtp extends SimplePage
{
    use WithRateLimiting;

    protected string $view = 'filament.pages.auth.verify-otp';

    public string $otp = '';

    public ?string $userEmail = '';

    public string $otpType = 'login';

    public ?int $pendingUserId = null;

    public function getTitle(): string|Htmlable
    {
        return $this->otpType === 'activation'
            ? 'Aktivasi Akun via OTP Email'
            : 'Verifikasi Keamanan (Two-Factor OTP)';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->otpType === 'activation'
            ? 'Aktivasi Email Anda'
            : 'Masukkan Kode Keamanan OTP';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return "Kode 6-digit OTP telah dikirimkan ke {$this->userEmail}. Masukkan kode sebelum kedaluwarsa (5 menit).";
    }

    public function mount(): void
    {
        $userId = session('auth_pending_user_id');

        if (! $userId) {
            $this->redirect(url('/admin/login'));

            return;
        }

        $user = User::find($userId);

        if (! $user) {
            $this->redirect(url('/admin/login'));

            return;
        }

        $this->pendingUserId = $user->id;
        $this->userEmail = $user->email;
        $this->otpType = session('auth_otp_type', 'login');
    }

    public function verify(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Terlalu Banyak Percobaan')
                ->body('Silakan tunggu beberapa saat sebelum mencoba kembali.')
                ->danger()
                ->send();

            return;
        }

        $this->validate([
            'otp' => ['required', 'string', 'size:6'],
        ], [
            'otp.required' => 'Kode OTP 6-digit wajib diisi.',
            'otp.size' => 'Kode OTP harus tepat 6 digit angka.',
        ]);

        $user = User::find($this->pendingUserId);

        if (! $user) {
            $this->redirect(url('/admin/login'));

            return;
        }

        $otpService = app(OtpService::class);

        // 1. Activation Flow
        if ($this->otpType === 'activation') {
            if ($otpService->verifyActivationOtp($user, $this->otp)) {
                session()->forget(['auth_pending_user_id', 'auth_otp_type']);
                session(['pending_approval_user_id' => $user->id]);

                Notification::make()
                    ->title('Email Berhasil Terverifikasi')
                    ->body('Akun Anda sekarang menunggu persetujuan dari Super Administrator.')
                    ->success()
                    ->send();

                $this->redirect(url('/admin/pending-approval'));

                return;
            }

            Notification::make()
                ->title('Kode OTP Tidak Valid')
                ->body('Kode OTP aktivasi salah atau telah kedaluwarsa.')
                ->danger()
                ->send();

            return;
        }

        // 2. Login Flow (2FA)
        if ($otpService->verifyLoginOtp($user, $this->otp)) {
            $remember = (bool) session('auth_remember', false);
            session()->forget(['auth_pending_user_id', 'auth_otp_type', 'auth_remember']);

            Auth::login($user, $remember);

            Notification::make()
                ->title('Login Berhasil')
                ->body('Selamat datang kembali di DNS Manager Pro!')
                ->success()
                ->send();

            $this->redirect(url('/admin'));

            return;
        }

        Notification::make()
            ->title('Kode OTP Salah')
            ->body('Kode OTP login yang Anda masukkan salah atau telah kedaluwarsa.')
            ->danger()
            ->send();
    }

    public function resend(): void
    {
        try {
            $this->rateLimit(1, 60);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Mohon Tunggu')
                ->body('Permintaan kirim ulang OTP hanya dapat dilakukan 1 kali per menit.')
                ->warning()
                ->send();

            return;
        }

        $user = User::find($this->pendingUserId);

        if (! $user) {
            $this->redirect(url('/admin/login'));

            return;
        }

        $otpService = app(OtpService::class);

        if ($this->otpType === 'activation') {
            $otpService->sendActivationOtp($user);
        } else {
            $otpService->sendLoginOtp($user);
        }

        Notification::make()
            ->title('Kode OTP Baru Terkirim')
            ->body("Kode OTP baru telah dikirimkan ke {$user->email}.")
            ->success()
            ->send();
    }

    public function cancel(): void
    {
        session()->forget(['auth_pending_user_id', 'auth_otp_type', 'auth_remember']);
        $this->redirect(url('/admin/login'));
    }
}
