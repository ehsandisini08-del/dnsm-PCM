<div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800 space-y-3">
    <div class="relative flex items-center justify-center">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200 dark:border-gray-800"></div>
        </div>
        <div class="relative bg-white dark:bg-gray-900 px-3 text-xs text-gray-500 uppercase tracking-wider font-semibold">
            Atau
        </div>
    </div>

    <a 
        href="{{ route('auth.google.redirect') }}" 
        class="w-full flex items-center justify-center gap-3 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-800 dark:text-gray-200 font-semibold text-sm shadow-sm transition-all duration-150 hover:shadow"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24">
            <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.7l3.1-3.1C17.3 1.8 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.4l3.7 2.9C6.5 7.4 9 5 12 5z"/>
            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.7-.2-2.3H12v4.6h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.9z"/>
            <path fill="#FBBC05" d="M5.6 14.7c-.2-.7-.4-1.5-.4-2.7 0-1.1.2-1.9.4-2.7L1.9 6.4C.7 8.8 0 10.3 0 12c0 1.7.7 3.2 1.9 5.6l3.7-2.9z"/>
            <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.4-6.4-5.3L1.9 16c1.8 3.8 5.6 7 10.1 7z"/>
        </svg>
        <span>{{ ($action ?? 'login') === 'register' ? 'Daftar dengan Google' : 'Masuk dengan Google' }}</span>
    </a>
</div>
