<?php

namespace App\Filament\Pages;

use App\Models\PdnsDomain;
use App\Services\PowerDNSService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DnsTools extends Page
{
    protected string $view = 'filament.pages.dns-tools';

    protected static string|\UnitEnum|null $navigationGroup = 'DNS Management';

    protected static ?string $navigationLabel = 'DNS Tools';

    protected static ?string $title = 'DNS Diagnostics & Lookup Tools';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public string $activeTab = 'lookup'; // 'lookup' or 'propagation'

    // Lookup fields
    public string $lookupDomain = '';

    public string $lookupType = 'A';

    public string $lookupServer = 'system'; // 'system', 'local', '8.8.8.8', '1.1.1.1', 'custom'

    public ?string $customServerIp = '';

    public ?array $lookupResults = null;

    public bool $isLookingUp = false;

    // Propagation fields
    public string $propDomain = '';

    public string $propType = 'A';

    public ?array $propResults = null;

    public bool $isCheckingProp = false;

    public function getExistingZonesProperty()
    {
        return PdnsDomain::orderBy('name')->pluck('name')->toArray();
    }

    public function selectDomainForLookup(string $domain): void
    {
        $this->lookupDomain = $domain;
    }

    public function selectDomainForProp(string $domain): void
    {
        $this->propDomain = $domain;
    }

    public function runLookup(): void
    {
        $this->validate([
            'lookupDomain' => ['required', 'string', 'min:2'],
            'lookupType' => ['required', 'string'],
        ]);

        $this->isLookingUp = true;

        $targetServer = match ($this->lookupServer) {
            'local' => '127.0.0.1',
            'google' => '8.8.8.8',
            'cloudflare' => '1.1.1.1',
            'custom' => $this->customServerIp ?: null,
            default => null,
        };

        try {
            $service = app(PowerDNSService::class);
            $this->lookupResults = $service->lookupDns(
                $this->lookupDomain,
                $this->lookupType,
                $targetServer
            );

            Notification::make()
                ->title('DNS Query Completed')
                ->body("Found {$this->lookupResults['count']} record(s) in {$this->lookupResults['latency_ms']} ms.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('DNS Lookup Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLookingUp = false;
        }
    }

    public function runPropagation(): void
    {
        $this->validate([
            'propDomain' => ['required', 'string', 'min:2'],
            'propType' => ['required', 'string'],
        ]);

        $this->isCheckingProp = true;

        try {
            $service = app(PowerDNSService::class);
            $this->propResults = $service->checkPropagation($this->propDomain, $this->propType);

            Notification::make()
                ->title('Propagation Checked')
                ->body('Queried 5 global Anycast DNS resolvers.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Propagation Check Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isCheckingProp = false;
        }
    }
}
