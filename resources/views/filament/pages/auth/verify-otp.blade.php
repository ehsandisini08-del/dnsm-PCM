<x-filament-panels::page.simple>
    <form wire:submit.prevent="verify" class="space-y-6">
        <div class="space-y-3 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 ring-8 ring-primary-50/50 dark:ring-primary-950/30">
                <x-filament::icon icon="heroicon-o-shield-check" class="h-8 w-8" />
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ $this->getSubheading() }}
            </div>
        </div>

        <div class="space-y-2">
            <label for="otp-input" class="block text-center text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                6-Digit Kode OTP
            </label>
            <input 
                id="otp-input"
                type="text" 
                wire:model="otp" 
                maxlength="6" 
                autofocus
                placeholder="------"
                class="block w-full text-center tracking-[0.5em] text-2xl font-mono font-bold py-3 rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-950 text-gray-900 dark:text-white focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                required
            />
            @error('otp')
                <p class="text-xs text-danger-600 dark:text-danger-400 text-center font-semibold mt-1">{{ $message }}</p>
            @enderror
        </div>

        <x-filament::button type="submit" class="w-full" wire:target="verify">
            <span wire:loading.remove wire:target="verify">Verifikasi Kode OTP</span>
            <span wire:loading wire:target="verify">Memverifikasi...</span>
        </x-filament::button>

        <div class="flex items-center justify-between text-xs pt-2 border-t border-gray-100 dark:border-gray-800">
            <button 
                type="button" 
                wire:click="resend" 
                class="text-primary-600 dark:text-primary-400 hover:underline font-semibold flex items-center gap-1"
            >
                <x-filament::icon icon="heroicon-m-arrow-path" class="h-3.5 w-3.5" />
                <span>Kirim Ulang Kode</span>
            </button>

            <button 
                type="button" 
                wire:click="cancel" 
                class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
            >
                Ganti Akun / Batal
            </button>
        </div>
    </form>
</x-filament-panels::page.simple>
