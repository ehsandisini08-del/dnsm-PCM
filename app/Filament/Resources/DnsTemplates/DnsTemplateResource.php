<?php

namespace App\Filament\Resources\DnsTemplates;

use App\Filament\Resources\DnsTemplates\Pages\CreateDnsTemplate;
use App\Filament\Resources\DnsTemplates\Pages\EditDnsTemplate;
use App\Filament\Resources\DnsTemplates\Pages\ListDnsTemplates;
use App\Filament\Resources\DnsTemplates\Pages\ViewDnsTemplate;
use App\Filament\Resources\DnsTemplates\Schemas\DnsTemplateForm;
use App\Filament\Resources\DnsTemplates\Schemas\DnsTemplateInfolist;
use App\Filament\Resources\DnsTemplates\Tables\DnsTemplatesTable;
use App\Models\DnsTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DnsTemplateResource extends Resource
{
    protected static ?string $model = DnsTemplate::class;

    protected static string|\UnitEnum|null $navigationGroup = 'DNS Management';

    protected static ?string $modelLabel = 'DNS Template';

    protected static ?string $pluralModelLabel = 'DNS Templates';

    protected static ?string $navigationLabel = 'Templates';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    public static function getNavigationBadge(): ?string
    {
        return (string) DnsTemplate::count();
    }

    public static function form(Schema $schema): Schema
    {
        return DnsTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DnsTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DnsTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDnsTemplates::route('/'),
            'create' => CreateDnsTemplate::route('/create'),
            'view' => ViewDnsTemplate::route('/{record}'),
            'edit' => EditDnsTemplate::route('/{record}/edit'),
        ];
    }
}
