<?php

namespace App\Filament\Resources\DnsServers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DnsServerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Server Name')
                    ->placeholder('e.g. NS1 Primary')
                    ->required()
                    ->maxLength(255),

                TextInput::make('hostname')
                    ->label('Hostname (FQDN)')
                    ->placeholder('ns1.example.com')
                    ->required()
                    ->maxLength(255),

                TextInput::make('ip_address')
                    ->label('IP Address')
                    ->placeholder('103.10.10.53')
                    ->ip()
                    ->required(),

                Select::make('type')
                    ->label('Server Type')
                    ->options([
                        'authoritative' => 'Authoritative Server',
                        'recursive' => 'Recursive Resolver',
                        'both' => 'Authoritative & Recursive',
                    ])
                    ->default('authoritative')
                    ->required(),

                TextInput::make('port')
                    ->label('DNS Port')
                    ->numeric()
                    ->default(53)
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'warning' => 'Warning',
                    ])
                    ->default('offline')
                    ->required(),

                TextInput::make('api_url')
                    ->label('PowerDNS API URL')
                    ->placeholder('http://103.10.10.53:8081')
                    ->url()
                    ->helperText('Optional PowerDNS Webserver API URL'),

                TextInput::make('api_key')
                    ->label('PowerDNS API Key')
                    ->password()
                    ->revealable()
                    ->helperText('API key is masked and securely stored.'),

                Textarea::make('description')
                    ->label('Description / Notes')
                    ->placeholder('Location, data center, hardware specs...')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
