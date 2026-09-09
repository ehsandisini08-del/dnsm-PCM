<?php

namespace App\Filament\Resources\PdnsRecords;

use App\Filament\Resources\PdnsRecords\Pages\CreatePdnsRecord;
use App\Filament\Resources\PdnsRecords\Pages\EditPdnsRecord;
use App\Filament\Resources\PdnsRecords\Pages\ListPdnsRecords;
use App\Filament\Resources\PdnsRecords\Schemas\PdnsRecordForm;
use App\Filament\Resources\PdnsRecords\Tables\PdnsRecordsTable;
use App\Models\PdnsRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PdnsRecordResource extends Resource
{
    protected static ?string $model = PdnsRecord::class;

    protected static string|\UnitEnum|null $navigationGroup = 'DNS Management';

    protected static ?string $modelLabel = 'Record';

    protected static ?string $pluralModelLabel = 'Records';

    protected static ?string $navigationLabel = 'Records';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    public static function getNavigationBadge(): ?string
    {
        return (string) PdnsRecord::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return PdnsRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PdnsRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPdnsRecords::route('/'),
            'create' => CreatePdnsRecord::route('/create'),
            'edit' => EditPdnsRecord::route('/{record}/edit'),
        ];
    }
}
