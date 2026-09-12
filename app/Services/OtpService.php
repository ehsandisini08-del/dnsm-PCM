<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountActivationOtpNotification;
use App\Notifications\LoginOtpNotification;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class OtpService
{
    /**
     * Default OTP validity duration in minutes.
     */
    public const OTP_EXPIRY_MINUTES = 5;

    /**
     * Generate and send an OTP for new account email activation.
     */
    public function sendActivationOtp(User $user): string
    {
        $code = $this->generateCode();

        $user->forceFill([
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ])->save();

        try {
            $user->notify(new AccountActivationOtpNotification($code, self::OTP_EXPIRY_MINUTES));
        } catch (Throwable $e) {
            Log::error("Failed to send activation OTP email to {$user->email}: " . $e->getMessage());
        }

        app(AuditLogService::class)->log('SEND_ACTIVATION_OTP', $user, null, [
            'email' => $user->email,
            'expires_at' => $user->otp_expires_at->toIso8601String(),
        ], $user->id);

        return $code;
    }

    /**
     * Generate and send an OTP for two-factor login verification.
     */
    public function sendLoginOtp(User $user): string
    {
        $code = $this->generateCode();

        $user->forceFill([
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ])->save();

        try {
            $user->notify(new LoginOtpNotification($code, self::OTP_EXPIRY_MINUTES));
        } catch (Throwable $e) {
            Log::error("Failed to send login OTP email to {$user->email}: " . $e->getMessage());
        }

        app(AuditLogService::class)->log('SEND_LOGIN_OTP', $user, null, [
            'email' => $user->email,
            'expires_at' => $user->otp_expires_at->toIso8601String(),
        ], $user->id);

        return $code;
    }

    /**
     * Verify activation OTP and mark user email as verified.
     */
    public function verifyActivationOtp(User $user, string $code): bool
    {
        if (! $this->isOtpValid($user, $code)) {
            return false;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'approval_status' => $user->approval_status ?? 'pending',
        ])->save();

        app(AuditLogService::class)->log('VERIFY_ACTIVATION_OTP', $user, null, [
            'email' => $user->email,
            'verified_at' => now()->toIso8601String(),
        ], $user->id);

        return true;
    }

    /**
     * Verify login OTP for 2FA.
     */
    public function verifyLoginOtp(User $user, string $code): bool
    {
        if (! $this->isOtpValid($user, $code)) {
            return false;
        }

        $user->forceFill([
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();

        session(['otp_verified_user_id' => $user->id, 'otp_verified_at' => now()->timestamp]);

        app(AuditLogService::class)->log('VERIFY_LOGIN_OTP', $user, null, [
            'email' => $user->email,
            'authenticated_at' => now()->toIso8601String(),
        ], $user->id);

        return true;
    }

    /**
     * Validate OTP matching and expiration.
     */
    public function isOtpValid(User $user, string $code): bool
    {
        $trimmed = trim($code);

        if (empty($trimmed) || empty($user->otp_code) || empty($user->otp_expires_at)) {
            return false;
        }

        if ($user->otp_expires_at->isPast()) {
            return false;
        }

        return Hash::check($trimmed, $user->otp_code);
    }

    /**
     * Generate 6-digit random numeric code.
     */
    protected function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
