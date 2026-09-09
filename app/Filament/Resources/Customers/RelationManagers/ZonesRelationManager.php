<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\Customer;
use App\Models\PdnsDomain;
use App\Services\PowerDNSService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ZonesRelationManager extends RelationManager
{
    protected static string $relationship = 'zones';

    protected static ?string $title = 'Assigned DNS Zones';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Zone Name')
                    ->placeholder('example.com')
                    ->required()
                    ->regex('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i')
                    ->disabled(fn (string $operation): bool => $operation === 'edit'),

                Select::make('type')
                    ->label('Zone Type')
                    ->options([
                        'NATIVE' => 'NATIVE',
                        'MASTER' => 'MASTER',
                        'SLAVE' => 'SLAVE',
                    ])
                    ->default('NATIVE')
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'disabled' => 'Disabled',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Zone Name'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('sync_status')
                    ->badge(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Zone Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('records_count')
                    ->counts('records')
                    ->label('Records')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'disabled' => 'gray',
                        'suspended' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('sync_status')
                    ->label('Sync')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'synced' => 'success',
                        'pending' => 'warning',
                        'error' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data): Model {
                        /** @var Customer $customer */
                        $customer = $this->getOwnerRecord();
                        $data['customer_id'] = $customer->id;

                        return app(PowerDNSService::class)->createZone($data);
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->using(function (PdnsDomain $record, array $data): Model {
                        return app(PowerDNSService::class)->updateZone($record, $data);
                    }),
                DeleteAction::make()
                    ->using(function (PdnsDomain $record) {
                        return app(PowerDNSService::class)->deleteZone($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $service = app(PowerDNSService::class);
                            foreach ($records as $record) {
                                $service->deleteZone($record);
                            }
                        }),
                ]),
            ]);
    }
}
