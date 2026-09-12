<?php

namespace App\Console\Commands;

use App\Services\SslService;
use Illuminate\Console\Command;

class SslSetupCommand extends Command
{
    protected $signature = 'app:ssl-setup
                            {--domain= : Target domain or hostname for SSL certificate}
                            {--email= : Administrator email for Let\'s Encrypt notifications}
                            {--renew : Renew existing SSL certificates}
                            {--dry-run : Perform a dry run test for SSL renewal}
                            {--status : Show current SSL certificate status}
                            {--no-redirect : Do not force HTTP to HTTPS redirect}';

    protected $description = 'Setup, renew, or check SSL/TLS certificates using Certbot and Let\'s Encrypt';

    public function handle(SslService $sslService): int
    {
        $this->info('╔═══════════════════════════════════════════════════╗');
        $this->info('║   SSL Certificate Management (Certbot)           ║');
        $this->info('╚═══════════════════════════════════════════════════╝');
        $this->newLine();

        if ($this->option('status')) {
            return $this->showStatus($sslService);
        }

        if ($this->option('renew')) {
            return $this->handleRenewal($sslService);
        }

        return $this->handleIssue($sslService);
    }

    protected function showStatus(SslService $sslService): int
    {
        $domain = $this->option('domain') ?: $sslService->getDefaultDomain();
        $status = $sslService->getSslStatus($domain);

        $this->info("Checking SSL status for [{$status['domain']}]...");
        $this->newLine();

        $this->table(
            ['Property', 'Value'],
            [
                ['Domain', $status['domain']],
                ['Installed', $status['is_installed'] ? 'Yes' : 'No'],
                ['Valid', $status['is_valid'] ? 'Yes' : 'No'],
                ['Issuer', $status['issuer'] ?? 'N/A'],
                ['Valid From', $status['valid_from'] ?? 'N/A'],
                ['Valid Until', $status['valid_to'] ?? 'N/A'],
                ['Days Remaining', isset($status['days_remaining']) ? "{$status['days_remaining']} days" : 'N/A'],
                ['HTTPS Active', $status['is_https'] ? 'Yes' : 'No'],
                ['Certbot Tool', $status['certbot_available'] ? 'Available' : 'Not Found'],
                ['Nginx Tool', $status['nginx_available'] ? 'Available' : 'Not Found'],
            ]
        );

        return self::SUCCESS;
    }

    protected function handleRenewal(SslService $sslService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'Running Certbot Renew (Dry Run mode)...' : 'Running Certbot Renew...');

        $result = $sslService->renewCertificate($dryRun);

        if ($result['success']) {
            $this->info("✓ {$result['message']}");
            if (! empty($result['output'])) {
                $this->line($result['output']);
            }

            return self::SUCCESS;
        }

        $this->error("✗ {$result['message']}");
        if (! empty($result['output'])) {
            $this->line($result['output']);
        }

        return self::FAILURE;
    }

    protected function handleIssue(SslService $sslService): int
    {
        $domain = $this->option('domain') ?: $this->ask('Enter domain name for SSL', $sslService->getDefaultDomain());
        $email = $this->option('email') ?: $this->ask('Enter administrator email for Let\'s Encrypt', 'admin@example.com');
        $forceRedirect = ! $this->option('no-redirect');

        $this->info("Requesting Let's Encrypt SSL certificate for [{$domain}]...");
        $result = $sslService->issueCertificate($domain, $email, $forceRedirect);

        if ($result['success']) {
            $this->info("✓ {$result['message']}");
            if (! empty($result['output'])) {
                $this->line($result['output']);
            }

            return self::SUCCESS;
        }

        $this->error("✗ {$result['message']}");
        if (! empty($result['output'])) {
            $this->line($result['output']);
        }

        return self::FAILURE;
    }
}
