<footer class="fixed bottom-0 left-0 right-0 max-w-xl mx-auto bg-white border-t border-gray-200 shadow-[0_-1px_4px_rgba(0,0,0,0.05)]">
    <nav class="flex justify-around items-center h-20">
        <!-- Nav Item: Home -->
        <a href="/dashboard"
           class="flex flex-col items-center justify-center text-center w-full
            {{ request()->is('dashboard') ? 'text-brand-green font-bold' : 'text-gray-400 hover:text-brand-green transition-colors' }}">
            <!-- Icon -->
            <svg class="w-6 h-6 mb-1" fill="{{ request()->is('dashboard') ? 'currentColor' : 'none' }}" stroke="{{ request()->is('dashboard') ? '' : 'currentColor' }}" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
            </svg>
            <!-- Text -->
            <span class="text-xs {{ request()->is('dashboard') ? 'font-bold' : 'font-medium' }}">Home</span>
        </a>

        <!-- Nav Item: History -->
        <a href="/history"
           class="flex flex-col items-center justify-center text-center w-full
            {{ request()->is('history') ? 'text-brand-green font-bold' : 'text-gray-400 hover:text-brand-green transition-colors' }}">
            <!-- Icon -->
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <!-- Text -->
            <span class="text-xs {{ request()->is('history') ? 'font-bold' : 'font-medium' }}">History</span>
        </a>

        <!-- Nav Item: Profile -->
        <a href="/profile"
           class="flex flex-col items-center justify-center text-center w-full
            {{ request()->is('profile') ? 'text-brand-green font-bold' : 'text-gray-400 hover:text-brand-green transition-colors' }}">
            <!-- Icon -->
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <!-- Text -->
            <span class="text-xs {{ request()->is('profile') ? 'font-bold' : 'font-medium' }}">Profile</span>
        </a>
    </nav>
</footer>
