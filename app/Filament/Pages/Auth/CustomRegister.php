<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\AuditLogService;
use App\Services\OtpService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class CustomRegister extends BaseRegister
{
    use WithRateLimiting;

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(3);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        $user = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'role' => 'operator',
            'approval_status' => 'pending',
            'is_active' => false,
            'email_verified_at' => null,
        ]);

        app(AuditLogService::class)->log('REGISTER_FORM', $user, null, [
            'name' => $user->name,
            'email' => $user->email,
        ], $user->id);

        // Send 6-digit Activation OTP via Email
        app(OtpService::class)->sendActivationOtp($user);

        session([
            'auth_pending_user_id' => $user->id,
            'auth_otp_type' => 'activation',
        ]);

        Notification::make()
            ->title('Pendaftaran Berhasil')
            ->body('Silakan masukkan 6-digit kode OTP aktivasi yang dikirimkan ke email Anda.')
            ->success()
            ->send();

        $this->redirect(url('/admin/verify-otp'));

        return null;
    }
}
