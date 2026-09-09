<?php

namespace App\Filament\Resources\DnsServers;

use App\Filament\Resources\DnsServers\Pages\CreateDnsServer;
use App\Filament\Resources\DnsServers\Pages\EditDnsServer;
use App\Filament\Resources\DnsServers\Pages\ListDnsServers;
use App\Filament\Resources\DnsServers\Pages\ViewDnsServer;
use App\Filament\Resources\DnsServers\RelationManagers\ZonesRelationManager;
use App\Filament\Resources\DnsServers\Schemas\DnsServerForm;
use App\Filament\Resources\DnsServers\Schemas\DnsServerInfolist;
use App\Filament\Resources\DnsServers\Tables\DnsServersTable;
use App\Models\DnsServer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DnsServerResource extends Resource
{
    protected static ?string $model = DnsServer::class;

    protected static string|\UnitEnum|null $navigationGroup = 'DNS Management';

    protected static ?string $modelLabel = 'DNS Server';

    protected static ?string $pluralModelLabel = 'DNS Servers';

    protected static ?string $navigationLabel = 'DNS Servers';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    public static function getNavigationBadge(): ?string
    {
        $count = DnsServer::count();
        $offline = DnsServer::where('status', 'offline')->count();

        return $offline > 0 ? "{$count} ({$offline} off)" : (string) $count;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return DnsServer::where('status', 'offline')->exists() ? 'danger' : 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return DnsServerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DnsServerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DnsServersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ZonesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDnsServers::route('/'),
            'create' => CreateDnsServer::route('/create'),
            'view' => ViewDnsServer::route('/{record}'),
            'edit' => EditDnsServer::route('/{record}/edit'),
        ];
    }
}
