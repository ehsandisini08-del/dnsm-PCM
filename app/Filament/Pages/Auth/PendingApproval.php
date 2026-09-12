<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;

class PendingApproval extends SimplePage
{
    protected string $view = 'filament.pages.auth.pending-approval';

    public ?string $userName = '';

    public ?string $userEmail = '';

    public function getTitle(): string|Htmlable
    {
        return 'Pendaftaran Menunggu Persetujuan';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Akun Sedang Ditinjau';
    }

    public function mount(): void
    {
        $userId = session('pending_approval_user_id');

        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $this->userName = $user->name;
                $this->userEmail = $user->email;
            }
        }
    }

    public function backToLogin(): void
    {
        session()->forget(['pending_approval_user_id', 'auth_pending_user_id', 'auth_otp_type']);
        $this->redirect(url('/admin/login'));
    }
}
