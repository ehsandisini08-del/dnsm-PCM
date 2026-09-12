<x-filament-panels::page>
    <div class="space-y-6">

        {{-- 1. SSL Status Overview Card --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-6 w-6 text-primary-500" />
                    <span>Status Sertifikat SSL / TLS</span>
                </div>
            </x-slot>

            <x-slot name="headerEnd">
                <button 
                    type="button" 
                    wire:click="refreshStatus" 
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 transition"
                >
                    <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" wire:loading.class="animate-spin" wire:target="refreshStatus" />
                    <span>Cek Ulang Status</span>
                </button>
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Status Card 1 --}}
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Status Keamanan SSL</span>
                    <div class="mt-2 flex items-center gap-2">
                        @if(!empty($sslStatus['is_valid']))
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                Aktif & Valid
                            </span>
                        @elseif(!empty($sslStatus['is_expired']))
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                Kedaluwarsa
                            </span>
                        @elseif(!empty($sslStatus['is_installed']))
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                Terpasang (Periksa)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                                <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                                Belum Terpasang
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Status Card 2 --}}
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Domain Utama</span>
                    <div class="mt-2 font-mono font-bold text-sm text-gray-900 dark:text-white truncate">
                        {{ $sslStatus['domain'] ?? 'Tidak Terdeteksi' }}
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1">
                        Penerbit: <strong class="text-gray-700 dark:text-gray-300">{{ $sslStatus['issuer'] ?? '-' }}</strong>
                    </div>
                </div>

                {{-- Status Card 3 --}}
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Masa Berlaku</span>
                    <div class="mt-2 font-bold text-sm text-gray-900 dark:text-white">
                        @if(isset($sslStatus['days_remaining']))
                            @if($sslStatus['days_remaining'] > 0)
                                <span class="{{ $sslStatus['is_expiring_soon'] ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    {{ $sslStatus['days_remaining'] }} Hari Lagi
                                </span>
                            @else
                                <span class="text-rose-600 dark:text-rose-400">Habis</span>
                            @endif
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1">
                        Kadaluwarsa: {{ $sslStatus['valid_to'] ? date('d M Y', strtotime($sslStatus['valid_to'])) : '-' }}
                    </div>
                </div>

                {{-- Status Card 4 --}}
                <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Protokol Web Server</span>
                    <div class="mt-2 flex items-center gap-2">
                        @if(!empty($sslStatus['is_https']))
                            <span class="inline-flex items-center gap-1 font-bold text-xs text-emerald-600 dark:text-emerald-400">
                                <x-filament::icon icon="heroicon-s-lock-closed" class="h-4 w-4" />
                                HTTPS Aktif (Port 443)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 font-semibold text-xs text-amber-600 dark:text-amber-400">
                                <x-filament::icon icon="heroicon-s-lock-open" class="h-4 w-4" />
                                HTTP (Port 80)
                            </span>
                        @endif
                    </div>
                    <div class="text-[11px] text-gray-500 mt-1 truncate">
                        URL: {{ $sslStatus['app_url'] ?? '-' }}
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- 2. Two-Column Action Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Form Request SSL Baru --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-plus-circle" class="h-5 w-5 text-primary-500" />
                        <span>Pasang / Terbitkan Sertifikat SSL</span>
                    </div>
                </x-slot>
                <x-slot name="description">
                    Menerbitkan sertifikat SSL gratis dari Let's Encrypt menggunakan Certbot Nginx plugin.
                </x-slot>

                <form wire:submit.prevent="requestSsl" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                            Domain / Hostname Web Panel
                        </label>
                        <input 
                            type="text" 
                            wire:model="domain" 
                            placeholder="contoh: saidnetlab.my.id" 
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                            required
                        />
                        @error('domain')
                            <p class="text-xs text-danger-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Pastikan A Record domain ini sudah mengarah ke IP publik server ini sebelum mengeksekusi.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1">
                            Email Notifikasi Administrator
                        </label>
                        <input 
                            type="email" 
                            wire:model="email" 
                            placeholder="admin@saidnetlab.my.id" 
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                            required
                        />
                        @error('email')
                            <p class="text-xs text-danger-600 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Digunakan oleh Let's Encrypt untuk notifikasi penting atau peringatan masa kedaluwarsa.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input 
                            id="force-redirect"
                            type="checkbox" 
                            wire:model="forceRedirect" 
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                        />
                        <label for="force-redirect" class="text-xs font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            Otomatis Redirect semua lalu lintas HTTP ke HTTPS (Port 443)
                        </label>
                    </div>

                    <div class="pt-2">
                        <x-filament::button 
                            type="submit" 
                            icon="heroicon-o-shield-check" 
                            class="w-full"
                            wire:target="requestSsl"
                        >
                            <span wire:loading.remove wire:target="requestSsl">Pasang Sertifikat SSL Sekarang</span>
                            <span wire:loading wire:target="requestSsl">Memproses Certbot & Nginx...</span>
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>

            {{-- Pembaruan & Diagnostik --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 text-primary-500" />
                        <span>Pembaruan & Diagnostik Certbot</span>
                    </div>
                </x-slot>
                <x-slot name="description">
                    Perpanjang masa berlaku sertifikat SSL atau uji kesiapan konfigurasi web server.
                </x-slot>

                <div class="space-y-4">
                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 space-y-2">
                        <div class="text-xs font-semibold text-gray-900 dark:text-white">Tentang Let's Encrypt & Auto-Renew:</div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                            Sertifikat Let's Encrypt berlaku selama <strong>90 hari</strong>. Certbot di server Ubuntu/Debian memiliki systemd timer bawaan yang memperbarui sertifikat otomatis ketika sisa waktu di bawah 30 hari.
                        </p>
                    </div>

                    <div class="space-y-2.5 pt-2">
                        <x-filament::button 
                            type="button" 
                            wire:click="renewSsl(false)" 
                            color="success" 
                            icon="heroicon-o-arrow-path"
                            class="w-full"
                            wire:target="renewSsl"
                        >
                            <span wire:loading.remove wire:target="renewSsl(false)">Perbarui (Renew) Sertifikat Sekarang</span>
                            <span wire:loading wire:target="renewSsl(false)">Menjalankan Certbot Renew...</span>
                        </x-filament::button>

                        <div class="grid grid-cols-2 gap-2">
                            <x-filament::button 
                                type="button" 
                                wire:click="renewSsl(true)" 
                                color="gray" 
                                icon="heroicon-o-play"
                                wire:target="renewSsl(true)"
                            >
                                <span wire:loading.remove wire:target="renewSsl(true)">Uji Dry Run</span>
                                <span wire:loading wire:target="renewSsl(true)">Testing...</span>
                            </x-filament::button>

                            <x-filament::button 
                                type="button" 
                                wire:click="testNginx" 
                                color="gray" 
                                icon="heroicon-o-wrench"
                                wire:target="testNginx"
                            >
                                <span wire:loading.remove wire:target="testNginx">Uji Nginx (-t)</span>
                                <span wire:loading wire:target="testNginx">Mengecek...</span>
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </x-filament::section>

        </div>

        {{-- 3. Terminal / Command Log Viewer --}}
        @if(!empty($outputLog))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-command-line" class="h-5 w-5 text-gray-500" />
                            <span>Output Log Terminal Certbot / Nginx</span>
                        </div>
                    </div>
                </x-slot>

                <x-slot name="headerEnd">
                    <button 
                        type="button" 
                        wire:click="clearLog" 
                        class="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 font-medium"
                    >
                        Tutup Log
                    </button>
                </x-slot>

                <div class="p-4 rounded-xl font-mono text-xs overflow-x-auto whitespace-pre-wrap {{ $outputStatus === 'success' ? 'bg-gray-950 text-emerald-400 border border-emerald-900/50' : 'bg-gray-950 text-gray-200 border border-gray-800' }}">
{{ $outputLog }}
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
