<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use App\Models\AuditLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')
                    ->label('Timestamp')
                    ->dateTime(),

                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('System'),

                TextEntry::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'CREATE') => 'success',
                        str_starts_with($state, 'UPDATE') => 'warning',
                        str_starts_with($state, 'DELETE') => 'danger',
                        $state === 'LOGIN' => 'info',
                        $state === 'LOGOUT' => 'gray',
                        $state === 'LOGIN_FAILED' => 'danger',
                        default => 'secondary',
                    }),

                TextEntry::make('model_type')
                    ->label('Target Model')
                    ->formatStateUsing(fn ($state, AuditLog $record): string => $state ? class_basename($state).' #'.$record->model_id : '—'),

                TextEntry::make('ip_address')
                    ->label('IP Address')
                    ->copyable(),

                TextEntry::make('user_agent')
                    ->label('User Agent')
                    ->placeholder('—')
                    ->columnSpanFull(),

                TextEntry::make('old_values')
                    ->label('Old Values (Before)')
                    ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '—')
                    ->columnSpanFull(),

                TextEntry::make('new_values')
                    ->label('New Values (After)')
                    ->formatStateUsing(fn ($state): string => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '—')
                    ->columnSpanFull(),
            ]);
    }
}
