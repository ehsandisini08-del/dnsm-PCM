<?php

namespace App\Filament\Pages;

use App\Services\BackupService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class Backups extends Page
{
    protected string $view = 'filament.pages.backups';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Backups & Snapshots';

    protected static ?string $title = 'System Backups & Database Snapshots';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    public string $backupName = '';

    public function getBackupsProperty(): array
    {
        return app(BackupService::class)->listBackups();
    }

    public function createNewBackup(): void
    {
        try {
            $service = app(BackupService::class);
            $result = $service->createBackup($this->backupName ?: null);

            $this->backupName = '';

            Notification::make()
                ->title('Backup Created Successfully')
                ->body("Backup [{$result['filename']}] created with {$result['total_zones']} zones and {$result['total_records']} records.")
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Backup Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function downloadBackup(string $filename): BinaryFileResponse
    {
        $path = storage_path('app/backups/'.basename($filename));

        return response()->download($path, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function restoreSelectedBackup(string $filename): void
    {
        try {
            $service = app(BackupService::class);
            $service->restoreBackup($filename);

            Notification::make()
                ->title('Database Restored')
                ->body("Backup [{$filename}] restored successfully.")
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Restore Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteSelectedBackup(string $filename): void
    {
        try {
            $service = app(BackupService::class);
            $service->deleteBackup($filename);

            Notification::make()
                ->title('Backup Deleted')
                ->body("Backup [{$filename}] was deleted.")
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Delete Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
