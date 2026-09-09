<?php

namespace App\Filament\Resources\DnsTemplates\Schemas;

use App\Services\PowerDNSService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DnsTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Template Name')
                    ->placeholder('e.g. Standard Web Hosting, Google Workspace...')
                    ->required()
                    ->maxLength(255),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Brief overview of what records this template creates...')
                    ->rows(2)
                    ->columnSpanFull(),

                Repeater::make('records')
                    ->relationship('records')
                    ->label('Template DNS Records')
                    ->helperText('You can use variable placeholders: {ip} for target IP, {domain} for the zone root domain.')
                    ->schema([
                        Select::make('type')
                            ->label('Type')
                            ->options(array_combine(PowerDNSService::SUPPORTED_TYPES, PowerDNSService::SUPPORTED_TYPES))
                            ->default('A')
                            ->required()
                            ->live(),

                        TextInput::make('name')
                            ->label('Host / Name')
                            ->placeholder('@ for root, or www, mail, etc.')
                            ->default('@')
                            ->required(),

                        TextInput::make('priority')
                            ->label('Priority')
                            ->numeric()
                            ->visible(fn ($get): bool => in_array($get('type'), ['MX', 'SRV']))
                            ->required(fn ($get): bool => in_array($get('type'), ['MX', 'SRV'])),

                        TextInput::make('value')
                            ->label('Content / Value')
                            ->placeholder('e.g. {ip}, mail.{domain}, 10 5060 sip.{domain}')
                            ->required(),

                        TextInput::make('ttl')
                            ->label('TTL')
                            ->numeric()
                            ->default(3600)
                            ->required(),
                    ])
                    ->columns(5)
                    ->columnSpanFull()
                    ->defaultItems(0)
                    ->reorderable()
                    ->collapsible(),
            ]);
    }
}
