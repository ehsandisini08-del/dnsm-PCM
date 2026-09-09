<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Create Backup Header Section --}}
        <x-filament::section>
            <x-slot name="heading">
                Create System Snapshot
            </x-slot>
            <x-slot name="description">
                Generates a complete snapshot of all DNS Zones, Resource Records, Customers, Nameservers, Templates, and Users.
            </x-slot>

            <form wire:submit.prevent="createNewBackup" class="flex flex-col sm:flex-row items-end gap-3">
                <div class="flex-grow w-full">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Optional Snapshot Label</label>
                    <input 
                        type="text" 
                        wire:model="backupName" 
                        placeholder="e.g. pre-migration-backup or monthly-snapshot" 
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500" 
                    />
                </div>
                <x-filament::button type="submit" icon="heroicon-o-plus-circle" class="w-full sm:w-auto">
                    Create Backup Now
                </x-filament::button>
            </form>
        </x-filament::section>

        {{-- List of Backups --}}
        <x-filament::section>
            <x-slot name="heading">
                Available Backups & Snapshots
            </x-slot>

            @if(empty($this->backups))
                <div class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-archive-box" class="mx-auto h-12 w-12 text-gray-400 mb-3" />
                    No backup files created yet. Click <strong>Create Backup Now</strong> above to generate your first snapshot.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-3">Backup File</th>
                                <th class="px-4 py-3">Created</th>
                                <th class="px-4 py-3">Size</th>
                                <th class="px-4 py-3">Zones & Records</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($this->backups as $backup)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30">
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-bold text-gray-900 dark:text-white text-sm">
                                            {{ $backup['filename'] }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                        {{ $backup['created_at'] }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">
                                        {{ $backup['size_formatted'] }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300">
                                            {{ $backup['total_zones'] }} Zones
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 ml-1">
                                            {{ $backup['total_records'] }} Records
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-1">
                                        <button 
                                            type="button" 
                                            wire:click="downloadBackup('{{ $backup['filename'] }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition"
                                        >
                                            <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-3.5 w-3.5" />
                                            <span>Download</span>
                                        </button>

                                        <button 
                                            type="button" 
                                            wire:click="restoreSelectedBackup('{{ $backup['filename'] }}')"
                                            wire:confirm="WARNING: Are you sure you want to restore this backup? This will sync database records with the snapshot."
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/80 text-amber-700 dark:text-amber-300 border border-amber-300/40 transition"
                                        >
                                            <x-filament::icon icon="heroicon-o-arrow-path" class="h-3.5 w-3.5" />
                                            <span>Restore</span>
                                        </button>

                                        <button 
                                            type="button" 
                                            wire:click="deleteSelectedBackup('{{ $backup['filename'] }}')"
                                            wire:confirm="Are you sure you want to delete this backup file?"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/80 text-rose-700 dark:text-rose-300 border border-rose-300/40 transition"
                                        >
                                            <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5" />
                                            <span>Delete</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
