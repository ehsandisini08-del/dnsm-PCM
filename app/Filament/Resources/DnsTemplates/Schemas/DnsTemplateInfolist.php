<?php

namespace App\Filament\Resources\DnsTemplates\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DnsTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Template Name')
                    ->weight('bold'),

                IconEntry::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextEntry::make('description')
                    ->label('Description')
                    ->placeholder('—')
                    ->columnSpanFull(),

                RepeatableEntry::make('records')
                    ->label('Defined Template Records')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Host'),
                        TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'A', 'AAAA' => 'primary',
                                'CNAME' => 'info',
                                'MX' => 'warning',
                                'TXT' => 'gray',
                                'NS' => 'success',
                                'SOA' => 'danger',
                                default => 'secondary',
                            }),
                        TextEntry::make('value')
                            ->label('Value / Pattern'),
                        TextEntry::make('priority')
                            ->label('Priority')
                            ->placeholder('—'),
                        TextEntry::make('ttl')
                            ->label('TTL'),
                    ])
                    ->columns(5)
                    ->columnSpanFull(),
            ]);
    }
}
