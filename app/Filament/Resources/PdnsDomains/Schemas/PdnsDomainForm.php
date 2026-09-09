<?php

namespace App\Filament\Resources\PdnsDomains\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PdnsDomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Zone Name')
                    ->placeholder('example.com')
                    ->required()
                    ->unique(table: 'domains', column: 'name', ignoreRecord: true)
                    ->regex('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i')
                    ->validationMessages([
                        'regex' => 'The zone name must be a valid domain format (e.g. example.com).',
                    ])
                    ->disabled(fn (string $operation): bool => $operation === 'edit'),

                Select::make('type')
                    ->label('Zone Type')
                    ->options([
                        'NATIVE' => 'NATIVE',
                        'MASTER' => 'MASTER',
                        'SLAVE' => 'SLAVE',
                    ])
                    ->default('NATIVE')
                    ->required()
                    ->live(),

                TextInput::make('master')
                    ->label('Master IP')
                    ->placeholder('103.10.10.1')
                    ->visible(fn ($get): bool => $get('type') === 'SLAVE')
                    ->required(fn ($get): bool => $get('type') === 'SLAVE'),

                Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('dns_server_id')
                    ->label('Primary DNS Server')
                    ->relationship('dnsServer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'disabled' => 'Disabled',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active')
                    ->required(),

                TagsInput::make('custom_ns')
                    ->label('Custom Nameservers')
                    ->placeholder('Add NS (e.g. ns1.example.com)')
                    ->helperText('Leave empty to use default system nameservers.')
                    ->visible(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
