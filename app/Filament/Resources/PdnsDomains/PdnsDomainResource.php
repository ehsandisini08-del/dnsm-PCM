<?php

namespace App\Filament\Resources\PdnsDomains;

use App\Filament\Resources\PdnsDomains\Pages\CreatePdnsDomain;
use App\Filament\Resources\PdnsDomains\Pages\EditPdnsDomain;
use App\Filament\Resources\PdnsDomains\Pages\ListPdnsDomains;
use App\Filament\Resources\PdnsDomains\Pages\ViewPdnsDomain;
use App\Filament\Resources\PdnsDomains\RelationManagers\RecordsRelationManager;
use App\Filament\Resources\PdnsDomains\Schemas\PdnsDomainForm;
use App\Filament\Resources\PdnsDomains\Schemas\PdnsDomainInfolist;
use App\Filament\Resources\PdnsDomains\Tables\PdnsDomainsTable;
use App\Models\PdnsDomain;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PdnsDomainResource extends Resource
{
    protected static ?string $model = PdnsDomain::class;

    protected static string|\UnitEnum|null $navigationGroup = 'DNS Management';

    protected static ?string $modelLabel = 'Zone';

    protected static ?string $pluralModelLabel = 'Zones';

    protected static ?string $navigationLabel = 'Zones';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    public static function getNavigationBadge(): ?string
    {
        return (string) PdnsDomain::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return PdnsDomainForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PdnsDomainInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PdnsDomainsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPdnsDomains::route('/'),
            'create' => CreatePdnsDomain::route('/create'),
            'view' => ViewPdnsDomain::route('/{record}'),
            'edit' => EditPdnsDomain::route('/{record}/edit'),
        ];
    }
}
