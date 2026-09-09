<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Contact / Name')
                    ->weight('bold'),

                TextEntry::make('company')
                    ->label('Company')
                    ->placeholder('—'),

                TextEntry::make('email')
                    ->label('Email')
                    ->copyable(),

                TextEntry::make('phone')
                    ->label('Phone')
                    ->placeholder('—'),

                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        default => 'secondary',
                    }),

                TextEntry::make('zones_count')
                    ->label('Total Zones')
                    ->state(fn (Customer $record): int => $record->zones()->count())
                    ->badge()
                    ->color('primary'),

                TextEntry::make('address')
                    ->label('Address')
                    ->placeholder('—')
                    ->columnSpanFull(),

                TextEntry::make('notes')
                    ->label('Notes')
                    ->placeholder('—')
                    ->columnSpanFull(),

                TextEntry::make('created_at')
                    ->label('Registered At')
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime(),
            ]);
    }
}
