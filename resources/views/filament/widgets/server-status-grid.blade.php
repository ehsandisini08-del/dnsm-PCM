<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-server-stack" class="h-5 w-5 text-primary-500" />
                    <span class="font-semibold text-gray-900 dark:text-white">DNS Nameserver Cluster Status</span>
                </div>
            </div>
        </x-slot>

        @php
            $servers = $this->getServers();
        @endphp

        @if($servers->isEmpty())
            <div class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-server" class="mx-auto h-8 w-8 text-gray-400 mb-2" />
                No DNS Nameservers configured yet.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($servers as $server)
                    <div class="flex flex-col justify-between p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 hover:border-primary-500/50 transition-all duration-200">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900 dark:text-white text-base">{{ $server->name }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full uppercase tracking-wider font-mono font-medium {{ match($server->status) {
                                        'online' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300/40',
                                        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-300/40',
                                        default => 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-300/40'
                                    } }}">
                                        {{ $server->status }}
                                    </span>
                                </div>
                                <div class="text-xs font-mono text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span>{{ $server->ip_address }}:{{ $server->port }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $server->hostname }}</span>
                                </div>
                            </div>

                            <button 
                                type="button"
                                wire:click="checkServer({{ $server->id }})"
                                title="Run Instant Ping & Health Check"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-primary-600 hover:bg-white dark:hover:bg-gray-800 transition shadow-sm border border-transparent hover:border-gray-200 dark:hover:border-gray-700"
                            >
                                <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-200/70 dark:border-gray-800/70 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1.5">
                                <x-filament::icon icon="heroicon-m-globe-alt" class="h-3.5 w-3.5 text-primary-500" />
                                <strong>{{ $server->zones_count }}</strong> assigned zones
                            </span>
                            <span>
                                Checked {{ $server->last_check_at ? $server->last_check_at->diffForHumans() : 'never' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
