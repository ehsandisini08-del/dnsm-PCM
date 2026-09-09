<?php

namespace App\Filament\Resources\PdnsDomains\RelationManagers;

use App\Models\PdnsDomain;
use App\Models\PdnsRecord;
use App\Services\PowerDNSService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    protected static ?string $title = 'DNS Records';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Record Type')
                    ->options(array_combine(PowerDNSService::SUPPORTED_TYPES, PowerDNSService::SUPPORTED_TYPES))
                    ->default('A')
                    ->required()
                    ->live(),

                TextInput::make('name')
                    ->label('Host / Name')
                    ->placeholder('@ for root or subdomain like www')
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
                        'TXT' => 'SPF, DKIM, DMARC, or arbitrary text',
                        'SRV' => 'Format: <weight> <port> <target>',
                        'CAA' => 'Format: <flags> <tag> "<value>"',
                        default => '',
                    })
                    ->required()
                    ->rows(2),

                TextInput::make('ttl')
                    ->label('TTL (seconds)')
                    ->numeric()
                    ->default(3600)
                    ->required(),

                Toggle::make('disabled')
                    ->label('Disabled')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Host')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A', 'AAAA' => 'primary',
                        'CNAME' => 'info',
                        'MX' => 'warning',
                        'TXT' => 'gray',
                        'NS' => 'success',
                        'SOA' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('content')
                    ->label('Content / Value')
                    ->searchable()
                    ->limit(60)
                    ->copyable(),

                TextColumn::make('ttl')
                    ->label('TTL')
                    ->sortable(),

                TextColumn::make('prio')
                    ->label('Priority')
                    ->placeholder('—')
                    ->sortable(),

                IconColumn::make('disabled')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->state(fn (PdnsRecord $record): bool => ! $record->disabled),
            ])
            ->defaultSort('type', 'asc')
            ->filters([
                SelectFilter::make('type')
                    ->options(array_combine(PowerDNSService::SUPPORTED_TYPES, PowerDNSService::SUPPORTED_TYPES)),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data): Model {
                        /** @var PdnsDomain $domain */
                        $domain = $this->getOwnerRecord();

                        return app(PowerDNSService::class)->createRecord($domain, $data);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (PdnsRecord $record, array $data): Model {
                        return app(PowerDNSService::class)->updateRecord($record, $data);
                    }),

                DeleteAction::make()
                    ->hidden(fn (PdnsRecord $record): bool => $record->type === 'SOA')
                    ->using(function (PdnsRecord $record) {
                        return app(PowerDNSService::class)->deleteRecord($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
