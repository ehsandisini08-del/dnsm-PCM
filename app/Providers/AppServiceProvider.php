<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationEvents;
use App\Models\Customer;
use App\Models\DnsServer;
use App\Models\PdnsDomain;
use App\Models\PdnsRecord;
use App\Models\User;
use App\Observers\CustomerObserver;
use App\Observers\DnsServerObserver;
use App\Observers\PdnsDomainObserver;
use App\Observers\PdnsRecordObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Model Observers for Audit Logging
        PdnsDomain::observe(PdnsDomainObserver::class);
        PdnsRecord::observe(PdnsRecordObserver::class);
        User::observe(UserObserver::class);
        Customer::observe(CustomerObserver::class);
        DnsServer::observe(DnsServerObserver::class);

        // Register Authentication Event Subscriber
        Event::subscribe(LogAuthenticationEvents::class);
    }
}
