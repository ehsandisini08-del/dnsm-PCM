<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Redirect user to Google OAuth consent screen.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(Request $request, OtpService $otpService, AuditLogService $auditService): RedirectResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect('/admin/login')->with('error', 'Gagal mengautentikasi dengan Google: ' . $e->getMessage());
        }

        $email = strtolower(trim($googleUser->getEmail()));
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        // 1. Existing User Flow
        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            if ($user->isRejected()) {
                return redirect('/admin/login')->with('error', 'Pendaftaran akun Anda telah ditolak oleh Administrator.');
            }

            // Case A: Email not verified yet
            if (! $user->email_verified_at) {
                $otpService->sendActivationOtp($user);
                session([
                    'auth_pending_user_id' => $user->id,
                    'auth_otp_type' => 'activation',
                ]);

                return redirect('/admin/verify-otp')->with('info', 'Masukkan kode OTP aktivasi yang dikirimkan ke Gmail Anda.');
            }

            // Case B: Verified, but awaiting admin approval
            if ($user->isPendingApproval() || ! $user->is_active) {
                session(['pending_approval_user_id' => $user->id]);

                return redirect('/admin/pending-approval');
            }

            // Case C: Approved & Active -> Send 2FA Login OTP
            $otpService->sendLoginOtp($user);
            session([
                'auth_pending_user_id' => $user->id,
                'auth_otp_type' => 'login',
            ]);

            return redirect('/admin/verify-otp')->with('info', 'Kode OTP keamanan telah dikirimkan ke email Anda.');
        }

        // 2. New Registration via Google Flow
        $newUser = User::create([
            'name' => $googleUser->getName() ?: 'Google User',
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'google_id' => $googleUser->getId(),
            'role' => 'operator',
            'approval_status' => 'pending',
            'is_active' => false,
            'email_verified_at' => null,
        ]);

        $auditService->log('REGISTER_GOOGLE', $newUser, null, [
            'email' => $newUser->email,
            'name' => $newUser->name,
            'google_id' => $newUser->google_id,
        ], $newUser->id);

        $otpService->sendActivationOtp($newUser);
        session([
            'auth_pending_user_id' => $newUser->id,
            'auth_otp_type' => 'activation',
        ]);

        return redirect('/admin/verify-otp')->with('success', 'Akun berhasil dibuat dengan Google! Silakan masukkan kode OTP aktivasi yang dikirim ke Gmail Anda.');
    }
}
