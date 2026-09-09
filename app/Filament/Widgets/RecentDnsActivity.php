<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentDnsActivity extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent DNS Activity & Audit Stream';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => AuditLog::query()->with('user')->latest()->limit(8))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('user.name')
                    ->label('Actor')
                    ->placeholder('System / Automated')
                    ->icon('heroicon-m-user')
                    ->color('primary'),

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
                    }),

                TextColumn::make('model_type')
                    ->label('Entity')
                    ->formatStateUsing(fn ($state, AuditLog $record): string => $state ? class_basename($state).' #'.$record->model_id : 'Authentication')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->copyable()
                    ->fontFamily('mono'),
            ])
            ->paginated(false);
    }
}
