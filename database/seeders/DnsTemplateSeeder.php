<?php

namespace Database\Seeders;

use App\Models\DnsTemplate;
use App\Models\DnsTemplateRecord;
use Illuminate\Database\Seeder;

class DnsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Standard Web Hosting Template
        $web = DnsTemplate::updateOrCreate(
            ['name' => 'Standard Web Hosting'],
            ['description' => 'Apex A record, www CNAME, and ftp A record.', 'is_active' => true]
        );

        $web->records()->delete();
        DnsTemplateRecord::create(['dns_template_id' => $web->id, 'name' => '@', 'type' => 'A', 'value' => '{ip}', 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $web->id, 'name' => 'www', 'type' => 'CNAME', 'value' => '{domain}.', 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $web->id, 'name' => 'ftp', 'type' => 'A', 'value' => '{ip}', 'ttl' => 3600]);

        // 2. Google Workspace Mail Template
        $gsuite = DnsTemplate::updateOrCreate(
            ['name' => 'Google Workspace (GSuite)'],
            ['description' => 'Google Workspace 5 MX records and Google SPF TXT record.', 'is_active' => true]
        );

        $gsuite->records()->delete();
        DnsTemplateRecord::create(['dns_template_id' => $gsuite->id, 'name' => '@', 'type' => 'MX', 'value' => 'aspmx.l.google.com.', 'priority' => 1, 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $gsuite->id, 'name' => '@', 'type' => 'MX', 'value' => 'alt1.aspmx.l.google.com.', 'priority' => 5, 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $gsuite->id, 'name' => '@', 'type' => 'MX', 'value' => 'alt2.aspmx.l.google.com.', 'priority' => 5, 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $gsuite->id, 'name' => '@', 'type' => 'MX', 'value' => 'alt3.aspmx.l.google.com.', 'priority' => 10, 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $gsuite->id, 'name' => '@', 'type' => 'MX', 'value' => 'alt4.aspmx.l.google.com.', 'priority' => 10, 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $gsuite->id, 'name' => '@', 'type' => 'TXT', 'value' => 'v=spf1 include:_spf.google.com ~all', 'ttl' => 3600]);

        // 3. Microsoft 365 / Office 365 Template
        $m365 = DnsTemplate::updateOrCreate(
            ['name' => 'Microsoft 365 (Office 365)'],
            ['description' => 'Microsoft 365 autodiscover CNAME, MX, and SPF TXT record.', 'is_active' => true]
        );

        $m365->records()->delete();
        DnsTemplateRecord::create(['dns_template_id' => $m365->id, 'name' => 'autodiscover', 'type' => 'CNAME', 'value' => 'autodiscover.outlook.com.', 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $m365->id, 'name' => '@', 'type' => 'TXT', 'value' => 'v=spf1 include:spf.protection.outlook.com ~all', 'ttl' => 3600]);

        // 4. Custom ISP Mail Server
        $ispMail = DnsTemplate::updateOrCreate(
            ['name' => 'ISP Local Mail Server'],
            ['description' => 'Local mail A record, @ MX record with priority 10, and local SPF.', 'is_active' => true]
        );

        $ispMail->records()->delete();
        DnsTemplateRecord::create(['dns_template_id' => $ispMail->id, 'name' => 'mail', 'type' => 'A', 'value' => '{ip}', 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $ispMail->id, 'name' => '@', 'type' => 'MX', 'value' => 'mail.{domain}.', 'priority' => 10, 'ttl' => 3600]);
        DnsTemplateRecord::create(['dns_template_id' => $ispMail->id, 'name' => '@', 'type' => 'TXT', 'value' => 'v=spf1 mx ~all', 'ttl' => 3600]);
    }
}
