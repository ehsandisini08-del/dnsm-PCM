<?php

namespace App\Filament\Resources\PdnsRecords\Schemas;

use App\Services\PowerDNSService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PdnsRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        $creatableTypes = array_diff(PowerDNSService::SUPPORTED_TYPES, ['SOA']);

        return $schema
            ->components([
                Select::make('domain_id')
                    ->label('DNS Zone')
                    ->relationship('domain', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (string $operation): bool => $operation === 'edit'),

                Select::make('type')
                    ->label('Record Type')
                    ->options(array_combine($creatableTypes, $creatableTypes))
                    ->default('A')
                    ->required()
                    ->live(),

                TextInput::make('name')
                    ->label('Name / Host')
                    ->placeholder('@ for root domain or subdomain like www')
                    ->helperText('Use @ for the apex/root domain.')
                    ->required(),

                TextInput::make('prio')
                    ->label('Priority')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(65535)
                    ->visible(fn ($get): bool => in_array($get('type'), ['MX', 'SRV']))
                    ->required(fn ($get): bool => in_array($get('type'), ['MX', 'SRV'])),

                Textarea::make('content')
                    ->label('Value / Content')
                    ->placeholder(fn ($get): string => match ($get('type')) {
                        'A' => '103.10.10.1',
                        'AAAA' => '2001:db8::1',
                        'CNAME' => 'target.example.com',
                        'MX' => 'mail.example.com',
                        'TXT' => 'v=spf1 include:_spf.example.com ~all',
                        'SRV' => '10 5060 sip.example.com',
                        'CAA' => '0 issue "letsencrypt.org"',
                        'PTR' => 'host.example.com',
                        default => 'Record content',
                    })
                    ->helperText(fn ($get): string => match ($get('type')) {
                        'A' => 'IPv4 address (e.g. 103.10.10.1)',
                        'AAAA' => 'IPv6 address (e.g. 2001:db8::1)',
                        'CNAME', 'NS', 'PTR' => 'Target hostname (e.g. host.example.com)',
                        'MX' => 'Mail server hostname',
                        'TXT' => 'SPF, DKIM, DMARC, or arbitrary text string',
                        'SRV' => 'Format: <weight> <port> <target> (e.g. 10 5060 sip.example.com)',
                        'CAA' => 'Format: <flags> <tag> "<value>" (e.g. 0 issue "letsencrypt.org")',
                        default => '',
                    })
                    ->required()
                    ->rows(2),

                TextInput::make('ttl')
                    ->label('TTL (Seconds)')
                    ->numeric()
                    ->default(3600)
                    ->required(),

                Toggle::make('disabled')
                    ->label('Disabled')
                    ->default(false),
            ]);
    }
}
