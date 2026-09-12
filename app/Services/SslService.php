<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class SslService
{
    /**
     * Get SSL certificate status for a domain.
     */
    public function getSslStatus(?string $domain = null): array
    {
        $domain = $domain ?: $this->getDefaultDomain();
        $isWindows = PHP_OS_FAMILY === 'Windows';

        $status = [
            'domain' => $domain,
            'is_installed' => false,
            'is_valid' => false,
            'is_expired' => false,
            'is_expiring_soon' => false,
            'issuer' => null,
            'subject' => null,
            'valid_from' => null,
            'valid_to' => null,
            'days_remaining' => null,
            'cert_path' => null,
            'certbot_available' => $this->isCertbotAvailable(),
            'nginx_available' => $this->isNginxAvailable(),
            'ssl_enabled_in_nginx' => false,
            'app_url' => config('app.url'),
            'is_https' => str_starts_with((string) config('app.url'), 'https://'),
        ];

        if ($isWindows) {
            return $status;
        }

        // Check Let's Encrypt live directory
        $certPath = "/etc/letsencrypt/live/{$domain}/fullchain.pem";

        if (! File::exists($certPath)) {
            // Check if any other domain certificate exists in /etc/letsencrypt/live/
            if (File::isDirectory('/etc/letsencrypt/live')) {
                $dirs = File::directories('/etc/letsencrypt/live');
                if (! empty($dirs)) {
                    $firstDir = basename($dirs[0]);
                    if ($firstDir !== 'README' && File::exists("/etc/letsencrypt/live/{$firstDir}/fullchain.pem")) {
                        $certPath = "/etc/letsencrypt/live/{$firstDir}/fullchain.pem";
                        $status['domain'] = $firstDir;
                    }
                }
            }
        }

        if (File::exists($certPath)) {
            $status['is_installed'] = true;
            $status['cert_path'] = $certPath;

            try {
                $certContent = File::get($certPath);
                $certInfo = openssl_x509_parse($certContent);

                if ($certInfo) {
                    $validFrom = $certInfo['validFrom_time_t'] ?? null;
                    $validTo = $certInfo['validTo_time_t'] ?? null;
                    $now = time();

                    $status['issuer'] = $certInfo['issuer']['O'] ?? ($certInfo['issuer']['CN'] ?? "Let's Encrypt");
                    $status['subject'] = $certInfo['subject']['CN'] ?? $domain;
                    $status['valid_from'] = $validFrom ? date('Y-m-d H:i:s', $validFrom) : null;
                    $status['valid_to'] = $validTo ? date('Y-m-d H:i:s', $validTo) : null;

                    if ($validTo) {
                        $daysRemaining = (int) floor(($validTo - $now) / 86400);
                        $status['days_remaining'] = $daysRemaining;
                        $status['is_expired'] = $daysRemaining <= 0;
                        $status['is_valid'] = $daysRemaining > 0 && ($validFrom ? $now >= $validFrom : true);
                        $status['is_expiring_soon'] = $daysRemaining > 0 && $daysRemaining <= 30;
                    }
                }
            } catch (Throwable $e) {
                Log::warning("Failed to parse SSL certificate: {$e->getMessage()}");
            }
        }

        // Check Nginx site configuration
        $nginxSite = '/etc/nginx/sites-available/dnsmanager';
        if (File::exists($nginxSite)) {
            $nginxContent = File::get($nginxSite);
            $status['ssl_enabled_in_nginx'] = str_contains($nginxContent, 'listen 443') || str_contains($nginxContent, 'ssl_certificate');
        }

        return $status;
    }

    /**
     * Request and install Let's Encrypt SSL certificate via Certbot.
     */
    public function issueCertificate(string $domain, string $email, bool $forceRedirect = true): array
    {
        $domain = trim($domain);
        $email = trim($email);

        if (empty($domain)) {
            return [
                'success' => false,
                'message' => 'Nama domain wajib diisi.',
                'output' => '',
            ];
        }

        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Alamat email administrator tidak valid.',
                'output' => '',
            ];
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return [
                'success' => false,
                'message' => 'Fitur Certbot hanya dapat dijalankan di server Linux (Ubuntu/Debian).',
                'output' => 'Windows OS detected. Certbot is not supported in this local environment.',
            ];
        }

        $redirectFlag = $forceRedirect ? '--redirect' : '--no-redirect';
        $cmd = "certbot --nginx -d {$domain} -m {$email} --agree-tos --non-interactive {$redirectFlag}";

        try {
            $result = Process::timeout(180)->run($cmd);
            $output = $result->output()."\n".$result->errorOutput();

            if ($result->successful()) {
                // Update APP_URL in .env if needed
                $this->updateAppUrlToHttps($domain);

                // Reload Nginx
                Process::run('systemctl reload nginx');

                return [
                    'success' => true,
                    'message' => "Sertifikat SSL Let's Encrypt untuk [{$domain}] berhasil dipasang!",
                    'output' => trim($output),
                ];
            }

            return [
                'success' => false,
                'message' => "Gagal memasang sertifikat SSL untuk [{$domain}]. Periksa output log Certbot.",
                'output' => trim($output),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengeksekusi Certbot: '.$e->getMessage(),
                'output' => $e->getTraceAsString(),
            ];
        }
    }

    /**
     * Renew SSL certificates using Certbot.
     */
    public function renewCertificate(bool $dryRun = false): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
                'success' => false,
                'message' => 'Fitur Certbot hanya dapat dijalankan di server Linux (Ubuntu/Debian).',
                'output' => 'Windows OS detected. Certbot is not supported in this local environment.',
            ];
        }

        $dryRunFlag = $dryRun ? '--dry-run' : '';
        $cmd = trim("certbot renew {$dryRunFlag} --non-interactive");

        try {
            $result = Process::timeout(180)->run($cmd);
            $output = $result->output()."\n".$result->errorOutput();

            if ($result->successful()) {
                if (! $dryRun) {
                    Process::run('systemctl reload nginx');
                }

                $msg = $dryRun
                    ? 'Simulasi perpanjangan SSL (Dry Run) berhasil dijalankan tanpa kendala.'
                    : 'Pembaruan sertifikat SSL (Renew) berhasil dijalankan!';

                return [
                    'success' => true,
                    'message' => $msg,
                    'output' => trim($output),
                ];
            }

            return [
                'success' => false,
                'message' => 'Pembaruan sertifikat SSL gagal atau tidak ada sertifikat yang memerlukan renew.',
                'output' => trim($output),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengeksekusi Certbot Renew: '.$e->getMessage(),
                'output' => $e->getTraceAsString(),
            ];
        }
    }

    /**
     * Test Nginx configuration.
     */
    public function testNginxConfig(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
                'success' => true,
                'output' => 'Nginx testing skipped on Windows development environment.',
            ];
        }

        try {
            $result = Process::run('nginx -t');
            $output = $result->output()."\n".$result->errorOutput();

            return [
                'success' => $result->successful(),
                'output' => trim($output),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if Certbot command is available on the system.
     */
    public function isCertbotAvailable(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return false;
        }

        $result = Process::run('which certbot');

        return $result->successful();
    }

    /**
     * Check if Nginx is installed.
     */
    public function isNginxAvailable(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return false;
        }

        $result = Process::run('which nginx');

        return $result->successful();
    }

    /**
     * Get default domain from config or request.
     */
    public function getDefaultDomain(): string
    {
        $appUrl = config('app.url', 'localhost');
        $host = parse_url($appUrl, PHP_URL_HOST);

        if ($host && $host !== 'localhost' && ! filter_var($host, FILTER_VALIDATE_IP)) {
            return $host;
        }

        if (request()->hasHeader('host')) {
            $requestHost = request()->getHost();
            if ($requestHost && $requestHost !== 'localhost' && ! filter_var($requestHost, FILTER_VALIDATE_IP)) {
                return $requestHost;
            }
        }

        return $host ?: 'saidnetlab.my.id';
    }

    /**
     * Update APP_URL in .env to use https://.
     */
    public function updateAppUrlToHttps(string $domain): bool
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return false;
        }

        try {
            $content = File::get($envPath);
            $newUrl = "https://{$domain}";

            if (preg_match('/^APP_URL=.*$/m', $content)) {
                $content = preg_replace('/^APP_URL=.*$/m', "APP_URL={$newUrl}", $content);
            } else {
                $content .= "\nAPP_URL={$newUrl}\n";
            }

            File::put($envPath, $content);

            return true;
        } catch (Throwable $e) {
            Log::warning("Failed to update APP_URL in .env: {$e->getMessage()}");

            return false;
        }
    }
}
