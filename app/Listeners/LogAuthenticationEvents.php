<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

class LogAuthenticationEvents
{
    public function __construct(protected AuditLogService $auditService) {}

    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->auditService->log(
                'LOGIN',
                $event->user,
                null,
                ['email' => $event->user->email, 'guard' => $event->guard],
                $event->user->id
            );
        }
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->auditService->log(
                'LOGOUT',
                $event->user,
                null,
                ['email' => $event->user->email, 'guard' => $event->guard],
                $event->user->id
            );
        }
    }

    public function handleFailed(Failed $event): void
    {
        $this->auditService->log(
            'LOGIN_FAILED',
            $event->user instanceof User ? $event->user : null,
            null,
            ['credentials' => array_keys($event->credentials), 'guard' => $event->guard],
            $event->user?->id
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
        ];
    }
}
