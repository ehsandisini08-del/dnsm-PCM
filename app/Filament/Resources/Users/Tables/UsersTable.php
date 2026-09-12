<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Notifications\AccountApprovedNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('role')
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
                    })
                    ->sortable(),

                TextColumn::make('approval_status')
                    ->label('Approval')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => 'Unknown',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'dns_admin' => 'DNS Admin',
                        'operator' => 'Operator',
                        'customer' => 'Customer',
                    ]),

                SelectFilter::make('approval_status')
                    ->label('Approval Status')
                    ->options([
                        'pending' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->isPendingApproval())
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pendaftaran Akun')
                    ->modalDescription(fn (User $record): string => "Apakah Anda yakin menyetujui akun \"{$record->name}\" ({$record->email})?")
                    ->form([
                        Select::make('assigned_role')
                            ->label('Pilih Role yang Diberikan')
                            ->options([
                                'operator' => 'Operator NOC',
                                'dns_admin' => 'DNS Administrator',
                                'customer' => 'Customer',
                            ])
                            ->default('operator')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'approval_status' => 'approved',
                            'is_active' => true,
                            'role' => $data['assigned_role'],
                            'approved_at' => now(),
                            'approved_by' => auth()->id(),
                        ]);

                        try {
                            $record->notify(new AccountApprovedNotification($data['assigned_role']));
                        } catch (\Throwable) {
                        }

                        Notification::make()
                            ->title('Akun Disetujui')
                            ->body("Akun [{$record->name}] telah disetujui sebagai " . ucfirst(str_replace('_', ' ', $data['assigned_role'])) . '.')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->isPendingApproval())
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pendaftaran Akun')
                    ->modalDescription(fn (User $record): string => "Apakah Anda yakin menolak akun \"{$record->name}\" ({$record->email})? Akun tidak akan dapat mengakses dashboard.")
                    ->action(function (User $record) {
                        $record->update([
                            'approval_status' => 'rejected',
                            'is_active' => false,
                        ]);

                        Notification::make()
                            ->title('Akun Ditolak')
                            ->body("Pendaftaran akun [{$record->name}] telah ditolak.")
                            ->danger()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
