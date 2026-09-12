<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\OtpService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomLogin extends BaseLogin
{
    use WithRateLimiting;

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $email = strtolower(trim($data['email']));
        $password = $data['password'];
        $remember = (bool) ($data['remember'] ?? false);

        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        // 1. Check if user was rejected by Super Admin
        if ($user->isRejected()) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Pendaftaran akun Anda telah ditolak oleh Administrator.')
                ->danger()
                ->send();

            return null;
        }

        // 2. Check if email is not yet verified via OTP
        if (! $user->email_verified_at) {
            app(OtpService::class)->sendActivationOtp($user);
            session([
                'auth_pending_user_id' => $user->id,
                'auth_otp_type' => 'activation',
            ]);

            Notification::make()
                ->title('Verifikasi Email Diperlukan')
                ->body('Silakan masukkan kode OTP yang telah dikirimkan ke email Anda.')
                ->info()
                ->send();

            $this->redirect(url('/admin/verify-otp'));

            return null;
        }

        // 3. Check if user is awaiting Super Admin approval
        if ($user->isPendingApproval() || ! $user->is_active) {
            session(['pending_approval_user_id' => $user->id]);
            $this->redirect(url('/admin/pending-approval'));

            return null;
        }

        // 4. Approved & Active User -> Trigger 2FA Login OTP
        app(OtpService::class)->sendLoginOtp($user);
        session([
            'auth_pending_user_id' => $user->id,
            'auth_otp_type' => 'login',
            'auth_remember' => $remember,
        ]);

        Notification::make()
            ->title('Kode OTP Terkirim')
            ->body('Masukkan kode 6-digit OTP yang dikirimkan ke email Anda untuk menyelesaikan login.')
            ->success()
            ->send();

        $this->redirect(url('/admin/verify-otp'));

        return null;
    }
}
