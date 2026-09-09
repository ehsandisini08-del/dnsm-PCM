<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 1. Modern Segmented Header Tab Navigation --}}
        <div class="p-1.5 bg-gray-100 dark:bg-gray-800/80 rounded-2xl inline-flex flex-wrap sm:flex-nowrap gap-1.5 w-full sm:w-auto shadow-inner border border-gray-200/60 dark:border-gray-700/60">
            <button 
                type="button" 
                wire:click="$set('activeTab', 'lookup')"
                class="flex-1 sm:flex-initial flex items-center justify-center gap-2.5 px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 {{ $activeTab === 'lookup' ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400 shadow-sm ring-1 ring-black/5 dark:ring-white/10' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-white/40 dark:hover:bg-gray-700/40' }}"
            >
                <x-filament::icon icon="heroicon-m-magnifying-glass" class="h-4 w-4" />
                <span>DNS Record Lookup</span>
                @if($lookupResults)
                    <span class="ml-1 text-[11px] font-mono font-bold px-1.5 py-0.5 rounded-full bg-primary-50 dark:bg-primary-950 text-primary-600 dark:text-primary-400">
                        {{ $lookupResults['count'] }}
                    </span>
                @endif
            </button>

            <button 
                type="button" 
                wire:click="$set('activeTab', 'propagation')"
                class="flex-1 sm:flex-initial flex items-center justify-center gap-2.5 px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 {{ $activeTab === 'propagation' ? 'bg-white dark:bg-gray-900 text-primary-600 dark:text-primary-400 shadow-sm ring-1 ring-black/5 dark:ring-white/10' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-white/40 dark:hover:bg-gray-700/40' }}"
            >
                <x-filament::icon icon="heroicon-m-globe-americas" class="h-4 w-4" />
                <span>Global DNS Propagation</span>
                @if($propResults)
                    <span class="ml-1 text-[11px] font-mono font-bold px-1.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400">
                        5 Nodes
                    </span>
                @endif
            </button>
        </div>

        {{-- ==================================================================== --}}
        {{-- TAB 1: DNS RECORD LOOKUP                                             --}}
        {{-- ==================================================================== --}}
        @if($activeTab === 'lookup')
            <div class="space-y-6">
                {{-- Query Form Card --}}
                <div class="p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400">
                                <x-filament::icon icon="heroicon-o-magnifying-glass-circle" class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900 dark:text-white">Query DNS Resource Records</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Perform real-time DNS resolution on local authoritative backend or public resolvers.</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit.prevent="runLookup" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                            {{-- Domain Input --}}
                            <div class="sm:col-span-6 space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    Domain / Hostname
                                </label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <x-filament::icon icon="heroicon-m-globe-alt" class="h-4 w-4" />
                                    </div>
                                    <input 
                                        type="text" 
                                        wire:model="lookupDomain" 
                                        placeholder="e.g. google.com or mail.domain.com" 
                                        class="block w-full pl-9 pr-3 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-950 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition" 
                                        required 
                                    />
                                </div>
                            </div>

                            {{-- Record Type Selector --}}
                            <div class="sm:col-span-3 space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    Record Type
                                </label>
                                <select 
                                    wire:model="lookupType" 
                                    class="block w-full py-2.5 px-3 text-sm font-semibold rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-950 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                                >
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
                                </select>
                            </div>

                            {{-- Submit Button --}}
                            <div class="sm:col-span-3">
                                <button 
                                    type="submit" 
                                    wire:loading.attr="disabled"
                                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-primary-600 hover:bg-primary-500 active:bg-primary-700 shadow-sm transition-all disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="runLookup" class="flex items-center gap-2">
                                        <x-filament::icon icon="heroicon-m-magnifying-glass" class="h-4 w-4" />
                                        <span>Resolve DNS</span>
                                    </span>
                                    <span wire:loading wire:target="runLookup" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span>Querying...</span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- Quick Zone Suggestions --}}
                        @if(!empty($this->existingZones))
                            <div class="flex flex-wrap items-center gap-1.5 pt-2">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1">Your Hosted Zones:</span>
                                @foreach(array_slice($this->existingZones, 0, 5) as $zone)
                                    <button 
                                        type="button" 
                                        wire:click="selectDomainForLookup('{{ $zone }}')"
                                        class="px-2 py-0.5 text-xs font-mono rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 transition"
                                    >
                                        {{ $zone }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </form>
                </div>

                {{-- Lookup Results Table --}}
                @if($lookupResults)
                    <div class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                        {{-- Result Header Badge Bar --}}
                        <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <span class="font-bold text-gray-900 dark:text-white text-base">Results for</span>
                                <span class="px-2.5 py-1 rounded-lg bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300 font-mono font-bold text-sm">
                                    {{ $lookupDomain }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-xs uppercase font-bold bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                    {{ $lookupType }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 text-xs font-mono">
                                <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                    <span class="h-2 w-2 rounded-full {{ $lookupResults['count'] > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
                                    <span>Found <strong>{{ $lookupResults['count'] }}</strong> record(s)</span>
                                </span>
                                <span class="text-gray-300 dark:text-gray-700">|</span>
                                <span class="text-gray-600 dark:text-gray-300">
                                    Latency: <strong>{{ $lookupResults['latency_ms'] }} ms</strong>
                                </span>
                            </div>
                        </div>

                        {{-- Table or Empty State --}}
                        @if(empty($lookupResults['records']))
                            <div class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                <x-filament::icon icon="heroicon-o-information-circle" class="mx-auto h-10 w-10 text-amber-500 mb-2" />
                                <div class="font-bold text-gray-800 dark:text-gray-200">No records found</div>
                                <p class="text-xs text-gray-400 mt-1">No {{ $lookupType }} records answered for {{ $lookupDomain }}.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm divide-y divide-gray-200 dark:divide-gray-800">
                                    <thead>
                                        <tr class="bg-gray-50/80 dark:bg-gray-950/60 text-[11px] uppercase tracking-wider font-bold text-gray-500 dark:text-gray-400">
                                            <th class="px-5 py-3.5">Host</th>
                                            <th class="px-5 py-3.5">Type</th>
                                            <th class="px-5 py-3.5">TTL</th>
                                            <th class="px-5 py-3.5">Priority</th>
                                            <th class="px-5 py-3.5">Target / Content</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 font-mono text-xs">
                                        @foreach($lookupResults['records'] as $rec)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                                                <td class="px-5 py-3.5 text-gray-900 dark:text-white font-semibold">
                                                    {{ $rec['host'] }}
                                                </td>
                                                <td class="px-5 py-3.5">
                                                    <span class="px-2 py-0.5 text-xs rounded font-bold {{ match($rec['type']) {
                                                        'A', 'AAAA' => 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300',
                                                        'CNAME' => 'bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300',
                                                        'MX' => 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300',
                                                        'TXT' => 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300',
                                                        'NS' => 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300',
                                                        'SOA' => 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300',
                                                        default => 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'
                                                    } }}">
                                                        {{ $rec['type'] }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400">
                                                    {{ $rec['ttl'] }}s
                                                </td>
                                                <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400">
                                                    {{ $rec['prio'] ?? '—' }}
                                                </td>
                                                <td class="px-5 py-3.5 text-gray-900 dark:text-white font-bold select-all break-all">
                                                    {{ $rec['content'] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        {{-- ==================================================================== --}}
        {{-- TAB 2: GLOBAL DNS PROPAGATION CHECKER                                --}}
        {{-- ==================================================================== --}}
        @if($activeTab === 'propagation')
            <div class="space-y-6">
                {{-- Propagation Query Form --}}
                <div class="p-6 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                                <x-filament::icon icon="heroicon-o-globe-americas" class="h-6 w-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-gray-900 dark:text-white">Global DNS Propagation Checker</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Verify if DNS record changes have propagated globally across 5 major Anycast networks.</p>
                            </div>
                        </div>
                    </div>

                    <form wire:submit.prevent="runPropagation" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                            {{-- Domain Input --}}
                            <div class="sm:col-span-6 space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    Domain / Hostname
                                </label>
                                <div class="relative rounded-xl shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <x-filament::icon icon="heroicon-m-globe-alt" class="h-4 w-4" />
                                    </div>
                                    <input 
                                        type="text" 
                                        wire:model="propDomain" 
                                        placeholder="e.g. example.com" 
                                        class="block w-full pl-9 pr-3 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-950 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition" 
                                        required 
                                    />
                                </div>
                            </div>

                            {{-- Record Type --}}
                            <div class="sm:col-span-3 space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    Record Type
                                </label>
                                <select 
                                    wire:model="propType" 
                                    class="block w-full py-2.5 px-3 text-sm font-semibold rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-950 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
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

                            {{-- Submit Button --}}
                            <div class="sm:col-span-3">
                                <button 
                                    type="submit" 
                                    wire:loading.attr="disabled"
                                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 shadow-sm transition-all disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="runPropagation" class="flex items-center gap-2">
                                        <x-filament::icon icon="heroicon-m-globe-americas" class="h-4 w-4" />
                                        <span>Check Propagation</span>
                                    </span>
                                    <span wire:loading wire:target="runPropagation" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span>Probing Resolvers...</span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- Quick Zone Suggestions --}}
                        @if(!empty($this->existingZones))
                            <div class="flex flex-wrap items-center gap-1.5 pt-2">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1">Your Hosted Zones:</span>
                                @foreach(array_slice($this->existingZones, 0, 5) as $zone)
                                    <button 
                                        type="button" 
                                        wire:click="selectDomainForProp('{{ $zone }}')"
                                        class="px-2 py-0.5 text-xs font-mono rounded-lg border border-gray-200 dark:border-gray-700 hover:border-emerald-500 dark:hover:border-emerald-500 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 transition"
                                    >
                                        {{ $zone }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </form>
                </div>

                {{-- Propagation Result Cards Grid --}}
                @if($propResults)
                    <div class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden space-y-4 p-5">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900 dark:text-white">Global Nodes for</span>
                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950">
                                    {{ $propDomain }} ({{ $propType }})
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 font-mono">5 Global Resolvers Queried</span>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            @foreach($propResults as $node)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-gray-200/80 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-950/40 hover:border-gray-300 dark:hover:border-gray-700 transition gap-3">
                                    <div class="flex items-center gap-3.5">
                                        <div class="flex-shrink-0">
                                            @if($node['status'] === 'resolved')
                                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-400 ring-4 ring-emerald-50 dark:ring-emerald-950/40">
                                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5" />
                                                </span>
                                            @elseif($node['status'] === 'nodata')
                                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-400 ring-4 ring-amber-50 dark:ring-amber-950/40">
                                                    <x-filament::icon icon="heroicon-o-exclamation-circle" class="h-5 w-5" />
                                                </span>
                                            @else
                                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-400 ring-4 ring-rose-50 dark:ring-rose-950/40">
                                                    <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5" />
                                                </span>
                                            @endif
                                        </div>

                                        <div class="space-y-0.5">
                                            <div class="font-bold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                <span>{{ $node['provider'] }}</span>
                                                <span class="text-xs font-mono font-normal text-gray-400">({{ $node['ip'] }})</span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 font-mono">
                                                <span>{{ $node['location'] }}</span>
                                                <span>&bull;</span>
                                                <span class="text-primary-600 dark:text-primary-400 font-bold">{{ $node['latency_ms'] }} ms</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="font-mono text-xs sm:text-right">
                                        <span class="inline-block px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 font-bold select-all break-all shadow-sm">
                                            {{ $node['result'] }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

    </div>
</x-filament-panels::page>
