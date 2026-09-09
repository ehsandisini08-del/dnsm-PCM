<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Full Name')
                    ->weight('bold'),

                TextEntry::make('email')
                    ->label('Email Address')
                    ->copyable(),

                TextEntry::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'dns_admin' => 'DNS Admin',
                        'operator' => 'Operator',
                        'customer' => 'Customer',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'dns_admin' => 'warning',
                        'operator' => 'info',
                        'customer' => 'gray',
                        default => 'secondary',
                    }),

                IconEntry::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextEntry::make('created_at')
                    ->label('Registered At')
                    ->dateTime(),

                TextEntry::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime(),
            ]);
    }
}
