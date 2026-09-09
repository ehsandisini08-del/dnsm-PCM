<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Status Cards & Action --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Active Server Nodes</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Real-time status of authoritative PowerDNS servers & API endpoints.</p>
            </div>
            <div>
                <x-filament::button 
                    wire:click="runAllHealthChecks" 
                    icon="heroicon-o-arrow-path" 
                    color="primary"
                >
                    Run Full Diagnostics on All Servers
                </x-filament::button>
            </div>
        </div>

        {{-- Server Node Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($this->servers as $server)
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="font-bold text-base text-gray-900 dark:text-white">{{ $server->name }}</div>
                            <div class="text-xs font-mono text-gray-500">{{ $server->ip_address }}:{{ $server->port }}</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold uppercase {{ match($server->status) {
                            'online' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300/50',
                            'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-300/50',
                            default => 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-300/50'
                        } }}">
                            {{ $server->status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-gray-100 dark:border-gray-800">
                        <div>
                            <span class="text-gray-500">Hostname:</span>
                            <div class="font-mono truncate text-gray-800 dark:text-gray-200">{{ $server->hostname }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Assigned Zones:</span>
                            <div class="font-bold text-gray-800 dark:text-gray-200">{{ $server->zones_count }} zones</div>
                        </div>
                    </div>

                    <div class="text-xs text-gray-400 dark:text-gray-500 pt-1">
                        Last checked: {{ $server->last_check_at ? $server->last_check_at->diffForHumans() : 'Never' }}
                    </div>
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-sm text-gray-500 dark:text-gray-400 border border-dashed rounded-xl border-gray-300 dark:border-gray-700">
                    No DNS servers configured. <a href="{{ url('/admin/dns-servers/create') }}" class="text-primary-600 underline">Add nameserver</a>.
                </div>
            @endforelse
        </div>

        {{-- Diagnostic Check History Stream Table --}}
        <div class="space-y-3">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Diagnostic & Health Check History Stream</h3>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
