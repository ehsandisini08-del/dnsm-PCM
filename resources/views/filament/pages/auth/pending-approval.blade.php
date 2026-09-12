<x-filament-panels::page.simple>
    <div class="space-y-6 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 ring-8 ring-amber-50/50 dark:ring-amber-950/30">
            <x-filament::icon icon="heroicon-o-clock" class="h-9 w-9" />
        </div>

        <div class="space-y-2">
            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                Email Anda Berhasil Diverifikasi!
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                Halo <strong class="text-gray-800 dark:text-gray-200">{{ $userName ?: 'Pengguna' }}</strong> ({{ $userEmail }}), pendaftaran akun Anda telah berhasil dan email Anda sudah aktif.
            </p>
            <div class="p-3.5 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/60 dark:bg-amber-950/30 text-xs text-amber-800 dark:text-amber-300 font-medium">
                Untuk menjaga keamanan server DNS ISP, setiap pendaftaran baru harus disetujui terlebih dahulu oleh <strong>Super Administrator</strong> sebelum Anda dapat mengakses dashboard dan fitur sistem.
            </div>
            <p class="text-[11px] text-gray-400 pt-1">
                Anda akan menerima notifikasi email setelah akun Anda disetujui dan diberikan hak akses.
            </p>
        </div>

        <div class="pt-2">
            <x-filament::button wire:click="backToLogin" color="gray" class="w-full">
                Kembali ke Halaman Login
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page.simple>
