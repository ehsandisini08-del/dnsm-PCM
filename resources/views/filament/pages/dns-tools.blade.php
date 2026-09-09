<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 1. Native Filament Contained Tabs --}}
        <x-filament::tabs :contained="true">
            <x-filament::tabs.item
                :active="$activeTab === 'lookup'"
                wire:click="$set('activeTab', 'lookup')"
                icon="heroicon-m-magnifying-glass"
                :badge="$lookupResults ? (string) $lookupResults['count'] : null"
                badge-color="primary"
            >
                DNS Record Lookup
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'propagation'"
                wire:click="$set('activeTab', 'propagation')"
                icon="heroicon-m-globe-americas"
                badge="5 Global Nodes"
                badge-color="success"
            >
                Global DNS Propagation
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- ==================================================================== --}}
        {{-- TAB 1: DNS RECORD LOOKUP                                             --}}
        {{-- ==================================================================== --}}
        @if($activeTab === 'lookup')
            <div class="space-y-6">
                <x-filament::section icon="heroicon-o-magnifying-glass-circle">
                    <x-slot name="heading">
                        Query DNS Resource Records
                    </x-slot>
                    <x-slot name="description">
                        Perform real-time DNS resolution on local authoritative backend or upstream resolvers.
                    </x-slot>

                    <form wire:submit.prevent="runLookup" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                            {{-- Domain Input --}}
                            <div class="sm:col-span-6 space-y-2">
                                <label class="block text-sm font-medium text-gray-950 dark:text-white">
                                    Domain / Hostname <span class="text-danger-600">*</span>
                                </label>
                                <x-filament::input.wrapper :prefix-icon="'heroicon-m-globe-alt'">
                                    <x-filament::input
                                        type="text"
                                        wire:model="lookupDomain"
                                        placeholder="e.g. google.com or mail.domain.com"
                                        required
                                    />
                                </x-filament::input.wrapper>
                            </div>

                            {{-- Record Type Selector --}}
                            <div class="sm:col-span-3 space-y-2">
                                <label class="block text-sm font-medium text-gray-950 dark:text-white">
                                    Record Type
                                </label>
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model="lookupType">
                                        <option value="A">A (IPv4)</option>
                                        <option value="AAAA">AAAA (IPv6)</option>
                                        <option value="CNAME">CNAME (Alias)</option>
                                        <option value="MX">MX (Mail Server)</option>
                                        <option value="TXT">TXT (SPF, DKIM, Text)</option>
                                        <option value="NS">NS (Nameserver)</option>
                                        <option value="SOA">SOA (Start of Authority)</option>
                                        <option value="CAA">CAA (Certificate Authority)</option>
                                        <option value="PTR">PTR (Reverse DNS)</option>
                                        <option value="SRV">SRV (Service Record)</option>
                                        <option value="ANY">ANY (All Available Records)</option>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>

                            {{-- Submit Button --}}
                            <div class="sm:col-span-3">
                                <x-filament::button
                                    type="submit"
                                    icon="heroicon-m-magnifying-glass"
                                    class="w-full"
                                    wire:target="runLookup"
                                >
                                    Resolve DNS
                                </x-filament::button>
                            </div>
                        </div>

                        {{-- Quick Zone Suggestions --}}
                        @if(!empty($this->existingZones))
                            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Quick Select:</span>
                                @foreach(array_slice($this->existingZones, 0, 6) as $zone)
                                    <button 
                                        type="button" 
                                        wire:click="selectDomainForLookup('{{ $zone }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-mono font-medium rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition"
                                    >
                                        <span>{{ $zone }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </form>
                </x-filament::section>

                {{-- Lookup Results Card --}}
                @if($lookupResults)
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span>Results for</span>
                                    <span class="font-mono font-bold text-primary-600 dark:text-primary-400">
                                        {{ $lookupDomain }}
                                    </span>
                                    <x-filament::badge color="gray" size="sm">
                                        {{ $lookupType }}
                                    </x-filament::badge>
                                </div>

                                <div class="flex items-center gap-2 font-mono text-xs">
                                    <x-filament::badge :color="$lookupResults['count'] > 0 ? 'success' : 'warning'" size="sm">
                                        {{ $lookupResults['count'] }} record(s) found
                                    </x-filament::badge>
                                    <x-filament::badge color="gray" size="sm">
                                        Latency: {{ $lookupResults['latency_ms'] }} ms
                                    </x-filament::badge>
                                </div>
                            </div>
                        </x-slot>

                        @if(empty($lookupResults['records']))
                            <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                <x-filament::icon icon="heroicon-o-information-circle" class="mx-auto h-8 w-8 text-amber-500 mb-2" />
                                <div class="font-semibold text-gray-800 dark:text-gray-200">No {{ $lookupType }} records found</div>
                                <p class="text-xs text-gray-400 mt-1">The server returned no answers for {{ $lookupDomain }}.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto -mx-6 -mb-6">
                                <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-800">
                                    <thead>
                                        <tr class="bg-gray-50/80 dark:bg-gray-900/60 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                            <th class="px-6 py-3.5">Host</th>
                                            <th class="px-6 py-3.5">Type</th>
                                            <th class="px-6 py-3.5">TTL</th>
                                            <th class="px-6 py-3.5">Priority</th>
                                            <th class="px-6 py-3.5">Content / Target</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 font-mono text-xs">
                                        @foreach($lookupResults['records'] as $rec)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                                                <td class="px-6 py-3.5 text-gray-950 dark:text-white font-medium">
                                                    {{ $rec['host'] }}
                                                </td>
                                                <td class="px-6 py-3.5">
                                                    <x-filament::badge :color="match($rec['type']) {
                                                        'A', 'AAAA' => 'primary',
                                                        'CNAME' => 'info',
                                                        'MX' => 'warning',
                                                        'TXT' => 'success',
                                                        'NS' => 'purple',
                                                        'SOA' => 'danger',
                                                        default => 'gray'
                                                    }" size="sm">
                                                        {{ $rec['type'] }}
                                                    </x-filament::badge>
                                                </td>
                                                <td class="px-6 py-3.5 text-gray-500 dark:text-gray-400">
                                                    {{ $rec['ttl'] }}s
                                                </td>
                                                <td class="px-6 py-3.5 text-gray-500 dark:text-gray-400">
                                                    {{ $rec['prio'] ?? '—' }}
                                                </td>
                                                <td class="px-6 py-3.5 text-gray-950 dark:text-white font-bold select-all break-all">
                                                    {{ $rec['content'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </x-filament::section>
                @endif
            </div>
        @endif

        {{-- ==================================================================== --}}
        {{-- TAB 2: GLOBAL DNS PROPAGATION CHECKER                                --}}
        {{-- ==================================================================== --}}
        @if($activeTab === 'propagation')
            <div class="space-y-6">
                <x-filament::section icon="heroicon-o-globe-americas">
                    <x-slot name="heading">
                        Global Anycast DNS Propagation Check
                    </x-slot>
                    <x-slot name="description">
                        Verify if DNS record changes have propagated globally across 5 major public Anycast resolvers.
                    </x-slot>

                    <form wire:submit.prevent="runPropagation" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                            {{-- Domain Input --}}
                            <div class="sm:col-span-6 space-y-2">
                                <label class="block text-sm font-medium text-gray-950 dark:text-white">
                                    Domain / Hostname <span class="text-danger-600">*</span>
                                </label>
                                <x-filament::input.wrapper :prefix-icon="'heroicon-m-globe-alt'">
                                    <x-filament::input
                                        type="text"
                                        wire:model="propDomain"
                                        placeholder="e.g. example.com"
                                        required
                                    />
                                </x-filament::input.wrapper>
                            </div>

                            {{-- Record Type --}}
                            <div class="sm:col-span-3 space-y-2">
                                <label class="block text-sm font-medium text-gray-950 dark:text-white">
                                    Record Type
                                </label>
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model="propType">
                                        <option value="A">A (IPv4)</option>
                                        <option value="AAAA">AAAA (IPv6)</option>
                                        <option value="CNAME">CNAME (Alias)</option>
                                        <option value="MX">MX (Mail Server)</option>
                                        <option value="TXT">TXT (SPF/DKIM)</option>
                                        <option value="NS">NS (Nameserver)</option>
                                        <option value="CAA">CAA</option>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>

                            {{-- Submit Button --}}
                            <div class="sm:col-span-3">
                                <x-filament::button
                                    type="submit"
                                    color="success"
                                    icon="heroicon-m-globe-americas"
                                    class="w-full"
                                    wire:target="runPropagation"
                                >
                                    Check Propagation
                                </x-filament::button>
                            </div>
                        </div>

                        {{-- Quick Zone Suggestions --}}
                        @if(!empty($this->existingZones))
                            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Quick Select:</span>
                                @foreach(array_slice($this->existingZones, 0, 6) as $zone)
                                    <button 
                                        type="button" 
                                        wire:click="selectDomainForProp('{{ $zone }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-mono font-medium rounded-lg border border-gray-200 dark:border-gray-700 hover:border-success-500 dark:hover:border-success-500 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:text-success-600 dark:hover:text-success-400 transition"
                                    >
                                        <span>{{ $zone }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </form>
                </x-filament::section>

                {{-- Propagation Results --}}
                @if($propResults)
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span>Propagation for</span>
                                    <span class="font-mono font-bold text-success-600 dark:text-success-400">
                                        {{ $propDomain }}
                                    </span>
                                    <x-filament::badge color="gray" size="sm">
                                        {{ $propType }}
                                    </x-filament::badge>
                                </div>
                                <span class="text-xs text-gray-500 font-mono">5 Global Resolvers Queried</span>
                            </div>
                        </x-slot>

                        <div class="space-y-3">
                            @foreach($propResults as $node)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/40 hover:border-gray-300 dark:hover:border-gray-700 transition gap-3">
                                    <div class="flex items-center gap-3.5">
                                        <div class="flex-shrink-0">
                                            @if($node['status'] === 'resolved')
                                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-success-100 text-success-700 dark:bg-success-950 dark:text-success-400">
                                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-6 w-6" />
                                                </span>
                                            @elseif($node['status'] === 'nodata')
                                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-warning-100 text-warning-700 dark:bg-warning-950 dark:text-warning-400">
                                                    <x-filament::icon icon="heroicon-o-exclamation-circle" class="h-6 w-6" />
                                                </span>
                                            @else
                                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-danger-100 text-danger-700 dark:bg-danger-950 dark:text-danger-400">
                                                    <x-filament::icon icon="heroicon-o-x-circle" class="h-6 w-6" />
                                                </span>
                                            @endif
                                        </div>

                                        <div class="space-y-0.5">
                                            <div class="font-bold text-gray-950 dark:text-white text-sm flex items-center gap-2">
                                                <span>{{ $node['provider'] }}</span>
                                                <span class="text-xs font-mono font-normal text-gray-400">({{ $node['ip'] }})</span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 font-mono">
                                                <span>{{ $node['location'] }}</span>
                                                <span>&bull;</span>
                                                <span class="text-primary-600 dark:text-primary-400 font-semibold">{{ $node['latency_ms'] }} ms</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="font-mono text-xs sm:text-right">
                                        <span class="inline-block px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-950 dark:text-gray-100 font-bold select-all break-all shadow-sm">
                                            {{ $node['result'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif
            </div>
        @endif

    </div>
</x-filament-panels::page>
