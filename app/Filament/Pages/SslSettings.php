<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\SslService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SslSettings extends Page
{
    protected string $view = 'filament.pages.ssl-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'SSL Certificate (Certbot)';

    protected static ?string $title = 'SSL / TLS Certificate Management';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public string $domain = '';

    public string $email = '';

    public bool $forceRedirect = true;

    public bool $isProcessing = false;

    public ?string $outputLog = null;

    public ?string $outputStatus = null; // 'success' | 'danger' | 'info'

    public array $sslStatus = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }

    public function mount(): void
    {
        $sslService = app(SslService::class);
        $this->domain = $sslService->getDefaultDomain();
        $this->email = Auth::user()?->email ?? 'admin@example.com';
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        $sslService = app(SslService::class);
        $this->sslStatus = $sslService->getSslStatus($this->domain ?: null);
    }

    public function requestSsl(): void
    {
        $this->validate([
            'domain' => ['required', 'string', 'min:3'],
            'email' => ['required', 'email'],
        ], [
            'domain.required' => 'Nama domain wajib diisi.',
            'email.required' => 'Email administrator wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $this->isProcessing = true;
        $this->outputLog = null;

        try {
            $sslService = app(SslService::class);
            $result = $sslService->issueCertificate($this->domain, $this->email, $this->forceRedirect);

            $this->outputLog = $result['output'];
            $this->outputStatus = $result['success'] ? 'success' : 'danger';

            if ($result['success']) {
                Notification::make()
                    ->title('SSL Berhasil Dipasang')
                    ->body($result['message'])
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Pemasangan SSL Gagal')
                    ->body($result['message'])
                    ->danger()
                    ->send();
            }

            $this->refreshStatus();
        } catch (Throwable $e) {
            $this->outputLog = $e->getMessage();
            $this->outputStatus = 'danger';

            Notification::make()
                ->title('Terjadi Kesalahan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function renewSsl(bool $dryRun = false): void
    {
        $this->isProcessing = true;
        $this->outputLog = null;

        try {
            $sslService = app(SslService::class);
            $result = $sslService->renewCertificate($dryRun);

            $this->outputLog = $result['output'];
            $this->outputStatus = $result['success'] ? 'success' : 'danger';

            if ($result['success']) {
                Notification::make()
                    ->title($dryRun ? 'Dry Run Berhasil' : 'SSL Berhasil Diperbarui')
                    ->body($result['message'])
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Pembaruan SSL Gagal')
                    ->body($result['message'])
                    ->danger()
                    ->send();
            }

            $this->refreshStatus();
        } catch (Throwable $e) {
            $this->outputLog = $e->getMessage();
            $this->outputStatus = 'danger';

            Notification::make()
                ->title('Gagal Menjalankan Renew')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function testNginx(): void
    {
        $this->isProcessing = true;

        try {
            $sslService = app(SslService::class);
            $result = $sslService->testNginxConfig();

            $this->outputLog = $result['output'];
            $this->outputStatus = $result['success'] ? 'success' : 'danger';

            Notification::make()
                ->title($result['success'] ? 'Konfigurasi Nginx Valid' : 'Konfigurasi Nginx Bermasalah')
                ->body($result['success'] ? 'Sintaks konfigurasi Nginx aman.' : 'Ditemukan kesalahan pada file Nginx.')
                ->color($result['success'] ? 'success' : 'danger')
                ->send();
        } catch (Throwable $e) {
            $this->outputLog = $e->getMessage();
            $this->outputStatus = 'danger';
        } finally {
            $this->isProcessing = false;
        }
    }

    public function clearLog(): void
    {
        $this->outputLog = null;
        $this->outputStatus = null;
    }
}
