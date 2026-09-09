<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Navigation Tabs --}}
        <div class="flex border-b border-gray-200 dark:border-gray-800">
            <button 
                type="button" 
                wire:click="$set('activeTab', 'lookup')"
                class="px-4 py-3 font-semibold text-sm border-b-2 transition-all {{ $activeTab === 'lookup' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
            >
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-4 w-4" />
                    <span>DNS Record Lookup</span>
                </div>
            </button>
            <button 
                type="button" 
                wire:click="$set('activeTab', 'propagation')"
                class="px-4 py-3 font-semibold text-sm border-b-2 transition-all {{ $activeTab === 'propagation' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
            >
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-globe-americas" class="h-4 w-4" />
                    <span>Global DNS Propagation Checker</span>
                </div>
            </button>
        </div>

        {{-- TAB 1: DNS Record Lookup --}}
        @if($activeTab === 'lookup')
            <div class="space-y-6">
                <x-filament::section>
                    <x-slot name="heading">
                        Query DNS Records
                    </x-slot>
                    <x-slot name="description">
                        Resolve any host or zone record in real-time against system resolvers or authoritative nameservers.
                    </x-slot>

                    <form wire:submit.prevent="runLookup" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Domain / Hostname</label>
                                <input 
                                    type="text" 
                                    wire:model="lookupDomain" 
                                    placeholder="e.g. google.com or mail.domain.com" 
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500" 
                                    required 
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Record Type</label>
                                <select 
                                    wire:model="lookupType" 
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <option value="A">A (IPv4)</option>
                                    <option value="AAAA">AAAA (IPv6)</option>
                                    <option value="CNAME">CNAME (Alias)</option>
                                    <option value="MX">MX (Mail)</option>
                                    <option value="TXT">TXT (Text/SPF/DKIM)</option>
                                    <option value="NS">NS (Nameserver)</option>
                                    <option value="SOA">SOA (Authority)</option>
                                    <option value="CAA">CAA (Certificate)</option>
                                    <option value="PTR">PTR (Reverse)</option>
                                    <option value="ANY">ANY (All Available)</option>
                                </select>
                            </div>

                            <div>
                                <x-filament::button type="submit" icon="heroicon-o-magnifying-glass" class="w-full">
                                    Lookup DNS
                                </x-filament::button>
                            </div>
                        </div>
                    </form>
                </x-filament::section>

                @if($lookupResults)
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center justify-between">
                                <span>Lookup Results for <code class="text-primary-600 dark:text-primary-400 font-mono">{{ $lookupDomain }}</code></span>
                                <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-mono">
                                    Latency: {{ $lookupResults['latency_ms'] }} ms
                                </span>
                            </div>
                        </x-slot>

                        @if(empty($lookupResults['records']))
                            <div class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                <x-filament::icon icon="heroicon-o-exclamation-circle" class="mx-auto h-8 w-8 text-amber-500 mb-2" />
                                No <strong>{{ $lookupType }}</strong> records found for <strong>{{ $lookupDomain }}</strong>.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-800">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                                            <th class="px-4 py-3">Host</th>
                                            <th class="px-4 py-3">Type</th>
                                            <th class="px-4 py-3">TTL</th>
                                            <th class="px-4 py-3">Priority</th>
                                            <th class="px-4 py-3">Target / Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 font-mono">
                                        @foreach($lookupResults['records'] as $rec)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/30">
                                                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">{{ $rec['host'] }}</td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-0.5 text-xs rounded font-bold bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300">
                                                        {{ $rec['type'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $rec['ttl'] }}s</td>
                                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $rec['prio'] ?? '—' }}</td>
                                                <td class="px-4 py-3 text-gray-900 dark:text-white break-all select-all font-semibold">{{ $rec['content'] }}</td>
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

        {{-- TAB 2: Global DNS Propagation Checker --}}
        @if($activeTab === 'propagation')
            <div class="space-y-6">
                <x-filament::section>
                    <x-slot name="heading">
                        Global Anycast DNS Propagation Check
                    </x-slot>
                    <x-slot name="description">
                        Check whether DNS records have propagated to global public DNS resolvers across multiple regions.
                    </x-slot>

                    <form wire:submit.prevent="runPropagation" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Domain / Hostname</label>
                                <input 
                                    type="text" 
                                    wire:model="propDomain" 
                                    placeholder="e.g. example.com" 
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500" 
                                    required 
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Record Type</label>
                                <select 
                                    wire:model="propType" 
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <option value="A">A (IPv4)</option>
                                    <option value="AAAA">AAAA (IPv6)</option>
                                    <option value="CNAME">CNAME (Alias)</option>
                                    <option value="MX">MX (Mail)</option>
                                    <option value="TXT">TXT (SPF/DKIM)</option>
                                    <option value="NS">NS (Nameserver)</option>
                                    <option value="CAA">CAA</option>
                                </select>
                            </div>

                            <div>
                                <x-filament::button type="submit" icon="heroicon-o-globe-americas" class="w-full">
                                    Check Propagation
                                </x-filament::button>
                            </div>
                        </div>
                    </form>
                </x-filament::section>

                @if($propResults)
                    <x-filament::section>
                        <x-slot name="heading">
                            Global Propagation Status for <code class="text-primary-600 dark:text-primary-400 font-mono">{{ $propDomain }} ({{ $propType }})</code>
                        </x-slot>

                        <div class="space-y-3">
                            @foreach($propResults as $node)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            @if($node['status'] === 'resolved')
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                                                    <x-filament::icon icon="heroicon-o-check" class="h-5 w-5" />
                                                </span>
                                            @elseif($node['status'] === 'nodata')
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400">
                                                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5" />
                                                </span>
                                            @else
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                                                    <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                                                </span>
                                            @endif
                                        </div>

                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                <span>{{ $node['provider'] }}</span>
                                                <span class="text-xs font-mono text-gray-500 font-normal">({{ $node['ip'] }})</span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $node['location'] }} &bull; {{ $node['latency_ms'] }} ms
                                            </div>
                                        </div>
                                    </div>

                                    <div class="font-mono text-xs text-right sm:max-w-md">
                                        <span class="px-2.5 py-1 rounded bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 font-semibold break-all select-all inline-block">
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
