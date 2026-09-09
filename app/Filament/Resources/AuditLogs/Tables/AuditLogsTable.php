<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System / Unauthenticated'),

                TextColumn::make('action')
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
                    })
                    ->sortable(),

                TextColumn::make('model_type')
                    ->label('Target Model')
                    ->formatStateUsing(fn ($state, AuditLog $record): string => $state ? class_basename($state).' #'.$record->model_id : '—')
                    ->sortable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('user_agent')
                    ->label('Client')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'LOGIN' => 'LOGIN',
                        'LOGOUT' => 'LOGOUT',
                        'LOGIN_FAILED' => 'LOGIN_FAILED',
                        'CREATE_ZONE' => 'CREATE_ZONE',
                        'UPDATE_ZONE' => 'UPDATE_ZONE',
                        'DELETE_ZONE' => 'DELETE_ZONE',
                        'CREATE_RECORD' => 'CREATE_RECORD',
                        'UPDATE_RECORD' => 'UPDATE_RECORD',
                        'DELETE_RECORD' => 'DELETE_RECORD',
                        'CREATE_USER' => 'CREATE_USER',
                        'UPDATE_USER' => 'UPDATE_USER',
                        'DELETE_USER' => 'DELETE_USER',
                        'CREATE_CUSTOMER' => 'CREATE_CUSTOMER',
                        'UPDATE_CUSTOMER' => 'UPDATE_CUSTOMER',
                        'DELETE_CUSTOMER' => 'DELETE_CUSTOMER',
                        'CREATE_SERVER' => 'CREATE_SERVER',
                        'UPDATE_SERVER' => 'UPDATE_SERVER',
                        'DELETE_SERVER' => 'DELETE_SERVER',
                    ]),

                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
